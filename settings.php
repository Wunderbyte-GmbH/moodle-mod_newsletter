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
 * @copyright  2015 onwards David Bogner <info@edulabs.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/newsletter/lib.php');

$settings->add(new admin_setting_configcheckbox('newsletter/debug',
                                                get_string('config_debug_label', 'newsletter'),
                                                get_string('config_debug_desc', 'newsletter'), 0));

$settings->add(new admin_setting_configcheckbox('newsletter/allow_guest_user_subscriptions',
                                                get_string('allow_guest_user_subscriptions_label', 'newsletter'),
                                                get_string('allow_guest_user_subscriptions_desc', 'newsletter'), 0));

$settings->add(new admin_setting_configcheckbox('newsletter/send_notifications',
                                                get_string('config_send_notifications_label', 'newsletter'),
                                                get_string('config_send_notifications_desc', 'newsletter'), 0));

$options = array();
$day = 60 * 60 * 24;
for ($i = 1; $i < 8; $i++) {
    $options[$day * $i] = $i;
}

$settings->add(new admin_setting_configselect('newsletter/activation_timeout',
                                                get_string('config_activation_timeout_label', 'newsletter'),
                                                get_string('config_activation_timeout_desc', 'newsletter'), $day, $options));