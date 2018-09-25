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
 * Privacy provider implementation for mod_newsletter.
 *
 * @package newsletter
 * @copyright 2018 Michael Pollak <moodle@michaelpollak.org>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_newsletter\privacy;
defined('MOODLE_INTERNAL') || die();

// TODO: Which are needed?
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;
use core_privacy\manager;

class provider implements
    // This plugin stores personal data.
    \core_privacy\local\metadata\provider,

    // This plugin is a core_user_data_provider.
    \core_privacy\local\request\plugin\provider {
    /**
     * Return the fields which contain personal data.
     *
     * @param collection $collection a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
     */
    public static function get_metadata(collection $collection) : collection {

        $collection->add_database_table(
            'newsletter_subscriptions',
            [
                'userid' => 'privacy:metadata:newsletter_subscriptions:userid',
                'newsletterid' => 'privacy:metadata:newsletter_subscriptions:newsletterid',
                'health' => 'privacy:metadata:newsletter_subscriptions:health',
                'timesubscribed' => 'privacy:metadata:newsletter_subscriptions:timesubscribed',
                'timestatuschanged' => 'privacy:metadata:newsletter_subscriptions:timestatuschanged',
                'subscriberid' => 'privacy:metadata:newsletter_subscriptions:subscriberid',
                'unsubscriberid' => 'privacy:metadata:newsletter_subscriptions:unsubscriberid',
                'sentnewsletters' => 'privacy:metadata:newsletter_subscriptions:sentnewsletters',
            ],
            'privacy:metadata:newsletter_subscriptions'
        );
        $collection->add_database_table(
            'newsletter_bounces',
            [
                'userid' => 'privacy:metadata:newsletter_bounces:userid',
                'issueid' => 'privacy:metadata:newsletter_bounces:issueid',
                'statuscode' => 'privacy:metadata:newsletter_bounces:statuscode',
                'timecreated' => 'privacy:metadata:newsletter_bounces:timecreated',
                'type' => 'privacy:metadata:newsletter_bounces:type',
                'timereceived' => 'privacy:metadata:newsletter_bounces:timereceived',
            ],
            'privacy:metadata:newsletter_bounces'
        );
        $collection->add_database_table(
            'newsletter_deliveries',
            [
                'userid' => 'privacy:metadata:newsletter_deliveries:userid',
                'issueid' => 'privacy:metadata:newsletter_deliveries:issueid',
                'newsletterid' => 'privacy:metadata:newsletter_deliveries:newsletterid',
                'delivered' => 'privacy:metadata:newsletter_deliveries:delivered',
            ],
            'privacy:metadata:newsletter_deliveries'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {

        // Fetch all information relevant to the user.
        $sql = "SELECT c.id
            FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {newsletter} new ON new.id = cm.instance
            INNER JOIN {newsletter_subscriptions} sub ON sub.newsletterid = new.id
            WHERE sub.userid = :userid";
        $params = [
            'modname'       => 'newsletter',
            'contextlevel'  => CONTEXT_MODULE,
            'userid'        => $userid,
        ];
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    /**
     * Export personal data for the given approved_contextlist. User and context information is contained within the contextlist.
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (!$contextlist->count()) {
            return;
        }

        $user = $contextlist->get_user();

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT  cm.id AS cmid,
                        news.name AS newslettername,
                        cm.course AS courseid,
                        sub.timesubscribed AS timesubscribed,
                        issue.title AS issuename
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {newsletter} news ON news.id = cm.instance
            INNER JOIN {newsletter_subscriptions} sub ON sub.newsletterid = news.id
            INNER JOIN {newsletter_issues} issue ON news.id = issue.newsletterid
                 WHERE c.id {$contextsql}
                       AND sub.userid = :userid
              ORDER BY cm.id";

        $params = ['modname' => 'newsletter', 'contextlevel' => CONTEXT_MODULE, 'userid' => $user->id] + $contextparams;

        // Reference to the instance seen in the last iteration of the loop. By comparing this with the current record, and
        // because we know the results are ordered, we know when we've moved to the subscription for a new instance and therefore
        // when we can export the complete data for the last instance. Used this idea from mod_choice, thank you.
        $lastcmid = null;

        $newslettersubs = $DB->get_recordset_sql($sql, $params);
        foreach ($newslettersubs as $newslettersub) {
            // If we've moved to a new instance, then write the last newsletterdata and reinit the newsletterdata array.
            if ($lastcmid != $newslettersub->cmid) {
                if (!empty($newsletterdata)) {
                    $context = \context_module::instance($lastcmid);
                    self::export_newsletter($newsletterdata, $context, $user);
                }
                $newsletterdata = [
                    'issuename' => [],
                    'timesubscribed' => \core_privacy\local\request\transform::datetime($newslettersub->timesubscribed),
                ];
            }
            $newsletterdata['issuename'][] = $newslettersub->issuename;
            $lastcmid = $newslettersub->cmid;
        }
        $newslettersubs->close();

        // The data for the last activity won't have been written yet, so make sure to write it now!
        if (!empty($newsletterdata)) {
            $context = \context_module::instance($lastcmid);
            self::export_newsletter($newsletterdata, $context, $user);
        }

    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if (!$context instanceof \context_module) {
            return;
        }

        if ($cm = get_coursemodule_from_id('newsletter', $context->instanceid)) {
            $DB->delete_records('newsletter_subscriptions', ['newsletterid' => $cm->instance]);
            $DB->delete_records('newsletter_deliveries', ['newsletterid' => $cm->instance]);
        }
    }



    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {

            if (!$context instanceof \context_module) {
                continue;
            }
            $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid], MUST_EXIST);
            $DB->delete_records('newsletter_subscriptions', ['newsletterid' => $instanceid, 'userid' => $userid]);
            $DB->delete_records('newsletter_deliveries', ['newsletterid' => $instanceid, 'userid' => $userid]);
        }
    }

    /**
     * Export the supplied personal data for a newsletter instance, along with any generic data or area files.
     *
     * @param array $newsletterdata the personal data to export for the subscription.
     * @param \context_module $context the context of the subscription.
     * @param \stdClass $user the user record
     */
    protected static function export_newsletter(array $newsletterdata, \context_module $context, \stdClass $user) {
        // Fetch the generic module data.
        $contextdata = helper::get_context_data($context, $user);

        // Merge with newsletterdata and write it.
        $contextdata = (object)array_merge((array)$contextdata, $newsletterdata);
        writer::with_context($context)->export_data([], $contextdata);

        // Write generic module intro files.
        helper::export_context_files($context, $user);
    }

}