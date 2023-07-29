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
 * The main newsletter configuration form
 *
 * @package    mod_newsletter
 * @copyright  2013 Ivan Šakić <ivan.sakic3@gmail.com>
 * @copyright  2015 onwards David Bogner <info@edulabs.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_newsletter\userfilter;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->libdir . '/formslib.php');

/**
 * Module instance settings form
 */
class mod_newsletter_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;
        $mform = &$this->_form;
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('newslettername', 'mod_newsletter'),
                array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'newslettername', 'mod_newsletter');
        $this->standard_intro_elements();

        $mform->addElement('select', 'subscriptionmode',
                get_string('subscription_mode', 'mod_newsletter'),
                $this->make_subscription_option_list());
        $mform->addHelpButton('subscriptionmode', 'subscription_mode', 'mod_newsletter');

        $mform->addElement('filemanager', 'stylesheets', get_string('stylesheets', 'mod_newsletter'),
                array('subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 0, 'accepted_types' => 'css'));
        $mform->addHelpButton('stylesheets', 'stylesheets', 'mod_newsletter');

        $mform->addElement('advcheckbox', 'allowguestusersubscriptions',
                get_string('allowguestusersubscriptions', 'mod_newsletter'));
        $mform->addHelpButton('allowguestusersubscriptions', 'allowguestusersubscriptions',
                'mod_newsletter');
        $mform->setDefault('allowguestusersubscriptions', 1);

        $mform->addElement('textarea', 'welcomemessage',
                get_string('welcomemessage', 'mod_newsletter'), 'wrap="virtual" rows="8" cols="50"');
        $mform->addHelpButton('welcomemessage', 'welcomemessage', 'mod_newsletter');

        $mform->addElement('header', 'header_profilefield',
                get_string('header_profilefield', 'mod_newsletter'));
        $mform->addElement('select', 'aboprofilefield',
                get_string('aboprofilefield', 'mod_newsletter'), $this->cpf_list());
        $mform->addHelpButton('aboprofilefield', 'aboprofilefield', 'mod_newsletter');

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();

        $newsletterid = empty($this->current->id) ? null : $this->current->id;

        $draftitemid = file_get_submitted_draft_itemid(NEWSLETTER_FILE_AREA_STYLESHEET);
        file_prepare_draft_area($draftitemid, $this->context->id, 'mod_newsletter',
                NEWSLETTER_FILE_AREA_STYLESHEET, $newsletterid,
                array('subdirs' => NEWSLETTER_FILE_OPTIONS_SUBDIRS, 'maxbytes' => 0,
                        'maxfiles' => -1));
        $entry = new stdClass();
        $entry->stylesheets = $draftitemid;

        parent::set_data($entry);
    }

    /**
     * Get custom profile fields as array.
     * @return array
     */
    public function cpf_list(): array {
        global $DB;
        $userprofilefieldsarray = [];
        $customuserprofilefields = $DB->get_records('user_info_field', ['datatype' => 'checkbox'], '', 'id, name, shortname');

        if (!empty($customuserprofilefields)) {
            // Create an array of key => value pairs for the dropdown.
            foreach ($customuserprofilefields as $cpf) {
                $userprofilefieldsarray[$cpf->id] = $cpf->name;
            }
        }
        $userprofilefieldsarray[0] = '';
        asort($userprofilefieldsarray);

        return $userprofilefieldsarray;
    }

    /**
     * Return subscription modes.
     * @return array
     */
    public function make_subscription_option_list() {
        $options = array();
        $options[NEWSLETTER_SUBSCRIPTION_MODE_OPT_IN] = get_string('sub_mode_opt_in',
                'mod_newsletter');
        $options[NEWSLETTER_SUBSCRIPTION_MODE_OPT_OUT] = get_string('sub_mode_opt_out',
                'mod_newsletter');
        $options[NEWSLETTER_SUBSCRIPTION_MODE_FORCED] = get_string('sub_mode_forced',
                'mod_newsletter');
        return $options;
    }

    /**
     * Returns cohort name and id as key.
     * @return array
     */
    public function make_cohort_option_list() {
        $cohorts = cohort_get_cohorts($this->context->id);
        $options = array();
        foreach ($cohorts as $cohortid => $cohort) {
            $options[$cohortid] = $cohort->name;
        }
        return $options;
    }
}
