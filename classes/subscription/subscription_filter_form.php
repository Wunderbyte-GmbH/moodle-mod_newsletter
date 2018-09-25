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
 * The form for creating and editing newsletter issues
 *
 * @package    mod_newsletter
 * @copyright  2015 David Bogner <info@edulabs.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_newsletter\subscription;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

class mod_newsletter_subscription_filter_form extends \moodleform {

    /**
     * Defines forms elements
     */
    public function definition() {
        $mform = &$this->_form;
        $data = &$this->_customdata;

        $newsletter = $data['newsletter'];

        $mform->addElement('hidden', 'id', $newsletter->get_course_module()->id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'action', NEWSLETTER_ACTION_MANAGE_SUBSCRIPTIONS);
        $mform->setType('action', PARAM_ALPHA);

        // Search for a specific user name.
        $mform->addElement('text', 'search', get_string('search'));
        $mform->setType('search', PARAM_RAW);

        // Filter by subscription status.
        $options = array(10 => get_string('all'));
        $options += (array) $newsletter->get_subscription_statuslist();
        $mform->addElement('select', 'status', get_string('header_health', 'mod_newsletter'),
                $options);
        $mform->setType('status', PARAM_INT);
        $mform->setDefault('status', 10);

        $mform->addElement('select', 'count', get_string('entries_per_page', 'mod_newsletter'),
                array(10 => 10, 20 => 20, 50 => 50, 100 => 100, 200 => 200, 500 => 500, 1000 => 1000));
        $mform->setType('count', PARAM_INT);
        $mform->setDefault('count', 50);

        $mform->addElement('select', 'orderby', get_string('sortby'),
                array('lastname' => get_string('lastname'), 'firstname' => get_string('firstname'),
                    'email' => get_string('email')));

        // Submit button does not use add_action_buttons because that adds
        // another fieldset which causes the CSS style to break in an unfixable
        // way due to fieldset quirks.
        $group = array();
        $group[] = $mform->createElement('submit', 'submitbutton', get_string('filter'));
        $group[] = $mform->createElement('submit', 'resetbutton', get_string('reset'));
        $mform->addGroup($group, 'buttons', '', ' ', false);
    }
}
