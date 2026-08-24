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
 * Subscription confirmation script for newsletter module
 *
 * @package    mod_newsletter
 * @copyright  2013 Ivan Šakić <ivan.sakic3@gmail.com>
 * @copyright  2015 onwards David Bogner <info@edulabs.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This page is reached from an activation e-mail, so it must stay accessible to logged-out visitors.
// phpcs:ignore moodle.Files.RequireLogin.Missing
require_once(dirname(__FILE__, 3) . '/config.php');
require_once($CFG->dirroot . '/mod/newsletter/lib.php');

// Formatted as: secret-userid-newsletterid[-guest].
$data = required_param(NEWSLETTER_PARAM_DATA, PARAM_ALPHANUMEXT);
$dataelements = explode('-', $data, 4);

if (count($dataelements) < 3) {
    throw new moodle_exception('invalidactivationlink', 'mod_newsletter');
}

$secret = clean_param($dataelements[0], PARAM_ALPHANUM);
$userid = clean_param($dataelements[1], PARAM_INT);
$newsletterid = clean_param($dataelements[2], PARAM_INT);
$guestuser = (count($dataelements) === 4 && $dataelements[3] === 'guest');

if (!$secret || !$userid || !$newsletterid) {
    throw new moodle_exception('invalidactivationlink', 'mod_newsletter');
}

$cm = get_coursemodule_from_instance('newsletter', $newsletterid, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);
$viewurl = new moodle_url('/mod/newsletter/view.php', ['id' => $cm->id]);

$PAGE->set_context($context);
$PAGE->set_url('/mod/newsletter/confirm.php', [NEWSLETTER_PARAM_DATA => $data]);

if (!$user = get_complete_user_data('id', $userid)) {
    throw new moodle_exception('invalidactivationlink', 'mod_newsletter');
}

if ($user->confirmed) {
    redirect($viewurl, get_string('accountalreadyconfirmed', 'mod_newsletter'), 5);
}

// Guest signup links expire, and the cleanup task removes the account once they do. Refuse a stale link
// explicitly instead of letting it work right up until the moment cron happens to run. The timeout is
// measured from account creation, so it only makes sense for the accounts this module created itself.
if ($guestuser) {
    $timeout = get_config('mod_newsletter', 'activation_timeout');
    $timeout = !empty($timeout) ? (int) $timeout : DAYSECS;
    if (!empty($user->timecreated) && (time() - $user->timecreated) > $timeout) {
        throw new moodle_exception('activationlinkexpired', 'mod_newsletter');
    }
}

// Let the auth plugin confirm the account rather than writing to the user table directly.
$authplugin = get_auth_plugin($user->auth);
if ($authplugin->user_confirm($user->username, $secret) !== AUTH_CONFIRM_OK) {
    throw new moodle_exception('invalidactivationlink', 'mod_newsletter');
}

$user = get_complete_user_data('id', $userid);
complete_user_login($user);

if ($guestuser) {
    $welcomemessage = $DB->get_field('newsletter', 'welcomemessageguestuser', ['id' => $newsletterid]);
    $default = get_string('welcometonewsletter_guestsubscription', 'mod_newsletter');
} else {
    $welcomemessage = $DB->get_field('newsletter', 'welcomemessage', ['id' => $newsletterid]);
    $default = get_string('welcometonewsletter', 'mod_newsletter');
}

redirect($viewurl, !empty($welcomemessage) ? $welcomemessage : $default, 15);
