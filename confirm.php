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

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot . '/mod/newsletter/lib.php');

$data = required_param(NEWSLETTER_PARAM_DATA, PARAM_RAW); // Formatted as: secret-userid.
$dataelements = explode('-', $data, 4);
$secret = clean_param($dataelements[0], PARAM_ALPHANUM);
$userid = clean_param($dataelements[1], PARAM_INT);
$newsletterid = clean_param($dataelements[2], PARAM_INT);
if (count($dataelements) == 4) {
    if ($dataelements[3] == "guest") {
        $guestuser = 1;
    }
}

$cm = get_coursemodule_from_instance('newsletter', $newsletterid);
$context = context_module::instance($cm->id);
$PAGE->set_context($context);

if ($secret && $userid) {
    if (!$user = get_complete_user_data('id', $userid)) {
        print_error("Cannot find user!");
    }
    if ($user->confirmed) {
        redirect(new moodle_url('/mod/newsletter/view.php', array('id' => $cm->id)),
                "You are already registered and subscribed!", 5);
        // TODO: user/editadvanced.php?id=2.
    } else {
        if ($secret == $user->secret) {
            global $DB;
            $DB->set_field('user', 'confirmed', 1, array('id' => $user->id));
            complete_user_login($user);
            if (!isset($guestuser)) {
                if (!$welcomemessage = $DB->get_field('newsletter', 'welcomemessage',
                        array('id' => $newsletterid))) {
                    $welcomemessage = get_string('welcometonewsletter', 'mod_newsletter');
                }
            } else {
                if (!$welcomemessage = $DB->get_field('newsletter', 'welcomemessageguestuser',
                        array('id' => $newsletterid))) {
                    $welcomemessage = get_string('welcometonewsletter_guestsubscription',
                            'mod_newsletter');
                }
            }
            redirect(new moodle_url('/mod/newsletter/view.php', array('id' => $cm->id)),
                    $welcomemessage, 15);
            // TODO: user/editadvanced.php?id=2.
        } else {
            print_error('The link you followed is invalid.');
        }
    }
} else {
    print_error('The link you followed is invalid.');
}
