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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * This file processes bounces
 *
 *
 * @package mod_newsletter
 * @copyright 2015 onwards David Bogner <info@edulabs.org>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_newsletter\bounce;

defined('MOODLE_INTERNAL') || die;

require_once ('../../lib.php');
require_once ("$CFG->dirroot/mod/newsletter/lib/Handler.php");
require_once ("$CFG->dirroot/mod/newsletter/lib/CwsDebug.php");
use MailBounceHandler\Handler;
use MailBounceHandler\CwsDebug;
use MailBounceHandler\Models\Result;

define ( 'NEWSLETTER_BOUNCE_HARD', 0 );
define ( 'NEWSLETTER_BOUNCE_SOFT', 1 );

class bounceprocessor extends Handler {

    /**
     *
     * @var integer unix timestamp
     */
    protected $timecreated = null;

    /**
     *
     * @var array
     */
    public $bounces = array();

    /**
     *
     * @var array of strings
     */
    public $issues = array();

    /**
     *
     * @var array of issueids
     */
    public $issueids = array();

    /**
     *
     * @var boolean
     */
    public $testrun = false;

    /**
     *
     * @var Result
     */
    public $result = null;

    /**
     * Apply the settings
     *
     * @param \stdClass $conf bounce settings of newsletter
     */
    public function __construct($conf) {
        $cwsDebug = new CwsDebug();
        parent::__construct($cwsDebug);
        $this->setDeleteProcessMode();
        $this->timecreated = time();

        if (isset($conf->host)) {
            $this->setMailboxHost($conf->host);
        }
        if (isset($conf->username)) {
            $this->setMailboxUsername($conf->username);
        }
        if (isset($conf->password)) {
            $this->setMailboxPassword($conf->password);
        }
        if (isset($conf->port)) {
            $this->setMailboxPort($conf->port);
        }
        if (isset($conf->service) AND $conf->service == "pop3") {
            $this->setImapMailboxService(false);
        }
        if (isset($conf->service_option)) {
            $this->setMailboxSecurity = $conf->service_option;
        }
        if (isset($conf->debug)) {
            $cwsDebug->setDebugVerbose();
        }
    }

    /**
     * Process mails and show results in the browser does not save or delete anything. just for information This can be used to test the imap/pop3
     * settings.
     */
    public function testrun() {
        $cwsDebug = new CwsDebug();
        $this->setNeutralProcessMode();
        $cwsDebug->setDebugVerbose();
        $cwsDebug->setEchoMode();
        $this->testrun = true;
        $this->process_bounces();
    }

    /**
     * Write bounces to database the bounce time is used to check if bounce was already processed
     * just in case it was not deleted after it was processed.
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function process_bounces() {
        global $DB;
        // TODO: threshhold should be a reasonable time between publishing date and a date where no bounces are expected anymore.
        $threshold = $this->timecreated - 5 * 86400;
        $this->issues = $DB->get_records_select('newsletter_issues', 'publishon > :threshold',
                array('threshold' => $threshold), 'id', 'id, title');
        $this->issueids = array_keys($this->issues);
        $this->result = $this->processMails();

        $getusersql = 'SELECT MAX(u.id)
				FROM {user} u
				WHERE u.email = ? AND u.id IN (
					SELECT ns.userid
					FROM {newsletter_subscriptions} ns
				)';
        $mails = $this->result->getMails();
        if (!empty($mails)) {
            foreach ($mails as $mail) {
                $recipients = $mail->getRecipients();
                $subject = $mail->getSubject();
                foreach ($recipients as $recipient) {

                    $userid = $DB->get_field_sql($getusersql, array($recipient->getEmail()));
                    // check if userid exists and if this bounce is already saved
                    if ($userid) {
                        $issueid = false;
                        $bouncedata = new \stdClass();
                        $bouncedata->timecreated = $this->timecreated;
                        $bouncetype = $recipient->getBounceType();
                        if ($bouncetype == 'failed' || $bouncetype == 'hard') {
                            $bouncedata->type = NEWSLETTER_BOUNCE_HARD;
                        } else {
                            $bouncedata->type = NEWSLETTER_BOUNCE_SOFT;
                        }
                        $bouncedata->userid = $userid;
                        $bouncedata->statuscode = $recipient->getStatus();
                        // Not used anymore. Same as timecreated. TODO: Remove.
                        $bouncedata->timereceived = $this->timecreated;
                        // Guesss the issue id the last guess is the latest issue.
                        if (!empty($this->issues)) {
                            foreach ($this->issues as $key => $value) {
                                if (preg_match("/" . $value->title . "/", $subject) == 1) {
                                    $issueid = $key;
                                    break;
                                }
                            }
                            if ($issueid == false) {
                                $issueid = max(array_keys($this->issues));
                            }
                        } else {
                            $issueid = $DB->get_field_sql(
                                    'SELECT id, MAX(publishon) FROM {newsletter_issues} WHERE publishon < :time',
                                    array('time' => $this->timecreated));
                        }
                        $bouncedata->issueid = $issueid;
                        $this->bounces[] = $bouncedata;
                    }
                    /**
                    $this->processMailMove($mail);
                    $this->processMailDelete($mail);
                    $this->result->getCounter()->incrDeleted();
                    */
                }
            }
        }
        if (!empty($this->bounces)){
            $DB->insert_records('newsletter_bounces', $this->bounces);
        }
    }



    /**
     * Update the health satus of the subscribers. Always take into account the ten last mails sent. If a person receives
     * the newsletter since years and the mailadress changes, the bounce ratio would otherwise also take years in order to
     * blacklist the user. So only consider most recent mails.
     */
    public function update_health() {
        global $DB;
        foreach ($this->bounces as $bounce) {
            // Number of latest newsletters to take into account.
            $newsletternumber = 15;
            // Go one year back for the bounces.
            $since = time() - 31556926;
            $bounces = $DB->count_records_select('newsletter_bounces', 'userid = :userid AND timecreated > :since',
                    array('userid' => $bounce->userid, 'since' => $since));
            $hardbounces = $DB->count_records_select('newsletter_bounces',
                    'userid = :userid AND type = :hardbounces AND timecreated > :since',
                    array('userid' => $bounce->userid, 'hardbounces' => NEWSLETTER_BOUNCE_HARD, 'since' => $since));
            $sent = $DB->get_record_sql(
                    'SELECT SUM(sentnewsletters) as sent FROM {newsletter_subscriptions} WHERE userid = :userid',
                    array('userid' => $bounce->userid));
            // Start with $newsletternumber to have a bounce ratio for the first mails sent under the delete threshold.
            if($sent->sent < $newsletternumber) {
                $sent->sent = $newsletternumber;
            }

            if ($bounces > 0) {
                // Hardbounces have more weight in calculation of bounce ratio. So adding to all bounces the hardbounces again.
                $bounceratio = ($hardbounces + $bounces) / $sent->sent;
            } else {
                $bounceratio = 0;
            }

            switch ($bounceratio) {
                case $bounceratio > 0.3:
                    $health = NEWSLETTER_SUBSCRIBER_STATUS_BLACKLISTED;
                    break;
                case $bounceratio >= 0.2:
                    $health = NEWSLETTER_SUBSCRIBER_STATUS_PROBLEMATIC;
                    break;
                case $bounceratio < 0.2:
                    $health = NEWSLETTER_SUBSCRIBER_STATUS_OK;
                    break;
                default:
                    $health = NEWSLETTER_SUBSCRIBER_STATUS_OK;
            }

            $subscriptions = $DB->get_records_select('newsletter_subscriptions',
                    'userid = :userid AND ( health = :ok OR health = :problematic )',
                    array('userid' => $bounce->userid, 'ok' => NEWSLETTER_SUBSCRIBER_STATUS_OK,
                        'problematic' => NEWSLETTER_SUBSCRIBER_STATUS_PROBLEMATIC));
            foreach ($subscriptions as $subcription) {
                if ($subcription->health != $health) {
                    $DB->set_field('newsletter_subscriptions', 'health', $health,
                            array('id' => $subcription->id));
                }
            }
        }
    }
}
