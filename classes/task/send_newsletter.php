<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Send newsletters to recipients and monitor the process.
 *
 * @package   mod_newsletter
 * @category  task
 * @copyright 2018 David Bogner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_newsletter\task;
use core_user;
use mod_newsletter\cron_helper;
use html_writer;
use mod_newsletter\issue_form;
use mod_newsletter\issue_parser;
use stdClass;
use mod_newsletter\newsletter;
use moodle_url;

require_once($CFG->dirroot . '/mod/newsletter/lib.php');

defined('MOODLE_INTERNAL') || die();

class send_newsletter extends \core\task\scheduled_task {

    public function get_name() {
        // Shown in admin screens.
        return get_string('send_newsletter', 'mod_newsletter');
    }

    public function execute() {
        global $DB, $CFG;
        $config = get_config('mod_newsletter');

        $debugoutput = $config->debug;
        if ($debugoutput) {
            mtrace("\n");
        }

        if ($debugoutput) {
            mtrace("Deleting expired inactive user accounts...\n");
        }

        $query = "SELECT u.id
                FROM {user} u
               INNER JOIN {newsletter_subscriptions} ns ON u.id = ns.userid
               WHERE u.confirmed = 0
                 AND :now - u.timecreated > :limit";
        $ids = $DB->get_fieldset_sql($query,
                array('now' => time(), 'limit' => $config->activation_timeout));

        if (!empty($ids)) {
            list($insql, $params) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('user', "id " . $insql, $params);
            $DB->delete_records_select('newsletter_subscriptions', "userid " . $insql, $params);
        }

        if ($debugoutput) {
            mtrace("Done.\n");
        }
        cron_helper::lock();

        $unsublinks = array();
        if ($debugoutput) {
            mtrace("Starting scheduled task: Send newsletter...\n");
            mtrace("Collecting data...\n");
        }

        $nounsublink = array(); // Store userids that don't receive unsublinks in an array.
        $issues = $DB->get_records('newsletter_issues',
                array('delivered' => NEWSLETTER_DELIVERY_STATUS_UNKNOWN));
        foreach ($issues as $issue) {
            if ($issue->publishon <= time() && !$DB->record_exists('newsletter_deliveries',
                    array('issueid' => $issue->id))) {
                // Populate the deliveries table.
                $recipients = newsletter_get_all_valid_recipients($issue->newsletterid);
                $subscriptionobjects = array();
                foreach ($recipients as $userid => $recipient) {
                    $sub = new stdClass();
                    $sub->userid = $userid;
                    $sub->issueid = $issue->id;
                    $sub->newsletterid = $issue->newsletterid;
                    $sub->delivered = 0;
                    $subscriptionobjects[] = $sub;
                    if ($recipient->nounsublink) {
                        // Who doesn't receive unsublink per issue.
                        $nounsublink[$issue->id][] = $userid;
                    }
                }
                $DB->insert_records('newsletter_deliveries', $subscriptionobjects);
                $DB->set_field('newsletter_issues', 'delivered',
                        NEWSLETTER_DELIVERY_STATUS_INPROGRESS, array('id' => $issue->id));
            }
        }

        if ($debugoutput) {
            mtrace("Data collection complete. Delivering...");
        }

        $issuestodeliver = $DB->get_records('newsletter_issues',
                array('delivered' => NEWSLETTER_DELIVERY_STATUS_INPROGRESS), null, 'id, newsletterid');
        foreach ($issuestodeliver as $issueid => $issue) {
            $urlinfo = parse_url($CFG->wwwroot);
            $hostname = $urlinfo['host'];
            $newsletter = newsletter::get_newsletter_by_instance($issue->newsletterid);
            $issue = $newsletter->get_issue($issue->id);

            if ($newsletter->get_instance()->subscriptionmode != NEWSLETTER_SUBSCRIPTION_MODE_FORCED) {
                $url = new moodle_url('/mod/newsletter/subscribe.php',
                        array('id' => $newsletter->get_course_module()->id));
                $unsublinks[$newsletter->get_instance()->id] = $url;
            }
            if ($debugoutput) {
                mtrace(
                        "Processing newsletter (id = {$issue->newsletterid}), issue \"{$issue->title}\" (id = {$issue->id})...");
            }
            $fs = get_file_storage();
            $files = $fs->get_area_files($newsletter->get_context()->id, 'mod_newsletter',
                    NEWSLETTER_FILE_AREA_ATTACHMENT, $issue->id, "", false);
            $attachment = array();
            foreach ($files as $file) {
                $attachment[$file->get_filename()] = $file->copy_content_to_temp();
            }
            // To ensure this link is clicked by the user we add a secret from userid and firstaccess.
            if (isset($unsublinks[$newsletter->get_instance()->id])) {
                $url = $unsublinks[$newsletter->get_instance()->id];
                $url->param(NEWSLETTER_PARAM_USER, 'replacewithuserid');
                $url->param(NEWSLETTER_PARAM_HASH, 'replacewithsecret');
                // The id in the html elements are used to better remove them later when no
                // unsubscription link should be sent.
                $issue->htmlcontent .= html_writer::start_div('',
                        array('id' => 'unsubscriptionlink'));
                $issue->htmlcontent .= html_writer::empty_tag('hr');
                $issue->htmlcontent .= html_writer::link($url,
                        get_string('unsubscribe_link_text', 'mod_newsletter'));
                $issue->htmlcontent .= html_writer::end_div();
            }

            // Generate table of content.
            $parsedhtml = new issue_parser($issue, true);
            $issue->htmlcontent = $parsedhtml->get_parsed_html();

            $issue->htmlcontent = file_rewrite_pluginfile_urls($issue->htmlcontent, 'pluginfile.php',
                    $newsletter->get_context()->id, 'mod_newsletter', NEWSLETTER_FILE_AREA_ISSUE,
                    $issue->id, issue_form::editor_options($newsletter->get_context(), $issue->id));
            $plaintexttmp = format_text_email($issue->htmlcontent, FORMAT_HTML);
            $htmltmp = $newsletter->inline_css($issue->htmlcontent, $issue->stylesheetid);
            $deliveries = $DB->get_records('newsletter_deliveries',
                    array('issueid' => $issueid, 'delivered' => 0));

            // Configure the $userfrom. All mails are sent from the support user (but as return path for
            // bounce processing, the address in the newsletter admin settings is used.
            $userfrom = core_user::get_support_user();

            if (empty($deliveries)) {
                $DB->set_field('newsletter_issues', 'delivered',
                        NEWSLETTER_DELIVERY_STATUS_DELIVERED, array('id' => $issueid));
                break;
            }
            foreach ($deliveries as $deliveryid => $delivery) {
                $recipient = $DB->get_record('user', array('id' => $delivery->userid));
                if ($debugoutput) {
                    mtrace("Sending message to {$recipient->email}... ");
                }

                // Make this explicit, $htmltmp and $plaintmp need to remain unchanged.
                $htmluser = $htmltmp;
                $plaintextuser = $plaintexttmp;

                // Remove unsub link.
                if (isset($nounsublink[$issueid]) && in_array($delivery->userid,
                        $nounsublink[$issueid])) {
                    if ($debugoutput) {
                        mtrace("Sending no unsublink to {$recipient->email} for {$issueid}");
                    }
                    // Find unsub link.
                    $doc = new \DOMDocument();
                    $doc->loadHTML($htmluser, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                    $unsublink = $doc->getElementById('unsubscriptionlink');
                    $unsublink->parentNode->removeChild($unsublink);
                    $htmluser = $doc->saveHTML();
                    $unsubpattern = '/' . get_string('unsubscribe_link_text', 'mod_newsletter') . '(.+)replacewithsecret]/s';
                    $plaintextuser = preg_replace($unsubpattern, '', $plaintextuser);
                }

                // Replace user specific data here.
                $toreplace = array();
                $replacement = array();

                $tags = $parsedhtml->get_supported_tags();
                foreach ($tags as $name) {
                    $toreplace[$name] = "news://" . $name . "/";
                    if ($name == 'lastname' or $name == 'firstname') {
                        $replacement[$name] = $recipient->$name;
                    } else if ($name == 'fullname') {
                        $replacement[$name] = fullname($recipient);
                    } else {
                        $replacement[$name] = '';
                    }
                }
                // Replace userid in unsubscribe link.
                $toreplace['replacewithuserid'] = 'replacewithuserid';
                $replacement['replacewithuserid'] = $delivery->userid;

                // Unsubscribe link.
                $toreplace['replacewithsecret'] = 'replacewithsecret';
                $replacement['replacewithsecret'] = md5(
                        $recipient->id . "+" . $recipient->firstaccess);

                $plaintextuser = str_replace($toreplace, $replacement, $plaintextuser);
                $htmluser = str_replace($toreplace, $replacement, $htmluser);

                $userfrom->customheaders = array( // Headers to make emails easier to track.
                'Precedence: Bulk',
                    'List-Id: "' . $newsletter->get_instance()->name . '" <newsletter' . $newsletter->get_course_module()->instance .
                                '@' . $hostname . '>',
                    'List-Help: ' . $CFG->wwwroot . '/mod/newsletter/view.php?id=' . $newsletter->get_context()->instanceid,
                    'Message-ID: ' . newsletter_get_email_message_id($issue->id, $recipient->id,
                            $hostname), 'X-Course-Id: ' . $newsletter->get_instance()->course,
                    'X-Course-Name: ' . format_string($newsletter->get_course()->fullname, true));
                $result = newsletter_email_to_user($recipient, $userfrom, $issue->title,
                        $plaintextuser, $htmluser, $attachment);
                if ($debugoutput) {
                    echo ($result ? "OK" : "FAILED") . "!\n";
                }
                $DB->set_field('newsletter_deliveries', 'delivered', time(), array('id' => $deliveryid));
                $sql = "UPDATE {newsletter_subscriptions} SET sentnewsletters = sentnewsletters + 1
                    WHERE newsletterid = :newsletterid AND userid = :userid ";
                $params = array('newsletterid' => $issue->newsletterid,
                    'userid' => $delivery->userid);
                $DB->execute($sql, $params);
                if (!$DB->record_exists('newsletter_deliveries',
                        array('issueid' => $issue->id, 'delivered' => 0))) {
                    $DB->set_field('newsletter_issues', 'delivered',
                            NEWSLETTER_DELIVERY_STATUS_DELIVERED, array('id' => $issueid));
                }
            }
        }

        if ($debugoutput) {
            mtrace("Delivery complete. Updating database...\n");
        }
        if ($debugoutput) {
            mtrace("Database update complete. Cleaning up...\n");
        }
        cron_helper::unlock();
    }
}
