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
 * class used by newsletter subscriber selection controls
 *
 * @package mod-newsletter
 * @copyright 2015 David Bogner
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_newsletter\subscription;
use user_selector_base;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/user/selector/lib.php');
require_once($CFG->dirroot . '/mod/newsletter/lib.php');


class mod_newsletter_potential_subscribers extends user_selector_base {

    /**
     *
     * @var int newsletterid
     */
    protected $newsletterid;

    /**
     *
     * @var integer
     */
    protected $courseid;

    /**
     *
     * @param string $name
     * @param array $options
     */
    public function __construct($name, $options) {
        $this->newsletterid = $options['newsletterid'];
        $this->courseid = $options['courseid'];
        parent::__construct($name, $options);
    }

    /**
     *
     * @return array
     */
    protected function get_options() {
        $options = parent::get_options();
        $options['file'] = 'mod/newsletter/classes/subscription/newsletter_user_subscription.php';
        $options['newsletterid'] = $this->newsletterid;
        $options['courseid'] = $this->courseid;
        // Add our custom options to the $options array.
        return $options;
    }

    /**
     * Candidate users
     *
     * @param string $search
     * @return array of available users
     */
    public function find_users($search) {
        global $DB;
        // By default wherecondition reget_enrolled_sql()trieves all users except the deleted, not confirmed and guest.

        $fields = 'SELECT ' . $this->required_fields_sql('u');
        $countfields = 'SELECT COUNT(1)';
        list($wherecondition, $params) = $this->search_sql($search, 'u');

        $params['newsletterid'] = $this->newsletterid;
        if ($this->courseid == 1) {
            $sql = "	FROM {user} u
						LEFT JOIN {newsletter_subscriptions} ns ON (ns.userid = u.id AND ns.newsletterid = :newsletterid)
						WHERE $wherecondition
						AND ns.id IS NULL";
        } else { // Only enrolled users selectable.
            $eparams = array();
            $eparams['courseid'] = $this->courseid;
            $eparams['now1'] = $eparams['now2'] = strtotime("now");
            $enrolsql = "	SELECT DISTINCT u.id FROM {user} u
							JOIN {user_enrolments} ue ON ue.userid = u.id
							JOIN {enrol} e ON (e.id = ue.enrolid AND e.courseid = :courseid)
      						WHERE $wherecondition
							AND u.id <> 1
							AND ue.status = 0
							AND e.status = 0
							AND ue.timestart < :now1
							AND (ue.timeend = 0 OR ue.timeend > :now2)";
            $params = array_merge($params, $eparams);
            $sql = "	FROM {user} u INNER JOIN ($enrolsql) enrolled_users_view ON u.id = enrolled_users_view.id
						LEFT JOIN {newsletter_subscriptions} ns ON (ns.userid = enrolled_users_view.id AND ns.newsletterid = :newsletterid)
						WHERE ns.id IS NULL";
        }

        list($sort, $sortparams) = users_order_by_sql('u', $search, $this->accesscontext);
        $order = ' ORDER BY ' . $sort;

        // Check to see if there are too many to show sensibly.
        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialmemberscount > $this->maxusersperpage) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        $availableusers = $DB->get_records_sql($fields . $sql . $order,
                array_merge($params, $sortparams));

        if (empty($availableusers)) {
            return array();
        }

        if ($search) {
            $groupname = get_string('subscribercandidatesmatching', 'newsletter', $search);
        } else {
            $groupname = get_string('subscribercandidates', 'mod_newsletter');
        }
        return array($groupname => $availableusers);
    }
}


/**
 * Subscribed users.
 */
class mod_newsletter_existing_subscribers extends user_selector_base {

    protected $courseid;

    protected $newsletterid;

    protected $newsletter;

    public function __construct($name, $options) {
        $this->newsletterid = $options['newsletterid'];
        $this->newsletter = $options['newsletter'];
        parent::__construct($name, $options);
    }

    /**
     * Candidate users
     *
     * @param string $search
     * @return array
     */
    public function find_users($search) {
        global $DB;
        // By default wherecondition retrieves all users except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'u');
        $params['newsletterid'] = $this->newsletterid;

        $fields = 'SELECT ' . $this->required_fields_sql('u') . ', ns.health, ns.id as subid ';
        $countfields = 'SELECT COUNT(1)';

        $sql = " FROM {user} u
		JOIN {newsletter_subscriptions} ns ON (ns.userid = u.id AND ns.newsletterid = :newsletterid)
		WHERE $wherecondition";

        list($sort, $sortparams) = users_order_by_sql('u', $search, $this->accesscontext);
        $order = ' ORDER BY ' . $sort;

        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialmemberscount > $this->maxusersperpage) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        $availableusers = $DB->get_records_sql($fields . $sql . $order,
                array_merge($params, $sortparams));

        if (empty($availableusers)) {
            return array();
        }

        if ($search) {
            $groupname = get_string('subscribedusersmatching', 'newsletter', $search);
        } else {
            $groupname = get_string('subscribedusers', 'mod_newsletter');
        }

        return array($groupname => $availableusers);
    }

    /**
     *
     * {@inheritDoc}
     * @see user_selector_base::get_options()
     */
    protected function get_options() {
        $options = parent::get_options();
        $options['newsletterid'] = $this->newsletterid;
        $options['file'] = 'mod/newsletter/classes/subscription/newsletter_user_subscription.php';
        return $options;
    }

    /**
     * Convert a user object to a string suitable for displaying as an option in the list box.
     *
     * @param object $user the user to display.
     * @return string a string representation of the user.
     */
    public function output_user($user) {
        $out = '';

        if ($user->health == NEWSLETTER_SUBSCRIBER_STATUS_UNSUBSCRIBED) {
            $out .= '(!) ';
        }
        $out .= fullname($user);
        if ($this->extrafields) {
            $displayfields = array();
            foreach ($this->extrafields as $field) {
                $displayfields[] = $user->{$field};
            }
            $out .= ' (' . implode(', ', $displayfields) . ')';
        }
        return $out;
    }
}