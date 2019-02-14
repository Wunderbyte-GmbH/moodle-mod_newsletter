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
 *
 * @package mod_newsletter
 * @subpackage backup-moodle2
 * @copyright 2018 onwards David Bogner {@link http://www.edulabs.org}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_newsletter_activity_task
 */

/**
 * Structure step to restore one newsletter activity
 */
class restore_newsletter_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {
        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('newsletter', '/activity/newsletter');
        if ($userinfo) {
            $paths[] = new restore_path_element('newsletter_issue',
                    '/activity/newsletter/issues/issue');
            $paths[] = new restore_path_element('newsletter_subscription',
                    '/activity/newsletter/subscriptions/subscription');
            $paths[] = new restore_path_element('newsletter_bounce',
                    '/activity/newsletter/bounces/bounce');
            $paths[] = new restore_path_element('newsletter_delivery',
                    '/activity/newsletter/deliveries/delivery');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    protected function process_newsletter($data) {
        global $DB;

        $data = (object) $data;
        $data->course = $this->get_courseid();

        $newitemid = $DB->insert_record('newsletter', $data);
        $this->apply_activity_instance($newitemid);
    }

    protected function process_newsletter_issue($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        // TODO: I don't know how but stylesheetid needs to be updated somehow.

        $data->newsletterid = $this->get_new_parentid('newsletter');
        $newitemid = $DB->insert_record('newsletter_issues', $data);
        $this->set_mapping('newsletter_issue', $oldid, $newitemid, true); // Fourth parameter is restorefiles.
    }

    protected function process_newsletter_subscription($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->newsletterid = $this->get_new_parentid('newsletter');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->subscriberid = $this->get_mappingid('user', $data->userid);
        $data->unsubscriberid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('newsletter_subscriptions', $data);
        $this->set_mapping('newsletter_subscription', $oldid, $newitemid, true);
    }

    protected function process_newsletter_bounce($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->issueid = $this->get_new_parentid('newsletter_issue');
        $data->userid = $this->get_mappingid('user', $data->userid);

        // Create only a new bounce if it does not already exist (see MDL-59854).
        $newitemid = $DB->insert_record('newsletter_bounces', $data);
        $this->set_mapping('newsletter_bounce', $oldid, $newitemid, true);
    }

    protected function process_newsletter_delivery($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->issueid = $this->get_new_parentid('newsletter_issue');
        $data->newsletterid = $this->get_new_parentid('newsletter');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('newsletter_deliveries', $data);
        $this->set_mapping('newsletter_delivery', $oldid, $newitemid, true);
    }

    protected function after_execute() {
        // Add newsletter related files, no need to match by itemname (just internally handled
        // context).
        $this->add_related_files('mod_newsletter', 'intro', null);

        // Add post related files, matching by itemname = 'newsletter_issue'.
        $this->add_related_files('mod_newsletter', 'stylesheets', 'newsletter');
        $this->add_related_files('mod_newsletter', 'attachments', 'newsletter_issue');
    }
}
