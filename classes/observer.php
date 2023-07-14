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
 * Event observers used in newsletter.
 *
 * @package mod_newsletter
 * @copyright 2015 David Bogner, edulabs.org <info@edulabs.org>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Event observer for mod_newsletter.
 */
class mod_newsletter_observer {

    /**
     * subscribe user to newsletters
     *
     * @param integer $userid
     * @param integer $courseid
     */
    public static function subscribe($userid, $courseid) {
        global $DB, $CFG;

        // Needed for constants.
        require_once($CFG->dirroot . '/mod/newsletter/lib.php');

        $sql = "SELECT n.id, n.subscriptionmode, cm.id AS cmid
            FROM {newsletter} n
            JOIN {course_modules} cm ON cm.instance = n.id
            JOIN {modules} m ON m.id = cm.module
            LEFT JOIN {newsletter_subscriptions} ns ON ns.newsletterid = n.id AND ns.userid = :userid
            JOIN {user_info_field} uif
            ON uif.id = n.profilefield
            JOIN {user_info_data} uid
            ON uid.fieldid = uif.id
            JOIN {user} u
            ON u.id = uid.userid
            WHERE n.course = :courseid
            AND m.name = 'newsletter'
            AND ns.id IS NULL";
        $params = array('courseid' => $courseid, 'userid' => $userid);

        // Get all newsletter that have activated a profilefield subscription
        $sql1 = "SELECT n.id
        FROM {newsletter} n
        JOIN {user_info_field} uif
        ON uif.id = n.profilefield
        JOIN {user_info_data} uid
        ON uid.fieldid = uif.id
        JOIN {user} u
        ON u.id = uid.userid
        WHERE u.id = :userid
        AND uid.data = 1
        AND n.course = :courseid";
        $newsletterids = $DB->get_fieldset_sql($sql1, $params);

        $newsletters = $DB->get_records_sql($sql, $params);
        foreach ($newsletters as $newsletter) {
            if($newsletter->subscriptionmode == NEWSLETTER_SUBSCRIPTION_MODE_OPT_OUT || 
            $newsletter->subscriptionmode == NEWSLETTER_SUBSCRIPTION_MODE_FORCED) {
                $newsletter = mod_newsletter\newsletter::get_newsletter_by_instance($newsletter->id);
                $newsletter->subscribe($userid);
            } else if($newsletter->subscriptionmode == NEWSLETTER_SUBSCRIPTION_MODE_OPT_IN) {
                if (in_array($newsletter->id, $newsletterids)) {
                    $newsletter = mod_newsletter\newsletter::get_newsletter_by_instance($newsletter->id);
                    $newsletter->subscribe($userid);
                } else {
                    $newsletter = mod_newsletter\newsletter::get_newsletter_by_instance($newsletter->id);
                }
            }
        }
    } 

    /**
     * Triggered via user_created event. Subscribes user to newsletter on frontpage
     *
     * @param \core\event\user_created $event
     */
    public static function user_created(\core\event\user_created $event) {
        $user = $event->get_record_snapshot('user', $event->objectid);
        self::subscribe($user->id, 1);
    }

    /**
     * Triggered via user_created event. Subscribes user to newsletter on frontpage
     *
     * @param \core\event\user_created $event
     */
    public static function user_updated(\core\event\user_updated $event) {
        $user = $event->get_record_snapshot('user', $event->objectid);
        self::subscribe($user->id, 1);
    }


    
    /**
     * Triggered via user_enrolment_deleted event.
     *
     * @param \core\event\user_enrolment_deleted $event
     */
    public static function user_enrolment_deleted(\core\event\user_enrolment_deleted $event) {
        global $DB;

        // NOTE: this has to be as fast as possible.
        // Get user enrolment info from event.
        $cp = (object) $event->other['userenrolment'];
        if ($cp->lastenrol) {
            $params = array('userid' => $cp->userid, 'courseid' => $cp->courseid);
            $DB->delete_records_select('newsletter_subscriptions',
                    'userid = :userid AND newsletterid IN (SELECT n.id FROM {newsletter} n WHERE n.course = :courseid)',
                    $params);
        }
    }

    /**
     * Observer for role_assigned event Subscribes user to newsletter of the related course
     *
     * @param \core\event\role_assigned $event
     */
    public static function role_assigned(\core\event\role_assigned $event) {
        $context = context::instance_by_id($event->contextid, MUST_EXIST);

        // If contextlevel is course then only subscribe user. Role assignment
        // at course level means user is enroled in course and can subscribe to newsletter.
        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }
        self::subscribe($event->relateduserid, $event->courseid);
    }

    /**
     * Observer for the user_deleted event deletes all newsletter subscriptions of the user
     *
     * @param \core\event\user_deleted $event
     */
    public static function user_deleted(\core\event\user_deleted $event) {
        global $DB;

        $params = array('userid' => $event->relateduserid);
        $DB->delete_records_select('newsletter_subscriptions', 'userid = :userid', $params);
    }
}
