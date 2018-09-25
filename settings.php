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

$settings->add(
        new admin_setting_configcheckbox('mod_newsletter/debug',
                get_string('config_debug_label', 'mod_newsletter'),
                get_string('config_debug_desc', 'mod_newsletter'), 0));
$settings->add(
        new admin_setting_configcheckbox('mod_newsletter/send_notifications',
                get_string('config_send_notifications_label', 'mod_newsletter'),
                get_string('config_send_notifications_desc', 'mod_newsletter'), 0));

$options = array();
$day = 60 * 60 * 24;
for ($i = 1; $i < 8; $i++) {
    $options[$day * $i] = $i;
}

$settings->add(
        new admin_setting_configselect('mod_newsletter/activation_timeout',
                get_string('config_activation_timeout_label', 'mod_newsletter'),
                get_string('config_activation_timeout_desc', 'mod_newsletter'), $day, $options));
$url = new moodle_url($CFG->wwwroot . "/mod/newsletter/bouncetest.php");
$a = '<a href="' . $url->out() . '">' . $url->out() . '</a>';
$settings->add(
        new admin_setting_heading('mod_newsletter/bounce',
                get_string('config_bounceprocessing', 'mod_newsletter'),
                get_string('config_bounceinfo', 'mod_newsletter', $a)));

$settings->add(
        new admin_setting_configcheckbox('mod_newsletter/enablebounce',
                get_string('config_bounce_enable', 'mod_newsletter'), '', 0));
$settings->add(
        new admin_setting_configtext('mod_newsletter/host',
                get_string('config_host', 'mod_newsletter'), '', '', PARAM_HOST));
$settings->add(
        new admin_setting_configtext('mod_newsletter/bounceemail',
                get_string('config_bounce_email', 'mod_newsletter'), '', '', PARAM_EMAIL));
$settings->add(
        new admin_setting_configtext('mod_newsletter/username',
                get_string('config_username', 'mod_newsletter'), '', '', PARAM_TEXT));
$settings->add(
        new admin_setting_configpasswordunmask('mod_newsletter/password',
                get_string('config_password', 'mod_newsletter'), '', '', PARAM_TEXT));
$settings->add(
        new admin_setting_configselect('mod_newsletter/service',
                get_string('config_service', 'mod_newsletter'), '', '',
                array('pop3' => 'pop3', 'imap' => 'imap')));
$settings->add(
        new admin_setting_configselect('mod_newsletter/service_option',
                get_string('config_service_option', 'mod_newsletter'), '', '',
                array('none' => 'none', 'tls' => 'tls', 'ssl' => 'ssl', 'notls' => 'notls')));
$settings->add(
        new admin_setting_configtext('mod_newsletter/port',
                get_string('config_port', 'mod_newsletter'), '', null, PARAM_INT));
