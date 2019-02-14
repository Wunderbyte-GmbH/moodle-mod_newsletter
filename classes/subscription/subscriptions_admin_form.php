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
 * @copyright  2015 onwards David Bogner <info@edulabs.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_newsletter\subscription;
use context_course;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/cohort/lib.php');


/**
 * Form for subscribing and unsubscribing cohorts to a newsletter
 */
class mod_newsletter_subscriptions_admin_form extends \moodleform {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = &$this->_form;
        $data = &$this->_customdata;

        $mform->addElement('hidden', 'id', $data['id']);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'action', NEWSLETTER_ACTION_MANAGE_SUBSCRIPTIONS);
        $mform->setType('action', PARAM_ALPHA);

        $mform->addElement('header', 'cohort_management',
                get_string('cohortmanagement', 'mod_newsletter'));
        $mform->setExpanded('cohort_management', false);

        if (isset($CFG->branch) && $CFG->branch < 28) {
            // This is valid before v2.8.
            $cohorts = cohort_get_visible_list($data['course']);
        } else {
            // This is valid after v2.8.
            $coursecontext = context_course::instance($data['course']->id);
            $options = cohort_get_available_cohorts($coursecontext,
                    COHORT_WITH_ENROLLED_MEMBERS_ONLY);
            foreach ($options as $opionobj) {
                $cohorts[$opionobj->id] = $opionobj->name . " (" . $opionobj->memberscnt . ")";
            }
        }

        if (isset($cohorts)) {
            $cohorts = $mform->addElement('select', 'cohorts',
                    get_string('cohortsavailable', 'mod_newsletter'), $cohorts);
            $cohorts->setMultiple(true);
        }

        $buttonarray = array();
        $buttonarray[] = & $mform->createElement('submit', 'subscribe', "Subscribe");
        $buttonarray[] = & $mform->createElement('submit', 'unsubscribe', "Unsubscribe");
        $mform->addGroup($buttonarray, 'cohorts_submit', '', array(' '), false);
    }
}
