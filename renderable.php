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
 * @copyright  2015 onwards David Bogner <info@edulabs.org>
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
    /** @public stdClass the newsletter record  */
    public $newsletter = null;
    /** @public mixed context|null the context record  */
    public $context = null;
    /** @public bool $showintro - show or hide the intro */
    public $showintro = false;
    /** @public int coursemoduleid - The course module id */
    public $coursemoduleid = 0;

    /**
     * Constructor
     *
     * @param stdClass $newsletter - the newsletter database record
     * @param mixed $context context|null - the course module context
     * (or the course context if the coursemodule has not been created yet)
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
    /** @public moodleform $form is the edit submission form */
    public $form = null;
    /** @public string $title is the title to be displayed in the header */
    public $title = '';
    /** @public string $classname is the name of the class to assign to the container */
    public $classname = '';
    /** @public string $jsinitfunction is an optional js function to add to the page requires */
    public $jsinitfunction = '';

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

    public $id;
    public $cmid;
    public $newsletterid;
    public $title;
    public $htmlcontent;
    public $publishon;
    public $numsubscriptions;
    public $numdelivered;
    public $numnotyetdelivered;

    /**
     * Constructor
     */
    public function __construct(stdClass $issue) {
        $this->id = $issue->id;
        $this->cmid = $issue->cmid;
        $this->newsletterid = $issue->newsletterid;
        $this->title = $issue->title;
        $this->publishon = $issue->publishon;
        $this->htmlcontent = $issue->htmlcontent;
        $this->numsubscriptions = isset($issue->numsubscriptions) ? $issue->numsubscriptions : 0;
        $this->numdelivered = isset($issue->numdelivered) ? $issue->numdelivered : 0;
        $this->numnotyetdelivered = isset($issue->numnotyetdelivered) ? $issue->numnotyetdelivered : $this->numsubscriptions;
    }
}

class newsletter_issue_summary extends newsletter_issue {
    public $editissue;
    public $deleteissue;

    public function __construct(stdClass $issue, $editissue = false, $deleteissue = false) {
        parent::__construct($issue);
        $this->editissue = $editissue;
        $this->deleteissue = $deleteissue;
    }
}

class newsletter_subscription_list implements renderable {
    public $cmid;
    public $subscriptions;
    public $columns;
    public function __construct($cmid, array $subscriptions, array $columns) {
        $this->cmid = $cmid;
        $this->subscriptions = $subscriptions;
        $this->columns = $columns;
    }
}

class newsletter_issue_summary_list implements renderable {

    public $issues = array();

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

    public $heading = '';
    public $sections = array();

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

    public $heading = '';
    public $summarylist = array();

    /**
     * Constructor
     */
    public function __construct($heading, newsletter_issue_summary_list $summarylist) {
        $this->heading = $heading;
        $this->summarylist = $summarylist;
    }
}

class newsletter_navigation_bar implements renderable {
    public $firstissue;
    public $previousissue;
    public $currentissue;
    public $nextissue;
    public $lastissue;

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

class newsletter_pager implements renderable {
    public $url;
    public $from;
    public $count;
    public $pages;
    public $totalentries;
    public $totalfiltered;

    public function __construct(moodle_url $url, $from, $count, array $pages, $totalentries, $totalfiltered) {
        $this->url = $url;
        $this->from = $from;
        $this->count = $count;
        $this->pages = $pages;
        $this->totalentries = $totalentries;
        $this->totalfiltered = $totalfiltered;
    }
}

class newsletter_main_toolbar implements renderable {
    public $cmid;
    public $groupby;
    public $createissues;
    public $managesubs;

    public function __construct($cmid, $groupby, $createissues = false, $managesubs = false) {
        $this->cmid = $cmid;
        $this->groupby = $groupby;
        $this->createissues = $createissues;
        $this->managesubs = $managesubs;
    }
}

class newsletter_progressbar implements renderable {
    public $tocomplete;
    public $completed;

    public function __construct($tocomplete, $completed) {
        $this->tocomplete = $tocomplete;
        $this->completed = $completed;
    }
}

class newsletter_attachment_list implements renderable {
    public $files;

    public function __construct(array $files) {
        $this->files = $files;
    }
}

class newsletter_publish_countdown implements renderable {
    public $now;
    public $until;

    public function __construct($now, $until) {
        $this->now = $now;
        $this->until = $until;
    }
}

class newsletter_action_button implements renderable {
    public $cmid;
    public $issueid;
    public $action;
    public $label;

    public function __construct($cmid, $issueid, $action, $label) {
        $this->cmid = $cmid;
        $this->issueid = $issueid;
        $this->action = $action;
        $this->label = $label;
    }
}

class newsletter_action_link implements renderable {
    public $url;
    public $text;
    public $class;

    public function __construct(moodle_url $url, $text = '', $class = 'mod_newsletter__action-link') {
        $this->url = $url;
        $this->text = $text;
        $this->class = $class;
    }
}