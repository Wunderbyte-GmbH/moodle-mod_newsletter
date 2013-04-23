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

require_once(dirname(__FILE__).'/renderable.php');
require_once(dirname(__FILE__).'/CssToInlineStyles/CssToInlineStyles.php');

class newsletter implements renderable {

    /** @var stdClass the newsletter record that contains the global settings for this newsletter instance */
    private $instance = null;

    /** @var context the context of the course module for this newsletter instance (or just the course if we are
     creating a new one) */
    private $context = null;

    /** @var stdClass the course this newsletter instance belongs to */
    private $course = null;

    /** @var stdClass the course module for this assign instance */
    private $coursemodule = null;

    /** @var stdClass the admin config for all newsletter instances  */
    private $config = null;

    /** @var mod_newsletter_renderer the custom renderer for this module */
    private $renderer = null;

    /**
     * Constructor for the newsletter class
     *
     * @param mixed $context context|null the course module context (or the course context if the coursemodule has not been created yet)
     * @param mixed $coursemodule the current course module if it was already loaded - otherwise this class will load one from the context as required
     * @param mixed $course the current course  if it was already loaded - otherwise this class will load one from the context as required
     */
    public function __construct(context $context, stdClass $coursemodule, stdClass $course) {
        $this->context = $context;
        $this->coursemodule = $coursemodule;
        $this->course = $course;
    }

    /**
     * Get the current course module
     *
     * @return mixed stdClass|null The course module
     */
    public function get_course_module() {
        if (!$this->coursemodule) {
            if ($this->context && $this->context->contextlevel == CONTEXT_MODULE) {
                $this->coursemodule = get_coursemodule_from_id('newsletter', $this->context->instanceid, 0, false, MUST_EXIST);
            }
        }
        return $this->coursemodule;
    }

    /**
     * Get the settings for the current instance of this newsletter.
     *
     * @return stdClass The settings
     */
    public function get_instance() {
        if (!$this->instance) {
            if ($this->get_course_module()) {
                global $DB;
                $this->instance = $DB->get_record('newsletter', array('id' => $this->get_course_module()->instance), '*', MUST_EXIST);
            } else {
                throw new coding_exception('Improper use of the assignment class. Cannot load the assignment record.');
            }
        }
        return $this->instance;
    }

    /**
     * Get context module
     *
     * @return context
     */
    public function get_context() {
        return $this->context;
    }

    /**
     * Get the current course
     *
     * @return mixed stdClass|null The course
     */
    public function get_course() {
        global $DB;
        if (!$this->course) {
            if ($this->context) {
                $this->course = $DB->get_record('course', array('id' => $this->get_course_context()->instanceid), '*', MUST_EXIST);
            }
        }
        return $this->course;
    }

    /**
     * Get the module renderer
     *
     * @return mixed stdClass|null The module renderer
     */
    public function get_renderer() {
        if (!$this->renderer) {
            global $PAGE;
            $this->renderer = $PAGE->get_renderer('mod_newsletter');
        }
        return $this->renderer;
    }

    public function get_config() {
        if (!$this->config) {
            $this->config = get_config('newsletter');
        }
        return $this->config;
    }

    public function reset_userdata($data) {
        global $CFG, $DB;

        $newsletterssql = "SELECT n.id
                             FROM {newsletter} n
                            WHERE n.course = :course";
        $params = array("course" => $data->courseid);

        $DB->delete_records_select('newsletter_submissions', "newsletter IN ($newsletterssql)", $params);
        $status[] = array('component' => get_string('modulenameplural', 'newsletter'),
                          'item' => get_string('delete_all_subscriptions','newsletter'),
                          'error' => false);

        return array();
    }

    public function view($params) {
        global $PAGE, $OUTPUT;

        switch ($params[NEWSLETTER_PARAM_ACTION]) {
        case NEWSLETTER_ACTION_VIEW_ISSUES_PUBLISHER:
            $output = $this->view_issue_summary_list($params);
            break;
        case NEWSLETTER_ACTION_EDIT_ISSUE:
            $output = $this->view_edit_issue_page($params);
            break;
        case NEWSLETTER_ACTION_READ_ISSUE:
            $output = $this->view_read_issue_page($params);
            break;
        case NEWSLETTER_ACTION_DELETE_ISSUE:
            $output = $this->view_delete_issue_page($params);
            break;
        default:
            print_error('Wrong ' . NEWSLETTER_PARAM_ACTION . ' parameter value: ' . $params[NEWSLETTER_PARAM_ACTION]);
            break;
        }

        return $output;
    }

    private function get_issues($from = 0, $to = 0) {
        global $DB;
        $where =  "WHERE " . ($from ? " i.publishon > :from" : "1") .
                    " AND " . ($to ? " i.publishon > :to" : "1");
        $query = "SELECT *
                    FROM {newsletter_issues} i
                {$where}
                ORDER BY i.publishon ASC";
        $params = array('from' => $from, 'to' => $to);
        $records = $DB->get_records_sql($query, $params);
        foreach ($records as $record) {
            $record->cmid = $this->get_course_module()->id;
            if (isset($record->status)) {
                $data = json_decode($record->status, true);
                $record->numsubscriptions = count($data);
                $record->numdelivered = 0;
                foreach($data as $status) {
                    if($status === 1) {
                        $record->numdelivered++;
                    }
                }
            }
        }
        return $records;
    }

    private function get_issue($issueid) {
        global $DB;
        $record = $DB->get_record('newsletter_issues', array('id' => $issueid, 'newsletterid' => $this->get_instance()->id));
        if ($record) {
            $record->cmid = $this->get_course_module()->id;
            $record->context = $this->get_context()->id;
        }
        return $record;
    }

    private function view_read_issue_page(array $params) {
        $renderer = $this->get_renderer();
        //require_capability('mod/newsletter:viewissues', $this->context); TODO: add cap check
        $this->load_stylesheet($params[NEWSLETTER_PARAM_ISSUE]);
        $output = $renderer->render(
                new newsletter_header(
                        $this->get_instance(),
                        $this->get_context(),
                        false,
                        $this->get_course_module()->id));
        $currentissue = $this->get_issue($params[NEWSLETTER_PARAM_ISSUE]);
        $navigation_bar = new newsletter_navigation_bar(
                                $currentissue,
                                $this->get_first_issue($currentissue),
                                $this->get_previous_issue($currentissue),
                                $this->get_next_issue($currentissue),
                                $this->get_last_issue($currentissue));
        $output .= $renderer->render($navigation_bar);
        //*
        $converter = new CssToInlineStyles();
        // $html = file_get_contents('./examples/sumo/index.htm');
        // $css = file_get_contents('./examples/sumo/style.css');
        $converter->setHTML($currentissue->htmlcontent);
        $converter->setCSS('');
        $currentissue->htmlcontent = $converter->convert();
        //*/
        $output .= $renderer->render(new newsletter_issue($currentissue));
        $output .= $renderer->render($navigation_bar);
        $output .= $renderer->render_footer();
        return $output;
    }

    private function load_stylesheet($issueid) {
        global $CFG;
        $fs = get_file_storage();
        $files = $fs->get_area_files($this->get_context()->id, 'mod_newsletter', 'issue', $issueid, "itemid, filepath, filename", $includedirs = false);
        $url = null;
        foreach ($files as $file) {
            $baseurl = "/pluginfile.php/{$file->get_contextid()}/mod_newsletter/issue";
            $filename = $file->get_filename();
            $url = new moodle_url($baseurl . $file->get_filepath() . $file->get_itemid() . '/' . $filename);
        }
        // Read contents
        if ($url) {
            global $PAGE;
            $PAGE->requires->css($url);
        }
    }

    private function view_issue_summary_list(array $params) {
        $renderer = $this->get_renderer();
        //require_capability('mod/newsletter:viewissues', $this->context); TODO: add cap check
        $output = $renderer->render(
                new newsletter_header(
                        $this->get_instance(),
                        $this->get_context(),
                        false,
                        $this->get_course_module()->id));


        $output .= $renderer->render(new newsletter_main_toolbar($this->get_course_module()->id, $params[NEWSLETTER_PARAM_GROUP_BY]));
        $output .= $renderer->render($this->prepare_issue_list('', $params[NEWSLETTER_PARAM_GROUP_BY]));
        $output .= $renderer->render_footer();
        return $output;
    }

    // TODO: Add first day of the week check
    private function prepare_issue_list($heading, $groupby) {
        global $DB;
        $issues = $this->get_issues();
        $firstissue = reset($issues);
        $firstdayofweek = (int) get_string('firstdayofweek', 'langconfig');

        switch ($groupby) {
        case NEWSLETTER_GROUP_ISSUES_BY_YEAR:
            $from = strtotime("first day of this year", $firstissue->publishon);
            $to = strtotime("next year", $from);
            $dateformat = "%Y";
            break;
        case NEWSLETTER_GROUP_ISSUES_BY_MONTH:
            $from = strtotime("first day of this month", $firstissue->publishon);
            $to = strtotime("next month", $from);
            $dateformat = "%B %Y";
            break;
        case NEWSLETTER_GROUP_ISSUES_BY_WEEK:
            $from = strtotime("Monday this week", $firstissue->publishon);
            $to = strtotime("Monday next week", $from);
            $dateformat = "Week %W of year %Y";
            $datefromto = "%d. %B %Y";
            break;
        }

        $sectionlist = new newsletter_section_list($heading);
        $currentissuelist = new newsletter_issue_summary_list();
        foreach ($issues as $issue) {
            while ($issue->publishon < $from) {
                $from = $to;
                switch ($groupby) {
                case NEWSLETTER_GROUP_ISSUES_BY_YEAR:
                    $to = strtotime("next year", $from);
                    break;
                case NEWSLETTER_GROUP_ISSUES_BY_MONTH:
                    $to = strtotime("next month", $from);
                    break;
                case NEWSLETTER_GROUP_ISSUES_BY_WEEK:
                    $to = strtotime("Monday next week", $from);
                    break;
                }
            }

            if ($issue->publishon < $to) {
                $currentissuelist->add_issue_summary(new newsletter_issue_summary($issue));
            } else {
                if ($groupby == NEWSLETTER_GROUP_ISSUES_BY_WEEK) {
                    $heading = userdate($from, $dateformat) . ' (' . userdate($from, $datefromto) . ' - ' . userdate(strtotime('yesterday', $to), $datefromto) . ')';
                } else {
                    $heading = userdate($from, $dateformat);
                }
                $sectionlist->add_issue_section(new newsletter_section($heading, $currentissuelist));
                $currentissuelist = new newsletter_issue_summary_list();
                $currentissuelist->add_issue_summary(new newsletter_issue_summary($issue));
                $from = $to;
                switch ($groupby) {
                case NEWSLETTER_GROUP_ISSUES_BY_YEAR:
                    $to = strtotime("next year", $from);
                    break;
                case NEWSLETTER_GROUP_ISSUES_BY_MONTH:
                    $to = strtotime("next month", $from);
                    break;
                case NEWSLETTER_GROUP_ISSUES_BY_WEEK:
                    $to = strtotime("Monday next week", $from);
                    break;
                }
            }
        }
        if (!empty($currentissuelist->issues)) {
            if ($groupby == NEWSLETTER_GROUP_ISSUES_BY_WEEK) {
                $heading = userdate($from, $dateformat) . ' (' . userdate($from, $datefromto) . ' - ' . userdate(strtotime('yesterday', $to), $datefromto) . ')';
            } else {
                $heading = userdate($from, $dateformat);
            }
            $sectionlist->add_issue_section(new newsletter_section($heading, $currentissuelist));
        }

        return $sectionlist;
    }

    private function view_delete_issue_page(array $params) {
        //require_capability('mod/newsletter:editissues', $this->context); TODO: add cap check
        if (!$params[NEWSLETTER_PARAM_ISSUE] || !$this->check_issue_id($params[NEWSLETTER_PARAM_ISSUE])) {
            print_error('Wrong ' . NEWSLETTER_PARAM_ISSUE . ' parameter value: ' . $params[NEWSLETTER_PARAM_ISSUE]);
        }
    }

    private function view_edit_issue_page(array $params) {
        //require_capability('mod/newsletter:editissues', $this->context); TODO: add cap check
        if (!$this->check_issue_id($params[NEWSLETTER_PARAM_ISSUE])) {
            print_error('Wrong ' . NEWSLETTER_PARAM_ISSUE . ' parameter value: ' . $params[NEWSLETTER_PARAM_ISSUE]);
        }

        require_once(dirname(__FILE__).'/issue_form.php');
        $mform = new mod_newsletter_issue_form(null, array(
                'id' => $this->get_course_module()->id,
                'allowedcontent' => $this->get_instance()->allowedcontent,
                'issue' => $this->get_issue($params[NEWSLETTER_PARAM_ISSUE])));

        if ($data = $mform->get_data()) {
            if (!$data->issue) {
                $this->add_issue($data);
            } else {
                $this->update_issue($data);
            }
            $url = new moodle_url('/mod/newsletter/view.php', array('id' => $this->get_course_module()->id));
            redirect($url);
        }

        $output = '';
        $renderer = $this->get_renderer();

        $output .= $renderer->render(
                    new newsletter_header(
                                $this->get_instance(),
                                $this->get_context(),
                                false,
                                $this->get_course_module()->id));
        $output .= $renderer->render(new newsletter_form($mform, get_string('edit_issue_title', 'newsletter')));

        $output .= $renderer->render_footer();
        return $output;
    }

    private function check_issue_id($issueid) {
        global $DB;

        return !$issueid || $DB->get_field('newsletter_issues', 'newsletterid',
                array('id' => $issueid, 'newsletterid' => $this->get_instance()->id));
    }

    private function add_issue(stdClass $data) {
        global $DB;
        $issue = new stdClass();
        $issue->id = 0;
        $issue->newsletterid = $this->get_instance()->id;
        $issue->title = $data->title;
        $issue->plaincontent = $data->plaincontent;
        $issue->htmlcontent = $data->htmlcontent['text'];
        $issue->publishon = $data->publishon;

        $issue->id = $DB->insert_record('newsletter_issues', $issue);

        if ($data->stylesheet) {
            file_save_draft_area_files($data->stylesheet, $this->get_context()->id, 'mod_newsletter', 'issue', $issue->id);
        }

        return $issue->id;
    }

    private function update_issue(stdClass $data) {
        global $DB;
        $issue = new stdClass();
        $issue->id = $data->issue;
        $issue->title = $data->title;
        $issue->newsletterid = $this->get_instance()->id;
        $issue->plaincontent = $data->plaincontent;
        $issue->htmlcontent = $data->htmlcontent['text'];
        $issue->publishon = $data->publishon;
        $DB->update_record('newsletter_issues', $issue);

        if ($data->stylesheet) {
            file_save_draft_area_files($data->stylesheet, $this->get_context()->id, 'mod_newsletter', 'issue', $issue->id);
        }
    }

    private function delete_issue($issueid) {
        global $DB;
        $DB->delete_records('newsletter_issues', array('id' => $issueid));
    }

        private function get_previous_issue($issue) {
        global $DB;
        $query = "SELECT *
                    FROM {newsletter_issues} i
                   WHERE i.newsletterid = :newsletterid
                     AND i.publishon < :publishon
                ORDER BY i.publishon DESC";
        $params = array('newsletterid' => $issue->newsletterid, 'publishon' => $issue->publishon);
        $results = $DB->get_records_sql($query, $params);
        return empty($results) ? null : reset($results);
    }

    private function get_next_issue($issue) {
        global $DB;
        $query = "SELECT *
                    FROM {newsletter_issues} i
                   WHERE i.newsletterid = :newsletterid
                     AND i.publishon > :publishon
                ORDER BY i.publishon ASC";
        $params = array('newsletterid' => $issue->newsletterid, 'publishon' => $issue->publishon);
        $results = $DB->get_records_sql($query, $params);
        return empty($results) ? null : reset($results);
    }

    private function get_first_issue($issue) {
        global $DB;
        $query = "SELECT *
                    FROM {newsletter_issues} i
                   WHERE i.newsletterid = :newsletterid
                     AND i.publishon < :publishon
                     AND i.id != :id
                ORDER BY i.publishon ASC";
        $params = array('newsletterid' => $issue->newsletterid, 'publishon' => $issue->publishon, 'id' => $issue->id);
        $results = $DB->get_records_sql($query, $params);
        return empty($results) ? null : reset($results);
    }

    private function get_last_issue($issue) {
        global $DB;
        $query = "SELECT *
                    FROM {newsletter_issues} i
                   WHERE i.newsletterid = :newsletterid
                     AND i.publishon > :publishon
                     AND i.id != :id
                ORDER BY i.publishon DESC";
        $params = array('newsletterid' => $issue->newsletterid, 'publishon' => $issue->publishon, 'id' => $issue->id);
        $results = $DB->get_records_sql($query, $params);
        return empty($results) ? null : reset($results);
    }
}
