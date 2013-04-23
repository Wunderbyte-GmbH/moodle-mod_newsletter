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
 * Administration settings definitions for the newsletter module.
 *
 * @package    mod_newsletter
 * @copyright  2013 Ivan Šakić <ivan.sakic3@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/newsletter/lib.php');

// Allowed newsletter content
$pickeroptions = array();
$pickeroptions[NEWSLETTER_CONTENT_PLAINTEXT_ONLY] = get_string('content_plaintext_only', 'newsletter');
$pickeroptions[NEWSLETTER_CONTENT_HTML_ONLY] = get_string('content_html_only', 'newsletter');
$pickeroptions[NEWSLETTER_CONTENT_ALL] = get_string('content_all', 'newsletter');

$settings->add(new admin_setting_configselect('newsletter/allowedcontent',
                                              get_string('config_allow_content_label', 'newsletter'),
                                              get_string('config_allow_content_desc', 'newsletter'),
                                              NEWSLETTER_CONTENT_ALL,
                                              $pickeroptions));

$settings->add(new admin_setting_configcheckbox('newsletter/debug',
                                                get_string('config_debug_label', 'newsletter'),
                                                get_string('config_debug_desc', 'newsletter'), 0));