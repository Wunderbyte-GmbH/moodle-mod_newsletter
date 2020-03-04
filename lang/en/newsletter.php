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
 * @copyright  2015 onwards David Bogner <info@edulabs.org>
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

$string['eventissuecreated'] = 'Newsletter issue created';
$string['eventsubscriptioncreated'] = 'New newsletter subscription';
$string['eventsubscriptiondeleted'] = 'Newsletter subscription deleted';
$string['eventsubscriptionunsubscribed'] = 'Newsletter subscription unsubscribed';
$string['eventissueviewed'] = 'Newsletter issue viewed';
$string['eventsubscriptionsviewed'] = 'Newsletter subscriptions viewed';
$string['eventsubscriptionresubscribed'] = 'Re-subscription to newsletter';

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
$string['attachments_no'] = 'No Attachments uploaded.';

$string['delete_all_subscriptions'] = 'Delete all subscriptions';

$string['subscription_mode'] = 'Subscription mode';
$string['subscription_mode_help'] = 'Select whether the enrolled users are subscribed to this newsletter automatically (opt out) or have to subscribe manually (opt in). WARNING! Opt out will automatically subscribe all enrolled users!';
$string['sub_mode_opt_in'] = 'Opt in';
$string['sub_mode_opt_out'] = 'Opt out';
$string['sub_mode_forced'] = 'Forced';

$string['publishon'] = 'Publish on';
$string['header_content'] = 'Issue content';
$string['header_publish'] = 'Publishing options';
$string['header_publishinfo'] = 'After the delivery of the newsletter issue is started, it will not be possible to change the publishing date anymore';

$string['config_send_notifications_label'] = 'Send notifications';
$string['config_send_notifications_desc'] = 'Check this box to enable sending subscription-related notifications to subscribers.';
$string['config_debug_label'] = 'Cron DEBUG mode';
$string['config_debug_desc'] = 'Check this box to enable debug output for the newsletter cron job.';
$string['config_activation_timeout_label'] = 'Expiration time of activation links';
$string['config_activation_timeout_desc'] = 'Select how many days the activation links sent by e-mail will be valid for.';
$string['config_bounceprocessing'] = 'Settings for bounce handling: Provide the mailbox login data for the bounce mail address';
$string['config_bounceinfo'] = 'Use bounce handling for newsletter only if you can not configure the Moodle VERP bounce settings as described on the following page: https://docs.moodle.org/dev/Email_processing Using the VERP Moodle method can not be used on every system and is quite difficult to setup. This is an easier alternative, but works only for the newsletter module. After saving the settings test them at {$a}';
$string['config_host'] = 'Mail host server (ex. mail.yourserver.com)';
$string['config_username'] = 'Mailbox username';
$string['config_password'] = 'Mailbox password';
$string['config_service'] = 'The service to use (imap or pop3)';
$string['config_service_option'] = 'The service options (none, tls, notls, ssl)';
$string['config_port'] = 'The port to access your mailbox ; default 143, other common choices are 110 (pop3), 995 (gmail)';
$string['config_bounce_enable'] = 'Enable bounce processing for the newsletter module';
$string['config_bounce_email'] = 'E-Mail address that should receive the bounces. Make sure, that this address is only used for handling bounce mails.';
$string['config_sendinglimit'] = 'Limit E-Mails per execution';
$string['config_sendinglimit_desc'] = 'Limits how many E-Mails are sent every time cron runs to avoid spam.';

$string['default_stylesheet'] = 'Default stylesheet';

$string['header_email'] = 'E-Mail';
$string['header_name'] = 'Name';
$string['header_health'] = 'Status (Delivered / Bounces)';
$string['header_bounceratio'] = 'Bounce ratio';
$string['header_actions'] = 'Actions';
$string['header_timesubscribed'] = 'Subscription date';
$string['header_timestatuschanged'] = 'Last status change';
$string['header_subscriberid'] = 'Subscribed by';
$string['header_unsubscriberid'] = 'Unsubscribed by';

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
$string['resubscribe'] = 'Confirm re-subscription';
$string['resubscribe_text'] = 'You have been unsubscribed from this newsletter. Do you really want to resubscribe?';
$string['resubscribe_btn'] = 'Confirm';

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
$string['groupby'] = 'Group issues by:';

$string['unsubscribe_link_text'] = 'Click here to unsubscribe';

$string['publish_in'] = 'To be published in {$a->days} days, {$a->hours} hrs, {$a->minutes} min, {$a->seconds} sec.';
$string['already_published'] = 'This issue has been published.';
$string['delete_issue_question'] = 'Are you sure you want to delete this issue?';
$string['delete_subscription_question'] = 'Are you sure you want to delete this subscription?';
$string['no_issues'] = 'This newsletter has no issues yet.';

$string['edit_subscription_title'] = 'Edit subscription';
$string['subscribe'] = 'Subscribe';
$string['unsubscribe'] = 'Unsubscribe';

$string['allowguestusersubscriptions'] = 'Allow guest user subscription';
$string['allowguestusersubscriptions_help'] = 'Enable to allow guest users to subscribe to newsletters on this site. This will necessitate their creating user accounts.';
$string['welcomemessage'] = 'Welcome message';
$string['welcomemessage_help'] = 'This text will be presented to a user after he had enrolled successfully for a newsletter.';
$string['welcomemessageguestuser'] = 'Welcome message guest user';
$string['welcomemessageguestuser_help'] = 'This text will be displayed to a new user after he had enrolled successfully AS A GUESTUSER for a newsletter.';

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

$string['emailexists'] = 'A user account with this e-mail address already exists. Please login in order to subscribe to the newsletter. If you forgot your login, use the {$a} link on the login page to recover it.';
$string['guestsubscriptionsuccess'] = 'Your email was successfully registered. <br /> In order to activate the subscription, please check the inbox of your mailaccount ({$a}) and click on the confirmation link';
$string['resubscriptionsuccess'] = 'You have been sucessfully re-subscribed.';

$string['readonline'] = 'View web version';
$string['send_newsletter'] = 'Send newsletter';
$string['process_bounces'] = 'Process bounced e-mails';
$string['subscribercandidatesmatching'] = 'Matching users for ({$a})';
$string['subscribercandidates'] = 'Potential subscribers';
$string['subscribedusersmatching'] = 'Subscribed users matching ({$a})';
$string['subscribedusers'] = 'Subscribed users';
$string['cohortmanagement'] = 'Subscribe/Unsubscribe cohorts';
$string['cohortsavailable'] = 'Available cohorts';
$string['welcomeredirect'] = 'Welcome! You will be redirected to the login page shortly.';
$string['welcometonewsletter'] = 'Welcome to the newsletter';
$string['welcometonewsletter_guestsubscription'] = 'Welcome to the newsletter!<br />You can unsubscribe anytime by clicking on the unsubscribe-button after login or on the unsubscribe link in every newsletter issue.';
$string['unsubscribedinfo'] = 'Users marked with (!) are unsubscribed';
$string['toc_header'] = 'Table of Content';
$string['toc_no'] = 'Do not autogenerate a table of content';
$string['toc_yes'] = 'Use {$a} headline level(s)';
$string['toc'] = 'Select how to autogenerate the table of content';
$string['toc_help'] = 'This is the number of headline levels to include. Example: You have a newsletter issue with three different headline levels (h1, h2, h3). Then you could choose to only use the most important headlines for the table of content. Then you choose to select only 1 headline level. If you want also to inclide h2 in the table of content, choose 2 levels.';
$string['unsubscribe_mail_subj'] = 'You were successfully unsubscribed from the newsletter';
$string['unsubscribe_mail_text'] = '<p>
Dear {$a->firstname} {$a->lastname},
<br>
You were successfully unsubscribed from the newsletter {$a->newslettertitle}. If you did this on purpose, there is nothing more to do. If you did accidentally unsubscribe, you can resubscribe now under the following link:
<br>
{$a->newsletterurl}</p>';
$string['unsubscribe_nounsub_text'] = 'Don\'t send an unsubscribe link.';
$string['unsubscribe_nounsub'] = 'Distributor';

// Privacy API.
$string['privacy:metadata:newsletter_subscriptions'] = 'Represent a subscription to a newsletter.';
$string['privacy:metadata:newsletter_subscriptions:userid'] = 'User who created the record';
$string['privacy:metadata:newsletter_subscriptions:newsletterid'] = 'ID of the subscribed newsletter';
$string['privacy:metadata:newsletter_subscriptions:health'] = 'Health of the subscription to store if errors were found';
$string['privacy:metadata:newsletter_subscriptions:timesubscribed'] = 'Timestamp when subscribed';
$string['privacy:metadata:newsletter_subscriptions:timestatuschanged'] = 'Timestamp of last change';
$string['privacy:metadata:newsletter_subscriptions:subscriberid'] = 'ID of user who subscribed';
$string['privacy:metadata:newsletter_subscriptions:unsubscriberid'] = 'ID of user who unsubscribed';
$string['privacy:metadata:newsletter_subscriptions:sentnewsletters'] = 'Number of newsletters already sent';
$string['privacy:metadata:newsletter_bounces'] = 'Represent newsletters that we received a bounce from the server.';
$string['privacy:metadata:newsletter_bounces:userid'] = 'User who created the record';
$string['privacy:metadata:newsletter_bounces:issueid'] = 'What newsletter was bounced';
$string['privacy:metadata:newsletter_bounces:statuscode'] = 'Statuscode we received';
$string['privacy:metadata:newsletter_bounces:timecreated'] = 'Timestamp when we created the entry';
$string['privacy:metadata:newsletter_bounces:type'] = 'Type we received';
$string['privacy:metadata:newsletter_bounces:timereceived'] = 'Timestamp when we received the bounce';
$string['privacy:metadata:newsletter_deliveries'] = 'Represent newsletters we have sent to users.';
$string['privacy:metadata:newsletter_deliveries:userid'] = 'User who received the newsletter';
$string['privacy:metadata:newsletter_deliveries:issueid'] = 'ID of the issue we sent';
$string['privacy:metadata:newsletter_deliveries:newsletterid'] = 'ID of the newsletter we sent';
$string['privacy:metadata:newsletter_deliveries:delivered'] = 'Timestamp of newsletter delivery';
