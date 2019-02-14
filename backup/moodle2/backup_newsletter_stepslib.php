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
 * Define all the backup steps that will be used by the backup_newsletter_activity_task
 */

/**
 * Define the complete newsletter structure for backup, with file and id annotations
 */
class backup_newsletter_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.

        $newsletter = new backup_nested_element('newsletter', array('id'),
                array('name', 'intro', 'introformat', 'timecreated', 'timemodified',
                    'subscriptionmode', 'allowguestusersubscriptions', 'welcomemessage',
                    'welcomemessageguestuser'));

        $issues = new backup_nested_element('issues');
        $issue = new backup_nested_element('issue', array('id'),
                array('title', 'htmlcontent', 'stylesheetid', 'publishon', 'delivered', 'toc'));

        $subscriptions = new backup_nested_element('subscriptions');
        $subscription = new backup_nested_element('subscription', array('id'),
                array('userid', 'health', 'timesubscribed', 'timestatuschanged', 'subscriberid',
                    'unsubscriberid', 'sentnewsletters', 'nounsublink'));

        $bounces = new backup_nested_element('bounces');
        $bounce = new backup_nested_element('bounce', array('id'),
                array('userid', 'statuscode', 'timecreated', 'type', 'timereceived'));

        $deliveries = new backup_nested_element('deliveries');
        $delivery = new backup_nested_element('delivery', array('id'),
                array('userid', 'delivered'));

        // Build the tree.

        $newsletter->add_child($issues);
        $issues->add_child($issue);

        $newsletter->add_child($subscriptions);
        $subscriptions->add_child($subscription);

        $issue->add_child($bounces);
        $bounces->add_child($bounce);

        $issue->add_child($deliveries);
        $deliveries->add_child($delivery);

        // Define sources.
        $newsletter->set_source_table('newsletter', array('id' => backup::VAR_ACTIVITYID));

        // All these source definitions only happen if we are including user info.
        if ($userinfo) {
            $issue->set_source_sql('
                SELECT *
                  FROM {newsletter_issues}
                 WHERE newsletterid = ?', array(backup::VAR_PARENTID));

            // Need bounces ordered by id so parents are always before childs on restore
            $bounce->set_source_table('newsletter_bounces', array(
                'issueid' => backup::VAR_PARENTID), 'id ASC');
            $delivery->set_source_table('newsletter_issues',
                    array('newsletterid' => backup::VAR_PARENTID));
            $delivery->set_source_table('newsletter_deliveries',
                    array('issueid' => backup::VAR_PARENTID,
                        'newsletterid' => backup::VAR_ACTIVITYID));
            $subscription->set_source_table('newsletter_subscriptions',
                    array('newsletterid' => backup::VAR_PARENTID));
        }

        // Define id annotations. $issue->annotate_ids('group', 'groupid');

        $bounce->annotate_ids('user', 'userid');
        $delivery->annotate_ids('user', 'userid');
        $subscription->annotate_ids('user', 'userid');
        $subscription->annotate_ids('user', 'subscriberid');
        $subscription->annotate_ids('user', 'unsubscriberid');

        // Define file annotations.
        $newsletter->annotate_files('mod_newsletter', 'intro', null); // This file area has no itemid.
        $newsletter->annotate_files('mod_newsletter', 'stylesheets', 'id');
        $issue->annotate_files('mod_newsletter', 'attachments', 'id');

        // Return the root element (newsletter), wrapped into standard activity structure.
        return $this->prepare_activity_structure($newsletter);
    }

}
