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
 * English strings for newsletter
 *
 * @package    mod_newsletter
 * @copyright  2013 Ivan Šakić <ivan.sakic3@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Newsletter';
$string['modulenameplural'] = 'Newsletters';
$string['modulename_help'] = 'The newsletter module allows publishing of e-mail newsletters.';
$string['newslettername'] = 'Name';
$string['newslettername_help'] = 'This is the content of the help tooltip associated with the newsletter field. Markdown syntax is supported.';
$string['newsletter'] = 'Newsletter';
$string['pluginadministration'] = 'Newsletter administration';
$string['pluginname'] = 'Newsletter';
$string['newsletterintro'] = 'Description';
$string['stylesheets'] = 'Upload newsletter stylesheets';
$string['stylesheets_help'] = 'Upload CSS files which will serve as stylesheets for this newsletter\'s issues. You may upload more than one, and you can selected them when creating a new issue. This field is optional, as the module comes with at least one out-of-the-box stylesheet file.';

$string['edit_issue'] = 'Edit this issue';
$string['delete_issue'] = 'Delete this issue';

$string['edit_issue_title'] = 'Edit newsletter issue';
$string['issue_title'] = 'Issue title';
$string['issue_title_help'] = 'Type in the title of this issue. Required.';

$string['issue_htmlcontent'] = 'HTML content';
$string['issue_stylesheet'] = 'Stylesheet file for HTML content';

$string['mode_group_by_year'] = 'Group issues by year';
$string['mode_group_by_month'] = 'Group issues by month';
$string['mode_group_by_week'] = 'Group issues by week';

$string['attachments'] = 'Attachments';
$string['attachments_help'] = 'Upload files you want to deliver with this issue as attachments.';

$string['delete_all_subscriptions'] = 'Delete all subscriptions';

$string['subscription_mode'] = 'Subscription mode';
$string['subscription_mode_help'] = 'Select whether the enrolled users are subscribed to this newsletter automatically (opt out) or have to subscribe manually (opt in). WARNING! Opt out will automatically subscribe all enrolled users!';
$string['sub_mode_opt_in'] = 'Opt in';
$string['sub_mode_opt_out'] = 'Opt out';
$string['sub_mode_forced'] = 'Forced';

$string['publishon'] = 'Publish on';
$string['header_content'] = 'Issue content';
$string['header_publish'] = 'Publishing options';

$string['config_send_notifications_label'] = 'Send notifications';
$string['config_send_notifications_desc'] = 'Check this box to enable sending subscription-related notifications to subscribers.';
$string['config_debug_label'] = 'Cron DEBUG mode';
$string['config_debug_desc'] = 'Check this box to enable debug output for the newsletter cron job.';
$string['config_activation_timeout_label'] = 'Expiration time of activation links';
$string['config_activation_timeout_desc'] = 'Select how many days the activation links sent by e-mail will be valid for.';
$string['default_stylesheet'] = 'Default stylesheet';

$string['header_email'] = 'E-Mail';
$string['header_name'] = 'Name';
$string['header_health'] = 'Health';
$string['header_actions'] = 'Actions';

$string['entries_per_page'] = 'Entries per page';
$string['create_new_issue'] = 'Create new issue';
$string['manage_subscriptions'] = 'Manage subscriptions';

$string['health_0'] = 'Healthy';
$string['health_1'] = 'Problematic';
$string['health_2'] = 'Blacklisted';

$string['page_first'] = 'First';
$string['page_previous'] = 'Previous';
$string['page_next'] = 'Next';
$string['page_last'] = 'Last';

$string['subscribe'] = 'Subscribe';

$string['subscribe_question'] = 'Would you like to subscribe to newsletter "{$a->name}" using the e-mail address "{$a->email}"?';
$string['unsubscribe_question'] = 'Would you like to unsubscribe your e-mail address "{$a->email}" from newsletter "{$a->name}"?';

$string['new_user_subscribe_message'] = '<p>Hello!</p><p>You have requested to be subscribed to newsletter "{$a->name}" with the following address: {$a->email}. In order to make it happen a new user account has been created for you:<br/>Username: <strong>{$a->username}</strong><br/>Password: <strong>{$a->password}</strong><br/>In order to activate your account please follow <a href="{$a->activateurl}">this link</a>. If you have made a mistake and would like to cancel the activation process follow this link <a href="{$a->cancelurl}">this link</a>.</p><p>Thank you!</p>';
$string['unsubscribe_link_text'] = 'Click here to unsubscribe';
$string['unsubscribe_link'] = '<hr /><p><a href="{$a->link}">{$a->text}</a></p>';

$string['publish_in'] = 'To be published in {$a->days} days, {$a->hours} hrs, {$a->minutes} min, {$a->seconds} sec.';
$string['already_published'] = 'This issue has been published.';
$string['delete_issue_question'] = 'Are you sure you want to delete this issue?';
$string['delete_subscription_question'] = 'Are you sure you want to delete this subscription?';
$string['no_issues'] = 'This newsletter has no issues yet.';