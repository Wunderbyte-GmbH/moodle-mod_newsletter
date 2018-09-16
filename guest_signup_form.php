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


class mod_newsletter_guest_signup_form extends moodleform {

    /**
     * Defines forms elements
     */
    public function definition() {
        $mform = &$this->_form;
        $data = &$this->_customdata;

        $mform->addElement('hidden', 'id', $data['id']);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', NEWSLETTER_PARAM_ACTION, $data[NEWSLETTER_PARAM_ACTION]);
        $mform->setType(NEWSLETTER_PARAM_ACTION, PARAM_ALPHANUM);

        $mform->addElement('header', 'subscribe', get_string('guestsubscribe', 'mod_newsletter'));

        $mform->addElement('text', 'firstname', get_string('firstname'), array('size' => '64'));
        $mform->setType('firstname', PARAM_TEXT);
        $mform->addRule('firstname', null, 'required', null, 'client');
        $mform->addRule('firstname', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('text', 'lastname', get_string('lastname'), array('size' => '64'));
        $mform->setType('lastname', PARAM_TEXT);
        $mform->addRule('lastname', null, 'required', null, 'client');
        $mform->addRule('lastname', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('text', 'email', get_string('email'), array('size' => '64'));
        $mform->setType('email', PARAM_EMAIL);
        $mform->addRule('email', null, 'required', null, 'client');
        $mform->addRule('email', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->add_action_buttons(true, get_string('subscribe', 'mod_newsletter'));
    }

    public function definition_after_data() {
        $mform = $this->_form;
        $mform->applyFilter('firstname', 'trim');
        $mform->applyFilter('lastname', 'trim');
        $mform->applyFilter('email', 'trim');
    }

    public function validation($usernew, $files) {
        global $CFG, $DB;
        $err = array();
        $usernew = (object) $usernew;

        $user = $DB->get_record('user', array('id' => $usernew->id));
        if (!$user or $user->email !== $usernew->email) {
            if (!validate_email($usernew->email)) {
                $err['email'] = get_string('invalidemail');
            } else if ($DB->record_exists('user',
                    array('email' => $usernew->email, 'mnethostid' => $CFG->mnet_localhost_id))) {
                $a = get_string('forgotten');
                $err['email'] = get_string('emailexists', 'mod_newsletter', $a);
            }
        }

        // Next the customisable profile fields.
        $err += profile_validation($usernew, $files);
        return $err;
    }
}
