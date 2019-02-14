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
 * @copyright  2015 David Bogner <info@edulabs.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_newsletter\subscription;
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/cohort/lib.php');

/**
 * Form for subscribing and unsubscribing users to a newsletter
 *
 */
class mod_newsletter_subscriber_selector_form extends \moodleform {
    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = &$this->_form;
        $data = &$this->_customdata;

        $existing = $data['existing'];
        $potential = $data['potential'];
        $leftarrow = $data['leftarrow'];
        $rightarrow = $data['rightarrow'];

        $mform->addElement('hidden', 'id', $data['id']);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', NEWSLETTER_PARAM_ACTION, NEWSLETTER_ACTION_MANAGE_SUBSCRIPTIONS);
        $mform->setType(NEWSLETTER_PARAM_ACTION, PARAM_ALPHA);

        $mform->addElement('header', 'subscribe_users', get_string('newsletter:subscribeuser', 'mod_newsletter'));
        $mform->setExpanded('subscribe_users', false);

        $existingcell = new \html_table_cell();
        $existingcell->text = $existing->display(true);
        if ($CFG->branch >= 33) {
            $existingcell->id = 'existingcell';
        } else {
            $existingcell->attributes['class'] = 'existing';
        }
        $actioncell = new \html_table_cell();
        $actioncell->text  = \html_writer::start_tag('div', array());
        if ($CFG->branch >= 33) {
            $actioncell->text .= \html_writer::empty_tag('input', array(
                            'type' => 'submit',
                            'name' => 'add',
                            'value' => $leftarrow . ' ' . get_string('subscribe', 'mod_newsletter'),
                            'class' => 'btn btn-secondary',
                            'style' => 'margin-bottom: 3px;')
            );
        } else {
            $actioncell->text .= \html_writer::empty_tag('input', array(
                            'type' => 'submit',
                            'name' => 'add',
                            'value' => $leftarrow . ' ' . get_string('subscribe', 'mod_newsletter'),
                            'class' => 'actionbutton')
            );
        }
        $actioncell->text .= \html_writer::end_tag('div', array());

        $actioncell->text .= \html_writer::start_tag('div', array());
        if ($CFG->branch >= 33) {
            $actioncell->text .= \html_writer::empty_tag('input', array(
                            'type' => 'submit',
                            'name' => 'unsubscribe',
                            'value' => ' ' . get_string('unsubscribe', 'mod_newsletter'),
                            'class' => 'btn btn-secondary',
                            'style' => 'margin-bottom: 3px;')
            );
        } else {
            $actioncell->text .= \html_writer::empty_tag('input', array(
                            'type' => 'submit',
                            'name' => 'unsubscribe',
                            'value' => ' ' . get_string('unsubscribe', 'mod_newsletter'),
                            'class' => 'actionbutton')
            );
        }
        $actioncell->text .= \html_writer::end_tag('div', array());

        $actioncell->text .= \html_writer::start_tag('div', array());
        if ($CFG->branch >= 33) {
            $actioncell->text .= \html_writer::empty_tag('input', array(
                            'type' => 'submit',
                            'name' => 'remove',
                            'value' => get_string('delete') . ' ' . $rightarrow,
                            'class' => 'btn btn-secondary')
            );
        } else {
            $actioncell->text .= \html_writer::empty_tag('input', array(
                            'type' => 'submit',
                            'name' => 'remove',
                            'value' => get_string('delete') . ' ' . $rightarrow,
                            'class' => 'actionbutton')
            );
        }
        $actioncell->text .= \html_writer::end_tag('div', array());
        $actioncell->text .= \html_writer::div('<br />' . get_string('unsubscribedinfo', 'mod_newsletter'));
        $actioncell->id = 'buttonscell';
        $actioncell->style = 'vertical-align:middle;';
        $potentialcell = new \html_table_cell();
        $potentialcell->text = $potential->display(true);
        if ($CFG->branch >= 33) {
            $potentialcell->id = 'potentialcell';
        } else {
            $potentialcell->attributes['class'] = 'potential';
        }

        $table = new \html_table();
        if ($CFG->branch >= 33) {
            $table->attributes['class'] = 'groupmanagementtable boxaligncenter';
        } else {
            $table->attributes['class'] = 'subscribertable boxaligncenter';
        }
        $table->data = array(new \html_table_row(array($existingcell, $actioncell, $potentialcell)));
        $mform->addElement('html', \html_writer::table($table));
    }
}
