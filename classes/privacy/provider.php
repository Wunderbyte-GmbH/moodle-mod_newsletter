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
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        return; // TODO:
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        return; // TODO:
    }



    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        return; // TODO:
    }
}