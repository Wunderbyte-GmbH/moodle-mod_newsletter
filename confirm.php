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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

$data = required_param(NEWSLETTER_PARAM_DATA, PARAM_RAW);  // Formatted as:  secret-userid
$dataelements = explode('-', $data, 2); // Stop after 1st hyphen. Rest is userid.
$secret = clean_param($dataelements[0], PARAM_ALPHANUM);
$userid = clean_param($dataelements[1], PARAM_INT);

if ($secret && $userid) {
    if (!$user = get_complete_user_data('id', $userid)) {
        print_error("Cannot find user!");
    }
    global $DB;
    $newsletterid = $DB->get_field('newsletter_subscriptions', 'newsletterid', array('userid' => $userid));
    $cm = get_coursemodule_from_instance('newsletter', $newsletterid);
    if ($user->confirmed) {
        redirect(new moodle_url('/mod/newsletter/view.php', array('id' => $cm->id)), "You are already registered and subscribed!", 5);
        // TODO: user/editadvanced.php?id=2
    } else {
        if ($secret == $user->secret) {
            global $DB;
            $DB->set_field('user', 'confirmed', 1, array('id' => $user->id));
            complete_user_login($user);
            redirect(new moodle_url('/mod/newsletter/view.php', array('id' => $cm->id)), "Welcome!", 5);
            // TODO: user/editadvanced.php?id=2
        } else {
            print_error('The link you followed is invalid.');
        }
    }
} else {
    print_error('The link you followed is invalid.');
}
