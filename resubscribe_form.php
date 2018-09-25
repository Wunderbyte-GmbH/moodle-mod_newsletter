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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/mod/newsletter/lib.php');


class mod_newsletter_resubscribe_form extends moodleform {

    /**
     * Defines forms elements
     */
    public function definition() {
        $mform = &$this->_form;
        $data = &$this->_customdata;

        $mform->addElement('hidden', 'id', $data['id']);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'resubscribe_confirmation');
        $mform->setType('resubscribe_confirmation', PARAM_INT);
        $mform->setDefault('resubscribe_confirmation', '1');

        $mform->addElement('hidden', NEWSLETTER_PARAM_ACTION, $data[NEWSLETTER_PARAM_ACTION]);
        $mform->setType(NEWSLETTER_PARAM_ACTION, PARAM_ALPHANUM);

        $mform->addElement('header', 'resubscribe_header',
                get_string('resubscribe', 'mod_newsletter'));

        $mform->addElement('html',
                '<p><br />' . get_string('resubscribe_text', 'mod_newsletter') . '</p>');

        $this->add_action_buttons(true, get_string('resubscribe_btn', 'mod_newsletter'));
    }

    public function definition_after_data() {
        return true;
    }

    public function validation($usernew, $files) {
        return true;
    }
}
