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
 * The form for guest user signups
 *
 * @package    mod_newsletter
 * @copyright  2013 Ivan Šakić <ivan.sakic3@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/cohort/lib.php');

/**
 * Form for subscribing and unsubscribing cohorts to a newsletter
 * 
 */
class mod_newsletter_subscriptions_admin_form extends moodleform {
   /**
     * Defines forms elements
     */
    public function definition() {

        $mform = &$this->_form;
        $data = &$this->_customdata;

        $mform->addElement('hidden', 'id', $data['id']);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'action', NEWSLETTER_ACTION_MANAGE_SUBSCRIPTIONS);
        $mform->setType('action', PARAM_ALPHA);

        $mform->addElement('header', 'cohort_management', "Cohort management");

        $options = cohort_get_visible_list($data['course']);

        $cohorts = $mform->addElement('select', 'cohorts', "Available cohorts", $options);
        $cohorts->setMultiple(true);

        $buttonarray=array();
        $buttonarray[] =& $mform->createElement('submit', 'subscribe', "Subscribe");
        $buttonarray[] =& $mform->createElement('submit', 'unsubscribe', "Unsubscribe");
        $mform->addGroup($buttonarray, 'cohorts_submit', '', array(' '), false);
    }
}
