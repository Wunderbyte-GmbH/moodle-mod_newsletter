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

// This file predates the autoloader and declares every renderable of the module. Splitting it would
// mean renaming all of these global classes, which the renderer and the templates depend on.
// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

/**
 * Renderable page header
 *
 * @package   mod_newsletter
 * @copyright 2013 Ivan Sakic <ivan.sakic3@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class newsletter_header implements renderable {
    /** @var stdClass the newsletter record */
    public $newsletter = null;
    /** @var context|null the course module context */
    public $context = null;
    /** @var bool show or hide the intro */
    public $showintro = false;
    /** @var int the course module id */
    public $coursemoduleid = 0;
    /** @var bool render with or without page header */
    public $embed = false;

    /**
     * Constructor
     *
     * @param stdClass $newsletter - the newsletter database record
     * @param mixed $context context|null - the course module context
     * (or the course context if the coursemodule has not been created yet)
     * @param bool $showintro - show or hide the intro
     * @param int $coursemoduleid - the course module id
     * @param bool $embed - render with or without page header
     */
    public function __construct(
        stdClass $newsletter,
        context $context,
        bool $showintro,
        int $coursemoduleid,
        bool $embed = false
    ) {
        $this->newsletter = $newsletter;
        $this->context = $context;
        $this->showintro = $showintro;
        $this->coursemoduleid = $coursemoduleid;
        $this->embed = $embed;
    }
}

/**
 * Renderable moodleform
 *
 * @package   mod_newsletter
 * @copyright 2013 Ivan Sakic <ivan.sakic3@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class newsletter_form implements renderable {
    /** @var moodleform the edit submission form */
    public $form = null;
    /** @var string the title to be displayed in the header */
    public $title = '';
    /** @var string the name of the class to assign to the container */
    public $classname = '';
    /** @var string an optional js function to add to the page requires */
    public $jsinitfunction = '';

    /**
     * Constructor
     *
     * @param moodleform $form This is the moodleform
     * @param string $title This is the title displayed in the header
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

/**
 * Data for rendering newsletter issue.
 *
 * @package   mod_newsletter
 * @copyright 2013 Ivan Sakic <ivan.sakic3@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class newsletter_issue implements renderable {
    /** @var int the issue id */
    public $id;
    /** @var int the course module id */
    public $cmid;
    /** @var int the newsletter instance id */
    public $newsletterid;
    /** @var string the issue title */
    public $title;
    /** @var string the issue body */
    public $htmlcontent;
    /** @var int timestamp the issue is published on */
    public $publishon;
    /** @var int number of subscriptions the issue goes out to */
    public $numsubscriptions;
    /** @var int number of copies already delivered */
    public $numdelivered;
    /** @var int number of copies still to be delivered */
    public $numnotyetdelivered;

    /**
     * Constructor
     *
     * @param stdClass $issue the issue record
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
        $this->numnotyetdelivered = isset($issue->numnotyetdelivered) ? $issue->numnotyetdelivered
            : $this->numsubscriptions;
    }
}

/**
 * Data for rendering a newsletter issue in a list, along with the actions allowed on it.
 *
 * @package   mod_newsletter
 * @copyright 2013 Ivan Sakic <ivan.sakic3@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class newsletter_issue_summary extends newsletter_issue {
    /** @var bool whether the issue may be edited */
    public $editissue;
    /** @var bool whether the issue may be deleted */
    public $deleteissue;
    /** @var bool whether the issue may be duplicated */
    public $duplicateissue;

    /**
     * Constructor
     *
     * @param stdClass $issue the issue record
     * @param bool $editissue whether the issue may be edited
     * @param bool $deleteissue whether the issue may be deleted
     * @param bool $duplicateissue whether the issue may be duplicated
     */
    public function __construct(
        stdClass $issue,
        $editissue = false,
        $deleteissue = false,
        $duplicateissue = false
    ) {
        parent::__construct($issue);
        $this->editissue = $editissue;
        $this->deleteissue = $deleteissue;
        $this->duplicateissue = $duplicateissue;
    }
}

/**
 * Data for rendering the subscription management table.
 *
 * @package   mod_newsletter
 * @copyright 2013 Ivan Sakic <ivan.sakic3@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class newsletter_subscription_list implements renderable {
    /** @var int the course module id */
    public int $cmid;
    /** @var array the subscription records to show */
    public array $subscriptions;
    /** @var array the columns to render */
    public array $columns;

    /**
     * Constructor
     *
     * @param int $cmid the course module id
     * @param array $subscriptions the subscription records to show
     * @param array $columns the columns to render
     */
    public function __construct($cmid, array $subscriptions, array $columns) {
        $this->cmid = $cmid;
        $this->subscriptions = $subscriptions;
        $this->columns = $columns;
    }
}

/**
 * A list of issue summaries.
 *
 * @package   mod_newsletter
 * @copyright 2013 Ivan Sakic <ivan.sakic3@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class newsletter_issue_summary_list implements renderable {
    /** @var newsletter_issue_summary[] the issue summaries in the list */
    public $issues = [];

    /**
     * Constructor
     *
     * @param array $issues the issue summaries in the list
     */
    public function __construct(array $issues = []) {
        $this->issues = $issues;
    }

    /**
     * Append an issue summary to the list.
     *
     * @param newsletter_issue_summary $issue the summary to append
     * @return void
     */
    public function add_issue_summary(newsletter_issue_summary $issue) {
        $this->issues[] = $issue;
    }
}

/**
 * A list of issue sections, grouped by day, week, month or year.
 *
 * @package   mod_newsletter
 * @copyright 2013 Ivan Sakic <ivan.sakic3@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class newsletter_section_list implements renderable {
    /** @var string the heading of the list */
    public string $heading = '';
    /** @var newsletter_section[] the sections in the list */
    public array $sections = [];

    /**
     * Constructor
     *
     * @param string $heading the heading of the list
     * @param array $sections the sections in the list
     */
    public function __construct($heading, array $sections = []) {
        $this->heading = $heading;
        $this->sections = $sections;
    }

    /**
     * Append a section to the list.
     *
     * @param newsletter_section $section the section to append
     * @return void
     */
    public function add_issue_section(newsletter_section $section) {
        $this->sections[] = $section;
    }
}

/**
 * One section of grouped issue summaries.
 *
 * @package   mod_newsletter
 * @copyright 2013 Ivan Sakic <ivan.sakic3@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class newsletter_section implements renderable {
    /** @var string the heading of the section */
    public $heading = '';
    /** @var newsletter_issue_summary_list the summaries in the section */
    public $summarylist = [];

    /**
     * Constructor
     *
     * @param string $heading the heading of the section
     * @param newsletter_issue_summary_list $summarylist the summaries in the section
     */
    public function __construct($heading, newsletter_issue_summary_list $summarylist) {
        $this->heading = $heading;
        $this->summarylist = $summarylist;
    }
}

/**
 * First, previous, next and last links shown while reading an issue.
 *
 * @package   mod_newsletter
 * @copyright 2013 Ivan Sakic <ivan.sakic3@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class newsletter_navigation_bar implements renderable {
    /** @var stdClass|null the first issue */
    public $firstissue;
    /** @var stdClass|null the issue before the current one */
    public $previousissue;
    /** @var stdClass the issue being read */
    public $currentissue;
    /** @var stdClass|null the issue after the current one */
    public $nextissue;
    /** @var stdClass|null the last issue */
    public $lastissue;

    /**
     * Constructor
     *
     * @param stdClass $currentissue the issue being read
     * @param stdClass|null $firstissue the first issue
     * @param stdClass|null $previousissue the issue before the current one
     * @param stdClass|null $nextissue the issue after the current one
     * @param stdClass|null $lastissue the last issue
     */
    public function __construct(
        stdClass $currentissue,
        stdClass $firstissue = null,
        stdClass $previousissue = null,
        stdClass $nextissue = null,
        stdClass $lastissue = null
    ) {
        $this->currentissue = $currentissue;
        $this->firstissue = $firstissue;
        $this->previousissue = $previousissue;
        $this->nextissue = $nextissue;
        $this->lastissue = $lastissue;
    }
}

/**
 * Paging controls for the issue list.
 *
 * @package   mod_newsletter
 * @copyright 2013 Ivan Sakic <ivan.sakic3@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class newsletter_pager implements renderable {
    /** @var moodle_url the base url of the pager links */
    public $url;
    /** @var int index of the first entry on the page */
    public $from;
    /** @var int number of entries per page */
    public $count;
    /** @var int number of pages */
    public $pages;
    /** @var int total number of entries */
    public $totalentries;
    /** @var int number of entries left after filtering */
    public $totalfiltered;

    /**
     * Constructor
     *
     * @param moodle_url $url the base url of the pager links
     * @param int $from index of the first entry on the page
     * @param int $count number of entries per page
     * @param int $pages number of pages
     * @param int $totalentries total number of entries
     * @param int $totalfiltered number of entries left after filtering
     */
    public function __construct(moodle_url $url, $from, $count, $pages, $totalentries, $totalfiltered) {
        $this->url = $url;
        $this->from = $from;
        $this->count = $count;
        $this->pages = $pages;
        $this->totalentries = $totalentries;
        $this->totalfiltered = $totalfiltered;
    }
}

/**
 * The toolbar shown above the issue list.
 *
 * @package   mod_newsletter
 * @copyright 2013 Ivan Sakic <ivan.sakic3@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class newsletter_main_toolbar implements renderable {
    /** @var int the course module id */
    public $cmid;
    /** @var string how the issues are grouped */
    public $groupby;
    /** @var bool whether issues may be created */
    public $createissues;
    /** @var bool whether subscriptions may be managed */
    public $managesubs;

    /**
     * Constructor
     *
     * @param int $cmid the course module id
     * @param string $groupby how the issues are grouped
     * @param bool $createissues whether issues may be created
     * @param bool $managesubs whether subscriptions may be managed
     */
    public function __construct($cmid, $groupby, $createissues = false, $managesubs = false) {
        $this->cmid = $cmid;
        $this->groupby = $groupby;
        $this->createissues = $createissues;
        $this->managesubs = $managesubs;
    }
}

/**
 * Delivery progress of an issue.
 *
 * @package   mod_newsletter
 * @copyright 2013 Ivan Sakic <ivan.sakic3@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class newsletter_progressbar implements renderable {
    /** @var int how much is still to be done */
    public $tocomplete;
    /** @var int how much is already done */
    public $completed;

    /**
     * Constructor
     *
     * @param int $tocomplete how much is still to be done
     * @param int $completed how much is already done
     */
    public function __construct($tocomplete, $completed) {
        $this->tocomplete = $tocomplete;
        $this->completed = $completed;
    }
}

/**
 * The attachments of an issue.
 *
 * @package   mod_newsletter
 * @copyright 2013 Ivan Sakic <ivan.sakic3@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class newsletter_attachment_list implements renderable {
    /** @var stored_file[] the attached files */
    public $files;

    /**
     * Constructor
     *
     * @param array $files the attached files
     */
    public function __construct(array $files) {
        $this->files = $files;
    }
}

/**
 * Countdown shown until an issue is published.
 *
 * @package   mod_newsletter
 * @copyright 2013 Ivan Sakic <ivan.sakic3@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class newsletter_publish_countdown implements renderable {
    /** @var int the current timestamp */
    public $now;
    /** @var int the timestamp counted down to */
    public $until;

    /**
     * Constructor
     *
     * @param int $now the current timestamp
     * @param int $until the timestamp counted down to
     */
    public function __construct($now, $until) {
        $this->now = $now;
        $this->until = $until;
    }
}

/**
 * A button carrying out an action on an issue.
 *
 * @package   mod_newsletter
 * @copyright 2013 Ivan Sakic <ivan.sakic3@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class newsletter_action_button implements renderable {
    /** @var int the course module id */
    public $cmid;
    /** @var int the issue the action applies to */
    public $issueid;
    /** @var string the action to carry out */
    public $action;
    /** @var string the label of the button */
    public $label;

    /**
     * Constructor
     *
     * @param int $cmid the course module id
     * @param int $issueid the issue the action applies to
     * @param string $action the action to carry out
     * @param string $label the label of the button
     */
    public function __construct($cmid, $issueid, $action, $label) {
        $this->cmid = $cmid;
        $this->issueid = $issueid;
        $this->action = $action;
        $this->label = $label;
    }
}

/**
 * A link carrying out an action.
 *
 * @package   mod_newsletter
 * @copyright 2013 Ivan Sakic <ivan.sakic3@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class newsletter_action_link implements renderable {
    /** @var moodle_url the target of the link */
    public $url;
    /** @var string the text of the link */
    public $text;
    /** @var string the css class of the link */
    public $class;

    /**
     * Constructor
     *
     * @param moodle_url $url the target of the link
     * @param string $text the text of the link
     * @param string $class the css class of the link
     */
    public function __construct(moodle_url $url, $text = '', $class = 'mod_newsletter__action-link') {
        $this->url = $url;
        $this->text = $text;
        $this->class = $class;
    }
}
