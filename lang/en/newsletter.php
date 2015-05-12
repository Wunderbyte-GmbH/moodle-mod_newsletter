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
$string['header_health'] = 'Status';
$string['header_actions'] = 'Actions';

$string['entries_per_page'] = 'Entries per page';
$string['create_new_issue'] = 'Create new issue';
$string['manage_subscriptions'] = 'Manage subscriptions';

$string['health_0'] = 'Active';
$string['health_1'] = 'Problematic';
$string['health_2'] = 'Blacklisted';
$string['health_4'] = 'Unsubscribed';

$string['page_first'] = 'First';
$string['page_previous'] = 'Previous';
$string['page_next'] = 'Next';
$string['page_last'] = 'Last';

$string['subscribe'] = 'Subscribe';
$string['guestsubscribe'] = 'Subscribe now!';

$string['subscribe_question'] = 'Would you like to subscribe to newsletter "{$a->name}" using the e-mail address "{$a->email}"?';
$string['unsubscribe_question'] = 'Would you like to unsubscribe your e-mail address "{$a->email}" from newsletter "{$a->name}"?';
$string['unsubscription_succesful'] = 'Your email "{$a->email}" was successfully removed from the following newsletter: "{$a->name}".';

$string['new_user_subscribe_message'] = 'Hello {$a->fullname},

You have requested to be subscribed to
\'{$a->newslettername}\' newsletter at \'{$a->sitename}\'
using this email address. A new account has been made for you:

Username: {$a->username}
Password: {$a->password}

You can change the account details after confirmation.
To confirm your new account, please go to this web address:

{$a->link}

In most mail programs, this should appear as a blue link
which you can just click on.  If that doesn\'t work,
then cut and paste the address into the address
line at the top of your web browser window.

If you need help, please contact the site administrator,
{$a->admin}';

$string['account_confirmed'] = 'Welcome to {$a->sitename}, {$a->fullname}!

Your account {$a->username} has been enabled.
To edit your account details, click {$a->editlink}.
To proceed to the newsletter, click {$a->newsletterlink}.';

$string['account_already_confirmed'] = 'Your account has already been enabled.
To proceed to the newsletter, click {$a->newsletterlink}.';
$string['allusers'] = 'Users (including unsubscribed): ';
$string['filteredusers'] = 'Filtered users: ';



$string['unsubscribe_link_text'] = 'Click here to unsubscribe';
$string['unsubscribe_link'] = '<hr /><p><a href="{$a->link}">{$a->text}</a></p>';

$string['publish_in'] = 'To be published in {$a->days} days, {$a->hours} hrs, {$a->minutes} min, {$a->seconds} sec.';
$string['already_published'] = 'This issue has been published.';
$string['delete_issue_question'] = 'Are you sure you want to delete this issue?';
$string['delete_subscription_question'] = 'Are you sure you want to delete this subscription?';
$string['no_issues'] = 'This newsletter has no issues yet.';

$string['edit_subscription_title'] = 'Edit subscription';
$string['subscribe'] = 'Subscribe';
$string['unsubscribe'] = 'Unsubscribe';

$string['allow_guest_user_subscriptions_label'] = 'Allow guest user subscription';
$string['allow_guest_user_subscriptions_desc'] = 'Enable to allow guest users to subscribe to newsletters on this site. This will necessitate their creating user accounts.';

$string['newsletter:viewnewsletter'] = 'View newsletter';
$string['newsletter:addinstance'] = 'Add a newsletter';
$string['newsletter:createissue'] = 'Create a newsletter issue';
$string['newsletter:deleteissue'] = 'Delete a newsletter issue';
$string['newsletter:deletesubscription'] = 'Delete newsletter subscriptions';
$string['newsletter:editissue'] = 'Edit a newsletter issue';
$string['newsletter:editsubscription'] = 'Edit newsletter subscriptions';
$string['newsletter:manageownsubscription'] = 'Manage my subsctiption to the newsletter';
$string['newsletter:managesubscriptions'] = 'Manage subscriptions to the newsletter';
$string['newsletter:publishissue'] = 'Publish a newsletter issue';
$string['newsletter:readissue'] = 'Read a newsletter issue';
$string['newsletter:subscribecohort'] = 'Subscribe a cohort to the newsletter';
$string['newsletter:subscribeuser'] = 'Subscribe users to the newsletter';
$string['newsletter:unsubscribecohort'] = 'Unsubscribe a cohort from a newsletter';
$string['newsletter:viewnewsletter'] = 'View a newsletter instance';

$string['emailexists'] = 'Useraccount with this e-mail adress exists. Please login in order to subscribe to the newsletter. If you forgot your login, use the {$a} link on the login page.';
$string['guestsubscriptionsuccess'] = 'Your email was successfully registered. <br /> In order to activate the subscription, please check the inbox of your mailaccount ({$a}) and click on the confirmation link';

