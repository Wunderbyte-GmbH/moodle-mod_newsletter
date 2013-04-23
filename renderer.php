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
 * This file contains a renderer for the newsletter module
 *
 * @package mod_newsletter
 * @copyright 2013 Ivan Šakić <ivan.sakic3@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/newsletter/locallib.php');

/**
 * A custom renderer class that extends the plugin_renderer_base and is used by the newsletter module.
 */
class mod_newsletter_renderer extends plugin_renderer_base {

    public function render_newsletter_header(newsletter_header $header) {
        $output = '';

        $this->page->set_title(get_string('pluginname', 'newsletter'));
        $this->page->set_heading($header->newsletter->name);

        $output .= $this->output->header();

        if ($header->showintro) {
            $output .= $this->output->box_start('generalbox boxaligncenter', 'intro');
            $output .= format_module_intro('newsletter', $header->assign, $header->coursemoduleid);
            $output .= $this->output->box_end();
        }

        return $output;
    }

    /**
     * Renders a generic form
     * @param assign_form $form The form to render
     * @return string
     */
    public function render_newsletter_form(newsletter_form $form) {
        $output = '';
        if ($form->jsinitfunction) {
            $this->page->requires->js_init_call($form->jsinitfunction, array());
        }
        $output .= $this->output->heading($form->title);
        $output .= $this->output->box_start('boxaligncenter ' . $form->classname);
        $output .= $this->moodleform($form->form);
        $output .= $this->output->box_end();

        return $output;
    }

    public function render_newsletter_section_list(newsletter_section_list $section_list) {
        $output = '';

        $output .= html_writer::start_tag('div', array('class' => 'mod_newsletter__section-list'));
        /* // Temporarily unused
        $output .= html_writer::start_tag('h3');
        $output .= $section_list->heading;
        $output .= html_writer::end_tag('h3');
        //*/
        $output .= html_writer::start_tag('ul');
        foreach ($section_list->sections as $section) {
            $output .= html_writer::start_tag('li');
            $output .= $this->render($section);
            $output .= html_writer::end_tag('li');
        }
        $output .= html_writer::end_tag('ul');
        $output .= html_writer::end_tag('div');

        return $output;
    }

    public function render_newsletter_section(newsletter_section $section) {
        $output = '';

        $output .= html_writer::start_tag('div', array('class' => 'mod_newsletter__section'));
        $output .= html_writer::start_tag('h3');
        $output .= $section->heading;
        $output .= html_writer::end_tag('h3');
        $output .= $this->render($section->summary_list);
        $output .= html_writer::end_tag('div');

        return $output;
    }

    public function render_newsletter_issue_summary_list(newsletter_issue_summary_list $list) {
        $output = '';

        $output .= html_writer::start_tag('div', array('class' => 'mod_newsletter__issue-list'));
        $output .= html_writer::start_tag('ul');
        foreach ($list->issues as $issue) {
            $output .= html_writer::start_tag('li');
            $output .= $this->render($issue);
            $output .= html_writer::end_tag('li');
        }
        $output .= html_writer::end_tag('ul');
        $output .= html_writer::end_tag('div');

        return $output;
    }

    public function render_newsletter_issue(newsletter_issue $issue) {
        $output = '';
        $output .= html_writer::start_tag('div', array('class' => 'mod_newsletter__issue--full__container'));
        $output .= $issue->htmlcontent;
        $output .= html_writer::end_tag('div');
        return $output;
    }

    public function render_newsletter_issue_summary(newsletter_issue_summary $issue) {
        $link = '';
        $link .= html_writer::empty_tag('img', array('src' => $this->output->pix_url('icon', 'newsletter'), 'class' => 'mod_newsletter__issue--summary__link-read-icon'));
        $link .= html_writer::start_tag('span');
        $link .= $issue->title . " (" . userdate($issue->publishon, '%d %B %Y') . ")";
        $link .= html_writer::end_tag('span');
        $url = new moodle_url('/mod/newsletter/view.php', array('id' => $issue->cmid, 'action' => NEWSLETTER_ACTION_READ_ISSUE, 'issue' => $issue->id));

        $output = '';
        $output .= html_writer::start_tag('div', array('class' => 'mod_newsletter__issue--summary'));
        $output .= html_writer::start_tag('div', array('class' => 'mod_newsletter__issue--summary__link-read'));
        $output .= html_writer::link($url, $link);
        $output .= html_writer::end_tag('div');
        $output .= html_writer::start_tag('div', array('class' => 'mod_newsletter__issue--summary__link-edit'));
        $output .= $this->render(new newsletter_action_button($issue->cmid, $issue->id, NEWSLETTER_ACTION_EDIT_ISSUE, get_string('edit_issue', 'newsletter')));
        $output .= html_writer::end_tag('div');
        $output .= $this->render(new newsletter_progressbar($issue->numdelivered, $issue->numsubscriptions));
        $output .= html_writer::end_tag('div');

        return $output;
    }

    public function render_newsletter_navigation_bar(newsletter_navigation_bar $navigation_bar) {
        $url = new moodle_url('/mod/newsletter/view.php');
        $firstissuelink = $navigation_bar->firstissue ?
                html_writer::link(
                new moodle_url($url, array('id' => $navigation_bar->currentissue->cmid,
                        'action' => NEWSLETTER_ACTION_READ_ISSUE, 'issue' => $navigation_bar->firstissue->id)),
                '', array('class' => 'mod-newsletter__navigation-bar__button--first')) : '';
        $previousissuelink = $navigation_bar->previousissue ?
                html_writer::link(
                new moodle_url($url, array('id' => $navigation_bar->currentissue->cmid,
                        'action' => NEWSLETTER_ACTION_READ_ISSUE, 'issue' => $navigation_bar->previousissue->id)),
                '', array('class' => 'mod-newsletter__navigation-bar__button--previous')) : '';
        $nextissuelink = $navigation_bar->nextissue ?
                html_writer::link(
                new moodle_url($url, array('id' => $navigation_bar->currentissue->cmid,
                        'action' => NEWSLETTER_ACTION_READ_ISSUE, 'issue' => $navigation_bar->nextissue->id)),
                '', array('class' => 'mod-newsletter__navigation-bar__button--next')) : '';
        $lastissuelink = $navigation_bar->lastissue ?
                html_writer::link(
                new moodle_url($url, array('id' => $navigation_bar->currentissue->cmid,
                        'action' => NEWSLETTER_ACTION_READ_ISSUE, 'issue' => $navigation_bar->lastissue->id)),
                '', array('class' => 'mod-newsletter__navigation-bar__button--last')) : '';

        $output = '';
        $output .= html_writer::start_tag('div', array('class' => 'mod-newsletter__navigation-bar__container'));
        $output .= html_writer::start_tag('div', array('class' => 'mod-newsletter__navigation-bar'));
        $output .= $firstissuelink;
        $output .= $previousissuelink;
        $output .= html_writer::start_tag('span', array('class' => 'mod-newsletter__navigation-bar__title'));
        $output .= "{$navigation_bar->currentissue->title}";
        $output .= html_writer::end_tag('span');
        $output .= $nextissuelink;
        $output .= $lastissuelink;
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');
        return $output;
    }

    public function render_newsletter_action_button(newsletter_action_button $button) {
        $url = new moodle_url('/mod/newsletter/view.php', array('id' => $button->cmid, 'action' => $button->action, 'issue' => $button->issueid));
        return html_writer::link($url, $button->label);
    }

    public function render_newsletter_main_toolbar(newsletter_main_toolbar $toolbar) {
        $output = '';
        $output .= html_writer::start_tag('div');
        $output .= html_writer::start_tag('span');
        $output .= 'Group issues by';
        $output .= html_writer::end_tag('span');
        $options = array(
            NEWSLETTER_GROUP_ISSUES_BY_YEAR => get_string('year'),
            NEWSLETTER_GROUP_ISSUES_BY_MONTH => get_string('month'),
            NEWSLETTER_GROUP_ISSUES_BY_WEEK => get_string('week'),
        );
        $output .= html_writer::start_tag('form', array('method' => 'GET', 'action' => new moodle_url('/mod/newsletter/view.php')));
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'id', 'value' => $toolbar->cmid));
        $output .= html_writer::select($options, NEWSLETTER_PARAM_GROUP_BY, $toolbar->groupby, false);
        $output .= html_writer::empty_tag('input', array('type' => 'submit', 'value' => 'Refresh'));
        $output .= html_writer::end_tag('form');
        $output .= html_writer::end_tag('div');
        return $output;
    }

    /**
     * Renders the footer
     *
     * @return void
     */
    public function render_footer() {
        return $this->output->footer();
    }

    /**
     * Helper method dealing with the fact we can not just fetch the output of moodleforms
     *
     * @param moodleform $mform
     * @return string HTML of the form
     */
    public function moodleform(moodleform $mform) {
        $output = '';
        ob_start();
        $mform->display();
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }


    public function render_widget(widget $widget) {
        $output = '';
        return $output;
    }

    public function render_newsletter_progressbar(newsletter_progressbar $progressbar) {
        $output = '';
        if ($progressbar->total == 0) {
            return $output;
        }
        $value = $progressbar->completed / $progressbar->total * 100;
        $output .= html_writer::start_tag('div', array('class' => 'mod_newsletter__meter'));
        $output .= html_writer::start_tag('div', array('class' => 'mod_newsletter__meter__foreground', 'style' => "width: $value%;"));
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');
        return $output;
    }
}
