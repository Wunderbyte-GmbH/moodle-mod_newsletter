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

$string['edit_issue_title'] = 'Edit newsletter issue';
$string['issue_title'] = 'Issue title';
$string['issue_title_help'] = 'Type in the title of this issue. Required.';

$string['issue_plaincontent'] = 'Plaintext content';
$string['issue_htmlcontent'] = 'HTML content';
$string['issue_stylesheet'] = 'Stylesheet file for HTML content';

$string['mode_group_by_year'] = 'Group issues by year';
$string['mode_group_by_month'] = 'Group issues by month';
$string['mode_group_by_week'] = 'Group issues by week';

$string['delete_all_subscriptions'] = 'Delete all subscriptions';

$string['content_plaintext_only'] = 'Plaintext only';
$string['content_html_only'] = 'HTML only';
$string['content_all'] = 'HTML and plaintext';

$string['allowedcontent'] = 'Allowed content type';

$string['publishon'] = 'Publish on';
$string['header_content'] = 'Issue content';
$string['header_publish'] = 'Publishing options';

$string['config_allow_content_label'] = 'Allowed content type';
$string['config_allow_content_desc'] = 'Select the content type that will be allowed to be sent in the newsletter.';
$string['config_debug_label'] = 'Cron DEBUG mode';
$string['config_debug_desc'] = 'Check this box to enable debug output for the newsletter cron job.';

$string['default_stylesheet'] = 'Default stylesheet';