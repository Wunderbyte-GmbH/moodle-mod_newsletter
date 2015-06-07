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
require_once($CFG->dirroot . '/repository/lib.php');

class mod_newsletter_issue_form extends moodleform {

	/**
	 * Returns the options array to use in filemanager for newsletter attachments
	 *
	 * @param stdClass $newsletter
	 * @return array
	 */
	public static function attachment_options($newsletter) {
		global $COURSE, $PAGE, $CFG;
		$maxbytes = get_user_max_upload_file_size($PAGE->context, $CFG->maxbytes, $COURSE->maxbytes);
		return array(
				'subdirs' => 0,
				'maxbytes' => $maxbytes,
				'maxfiles' => 10,
				'accepted_types' => '*',
				'return_types' => FILE_INTERNAL
		);
	}
	
	/**
	 * Returns the options array to use in newsletter text editor
	 *
	 * @param context_module $context
	 * @param int $issued id of newsletter issue, use null when adding new issue
	 * @return array
	 */
	public static function editor_options(context_module $context, $issueid) {
		global $COURSE, $PAGE, $CFG;
		$maxbytes = get_user_max_upload_file_size($PAGE->context, $CFG->maxbytes, $COURSE->maxbytes);
		return array(
				'maxfiles' => EDITOR_UNLIMITED_FILES,
				'maxbytes' => $maxbytes,
				'trusttext'=> true,
				'return_types'=> FILE_INTERNAL | FILE_EXTERNAL,
				'subdirs' => file_area_contains_subdirs($context, 'mod_newsletter', NEWSLETTER_FILE_AREA_ISSUE, $issueid)
		);
	}
	
    /**
     * Defines forms elements
     */
    public function definition() {

        $mform = &$this->_form;
        $data = &$this->_customdata;

        $newsletter = $data['newsletter'];
        $issue = $data['issue'];
        $context = $data['context'];

        $mform->addElement('hidden', 'id', $newsletter->get_course_module()->id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'issue', $issue ? $issue->id : 0);
        $mform->setType('issue', PARAM_INT);
        $mform->addElement('hidden', 'action', NEWSLETTER_ACTION_EDIT_ISSUE);
        $mform->setType('action', PARAM_ALPHA);

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'title', get_string('issue_title', 'newsletter'), array('size' => '64'));
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');
        $mform->addRule('title', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('title', 'issue_title', 'newsletter');

        $mform->addElement('header', 'general', get_string('header_content', 'newsletter'));

        $mform->addElement('editor', 'htmlcontent', get_string('issue_htmlcontent', 'newsletter'),null,self::editor_options($context, (empty($issue->id) ? null : $issue->id)));
        $mform->setType('htmlcontent', PARAM_RAW);
        $mform->addRule('htmlcontent', get_string('required'), 'required', null, 'client');
        
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_newsletter', NEWSLETTER_FILE_AREA_STYLESHEET, $newsletter->get_instance()->id, 'filename', false);
        $options = array();
        $options[NEWSLETTER_DEFAULT_STYLESHEET] = get_string('default_stylesheet', 'newsletter');
        foreach ($files as $file) {
            $options[$file->get_id()] = $file->get_filename();
        }

        $mform->addElement('select', 'stylesheetid', get_string('issue_stylesheet', 'newsletter'), $options);
        $mform->setType('stylesheetid', PARAM_INT);

        $mform->addElement('filemanager', 'attachments', get_string('attachments', 'newsletter'), null, self::attachment_options($newsletter));
        $mform->addHelpButton('attachments', 'attachments', 'newsletter');

        $mform->addElement('header', 'general', get_string('header_publish', 'newsletter'));

        $mform->addElement('date_time_selector', 'publishon', get_string('publishon', 'newsletter'));
        $mform->setType('plaincontent', PARAM_INT);
        $mform->setDefault('publishon', strtotime("+24 hours"));

        $this->add_action_buttons(false);
    }
    
    /**
     * Form validation
     *
     * @param array $data data from the newsletter.
     * @param array $files files uploaded.
     * @return array of errors.
     */
    function validation($data, $files) {
    	$errors = parent::validation($data, $files);
    	if (empty($data['htmlcontent']['text'])) {
    		$errors['htmlcontent'] = get_string('erroremptymessage', 'forum');
    	}
    	if (empty($data['title'])) {
    		$errors['title'] = get_string('erroremptysubject', 'forum');
    	}
    	return $errors;
    }
}
