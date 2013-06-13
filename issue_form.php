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
 * @copyright  2013 Ivan Šakić <ivan.sakic3@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

class mod_newsletter_issue_form extends moodleform {

    /**
     * Defines forms elements
     */
    public function definition() {

        $mform = &$this->_form;
        $data = &$this->_customdata;

        $newsletter = $data['newsletter'];
        $issue = $data['issue'];

        $mform->addElement('hidden', 'id', $newsletter->get_course_module()->id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'issue', $issue ? $issue->id : 0);
        $mform->setType('issue', PARAM_INT);
        $mform->addElement('hidden', 'action', NEWSLETTER_ACTION_EDIT_ISSUE);
        $mform->setType('action', PARAM_ACTION);

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'title', get_string('issue_title', 'newsletter'), array('size' => '64'));
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');
        $mform->addRule('title', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('title', 'issue_title', 'newsletter');

        $mform->addElement('header', 'general', get_string('header_content', 'newsletter'));

        $mform->addElement('editor', 'htmlcontent', get_string('issue_htmlcontent', 'newsletter'));
        $mform->setType('htmlcontent', PARAM_RAW);

        $fs = get_file_storage();
        $context = $newsletter->get_context();
        $files = $fs->get_area_files($context->id, 'mod_newsletter', NEWSLETTER_FILE_AREA_STYLESHEETS, $newsletter->get_instance()->id, 'filename', false);
        $options = array();
        $options[NEWSLETTER_DEFAULT_STYLESHEET] = get_string('default_stylesheet', 'newsletter');
        foreach ($files as $file) {
            $options[$file->get_id()] = $file->get_filename();
        }

        $mform->addElement('select', 'stylesheetid', get_string('issue_stylesheet', 'newsletter'), $options);
        $mform->setType('stylesheetid', PARAM_INT);

        $mform->addElement('filemanager', 'attachments', get_string('attachments', 'newsletter'),
                            array('subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 0));
        $mform->addHelpButton('attachments', 'attachments', 'newsletter');

        $mform->addElement('header', 'general', get_string('header_publish', 'newsletter'));

        $mform->addElement('date_time_selector', 'publishon', get_string('publishon', 'newsletter'));
        $mform->setType('plaincontent', PARAM_INT);
        $mform->setDefault('publishon', strtotime("+24 hours"));

        $this->add_action_buttons();

        if ($issue) {
            $values = new stdClass();
            $values->title = $issue->title;
            $values->htmlcontent = array('format' => 1, 'text' => $issue->htmlcontent);
            $values->publishon = $issue->publishon;
            $values->stylesheetid = $issue->stylesheetid;
            $draftitemid = file_get_submitted_draft_itemid(NEWSLETTER_FILE_AREA_ATTACHMENTS);
            file_prepare_draft_area($draftitemid, $context->id, 'mod_newsletter', NEWSLETTER_FILE_AREA_ATTACHMENTS, $issue->id,
                                             array('subdirs' => NEWSLETTER_FILE_OPTIONS_SUBDIRS, 'maxbytes' => 0, 'maxfiles' => -1));
            $values->attachments = $draftitemid;
            $this->set_data($values);
        }
    }
}
