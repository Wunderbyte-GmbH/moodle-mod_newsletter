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
 * Internal library of functions for module newsletter
 *
 * @package    mod_newsletter
 * @copyright  2013 Ivan Šakić <ivan.sakic3@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Renderable page header
 * @package   mod_newsletter
 * @copyright 2013 Ivan Šakić <ivan.sakic3@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class newsletter_header implements renderable {
    /** @var stdClass the newsletter record  */
    var $newsletter = null;
    /** @var mixed context|null the context record  */
    var $context = null;
    /** @var bool $showintro - show or hide the intro */
    var $showintro = false;
    /** @var int coursemoduleid - The course module id */
    var $coursemoduleid = 0;

    /**
     * Constructor
     *
     * @param stdClass $newsletter - the newsletter database record
     * @param mixed $context context|null - the course module context (or the course context if the coursemodule has not been created yet)
     * @param bool $showintro - show or hide the intro
     * @param int $coursemoduleid - the course module id
     */
    public function __construct(stdClass $newsletter, $context, $showintro, $coursemoduleid) {
        $this->newsletter = $newsletter;
        $this->context = $context;
        $this->showintro = $showintro;
        $this->coursemoduleid = $coursemoduleid;
    }
}

/**
 * Renderable moodleform
 * @package   mod_newsletter
 * @copyright 2013 Ivan Šakić <ivan.sakic3@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class newsletter_form implements renderable {
    /** @var moodleform $form is the edit submission form */
    var $form = null;
    /** @var string $title is the title to be displayed in the header */
    var $title = '';
    /** @var string $classname is the name of the class to assign to the container */
    var $classname = '';
    /** @var string $jsinitfunction is an optional js function to add to the page requires */
    var $jsinitfunction = '';

    /**
     * Constructor
     * @param moodleform $form This is the moodleform
     * @param string $classname This is the class name for the container div
     * @param string $jsinitfunction This is an optional js function to add to the page requires
     */
    public function __construct(moodleform $form, $title = '', $classname = '', $jsinitfunction = '') {
        $this->form = $form;
        $this->title = $title;
        $this->classname = $classname;
        $this->jsinitfunction = $jsinitfunction;
    }
}

class newsletter_issue implements renderable {

    var $id;
    var $cmid;
    var $newsletterid;
    var $title;
    var $plaincontent;
    var $htmlcontent;
    var $publishon;
    var $numsubscriptions;
    var $numdelivered;

    /**
     * Constructor
     */
    public function __construct(stdClass $issue) {
        $this->id = $issue->id;
        $this->cmid = $issue->cmid;
        $this->newsletterid = $issue->newsletterid;
        $this->title = $issue->title;
        $this->publishon = $issue->publishon;
        $this->plaincontent = $issue->plaincontent;
        $this->htmlcontent = $issue->htmlcontent;
        $this->numsubscriptions = isset($issue->numsubscriptions) ? $issue->numsubscriptions: 0;
        $this->numdelivered = isset($issue->numdelivered) ? $issue->numdelivered: 0;
    }
}

class newsletter_issue_summary extends newsletter_issue {
}

class newsletter_issue_summary_list implements renderable {

    var $issues = array();

    /**
     * Constructor
     */
    public function __construct(array $issues = array()) {
        $this->issues = $issues;
    }

    public function add_issue_summary(newsletter_issue_summary $issue) {
        $this->issues[] = $issue;
    }
}

class newsletter_section_list implements renderable {

    var $heading = '';
    var $sections = array();

    /**
     * Constructor
     */
    public function __construct($heading, array $sections = array()) {
        $this->heading = $heading;
        $this->sections = $sections;
    }

    public function add_issue_section(newsletter_section $section) {
        $this->sections[] = $section;
    }
}

class newsletter_section implements renderable {

    var $heading = '';
    var $summary_list = array();

    /**
     * Constructor
     */
    public function __construct($heading, newsletter_issue_summary_list $summary_list) {
        $this->heading = $heading;
        $this->summary_list = $summary_list;
    }
}

class newsletter_navigation_bar implements renderable {
    var $firstissue;
    var $previousissue;
    var $currentissue;
    var $nextissue;
    var $lastissue;

    public function __construct(stdClass $currentissue,
                                stdClass $firstissue = null,
                                stdClass $previousissue = null,
                                stdClass $nextissue = null,
                                stdClass $lastissue = null) {
        $this->currentissue = $currentissue;
        $this->firstissue = $firstissue;
        $this->previousissue = $previousissue;
        $this->nextissue = $nextissue;
        $this->lastissue = $lastissue;
    }
}

class newsletter_main_toolbar implements renderable {
    var $cmid;
    var $groupby;

    public function __construct($cmid, $groupby) {
        $this->cmid = $cmid;
        $this->groupby = $groupby;
    }
}

class newsletter_progressbar implements renderable {
    var $total;
    var $completed;

    public function __construct($total, $completed) {
        $this->total = $total;
        $this->completed = $completed;
    }
}

class newsletter_action_button implements renderable {
    var $cmid;
    var $issueid;
    var $action;
    var $label;

    public function __construct($cmid, $issueid, $action, $label) {
        $this->cmid = $cmid;
        $this->issueid = $issueid;
        $this->action = $action;
        $this->label = $label;
    }
}