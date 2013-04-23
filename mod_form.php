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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form
 */
class mod_newsletter_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {

        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('newslettername', 'newsletter'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'newslettername', 'newsletter');

        $this->add_intro_editor();

        $options = array();
        $options[NEWSLETTER_CONTENT_PLAINTEXT_ONLY] = get_string('content_plaintext_only', 'newsletter');
        $options[NEWSLETTER_CONTENT_HTML_ONLY] = get_string('content_html_only', 'newsletter');
        $options[NEWSLETTER_CONTENT_ALL] = get_string('content_all', 'newsletter');

        $mform->addElement('select', 'allowedcontent', get_string('allowedcontent', 'newsletter'), $options);

        $mform->addElement('filemanager', 'stylesheets', get_string('stylesheets', 'newsletter'),
                            array('subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 0, 'accepted_types' => array('css')));
        $mform->addHelpButton('stylesheets', 'stylesheets', 'newsletter');

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();

        $draftitemid = file_get_submitted_draft_itemid(NEWSLETTER_FILE_AREA_STYLESHEETS);
        file_prepare_draft_area($draftitemid, $this->context->id, 'mod_newsletter', NEWSLETTER_FILE_AREA_STYLESHEETS, $this->current->id,
                                             array('subdirs' => NEWSLETTER_FILE_OPTIONS_SUBDIRS, 'maxbytes' => 0, 'maxfiles' => -1));
        $entry = new stdClass();
        $entry->stylesheets = $draftitemid;

        parent::set_data($entry);
    }
}
