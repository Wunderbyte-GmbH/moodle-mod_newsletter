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
 * Subscribe and unsubscribe script for the newsletter module.
 *
 * @package    mod_newsletter
 * @copyright  2013 Ivan Sakic <ivan.sakic3@gmail.com>
 * @copyright  2015 onwards David Bogner <info@edulabs.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Unsubscribe links are followed from e-mails, so this page must stay reachable to logged-out visitors.
// phpcs:ignore moodle.Files.RequireLogin.Missing
require_once(dirname(__FILE__, 3) . '/config.php');
require_once(__DIR__ . '/lib.php');
$id = required_param(NEWSLETTER_PARAM_ID, PARAM_INT);
$user = optional_param(NEWSLETTER_PARAM_USER, 0, PARAM_INT);
$confirm = optional_param(NEWSLETTER_PARAM_CONFIRM, NEWSLETTER_CONFIRM_UNKNOWN, PARAM_INT);
$secret = optional_param(NEWSLETTER_PARAM_HASH, false, PARAM_TEXT);

// Unsubscribe links are followed anonymously, so resolve the target user from the link parameter.
// Guest signup accounts are confirmed through confirm.php, not here.
if ($user) {
    $user = $DB->get_record('user', ['id' => $user], '*', MUST_EXIST);
} else {
    $user = $USER;
}

$url = new moodle_url('/mod/newsletter/subscribe.php', ['id' => $id]);
$PAGE->set_url($url);
$coursemodule = get_coursemodule_from_id('newsletter', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $coursemodule->course], '*', MUST_EXIST);

$PAGE->set_context(context_system::instance()); // No login required.
$context = context_module::instance($coursemodule->id);

$newsletter = mod_newsletter\newsletter::get_newsletter_by_course_module($id);

if ($newsletter->is_subscribed($user->id)) {
    if ($confirm == NEWSLETTER_CONFIRM_UNKNOWN) {
        echo $OUTPUT->header();
        // Post the secret to the confirm step.
        if ($secret === md5($user->id . "+" . $user->firstaccess)) {
            echo $OUTPUT->confirm(
                get_string(
                    'unsubscribe_question',
                    'newsletter',
                    ['name' => $newsletter->get_instance()->name,
                    'email' => $user->email]
                ),
                new moodle_url(
                    $url,
                    [NEWSLETTER_PARAM_USER => $user->id,
                                NEWSLETTER_PARAM_HASH => $secret,
                    NEWSLETTER_PARAM_CONFIRM => NEWSLETTER_CONFIRM_YES]
                ),
                new moodle_url(
                    $url,
                    [NEWSLETTER_PARAM_USER => $user->id,
                    NEWSLETTER_PARAM_CONFIRM => NEWSLETTER_CONFIRM_NO]
                )
            );
        } else {
            echo \core\notification::error('You used an invalid unsubscription link');
        }
        echo $OUTPUT->footer();
    } else if ($confirm == NEWSLETTER_CONFIRM_YES) {
        // Check if secret value is correct.
        // NOTE: this makes all older unsub links invalid.
        if ($secret === md5($user->id . "+" . $user->firstaccess)) {
            $subscriptionid = $newsletter->get_subid($user->id);
            $newsletter->unsubscribe($subscriptionid);
            // Send mail to user just to be sure.
            $a = new stdClass();
            $a->firstname = $user->firstname;
            $a->lastname = $user->lastname;
            $a->newsletterurl = $newsletter->get_subsribe_url()->out();
            $a->newslettertitle = $newsletter->get_instance()->name;
            $unsubsubj = get_string('unsubscribe_mail_subj', 'newsletter');
            $unsubtext = get_string('unsubscribe_mail_text', 'newsletter', $a);
            email_to_user(
                $user,
                core_user::get_support_user(),
                $unsubsubj,
                html_to_text($unsubtext),
                $unsubtext,
                '',
                '',
                false
            );
            echo $OUTPUT->header();
            $stringparams = ['name' => $newsletter->get_instance()->name,
                'email' => $user->email];
            echo $OUTPUT->box(
                get_string('unsubscription_succesful', 'newsletter', $stringparams),
                'mdl-align'
            );
            echo $OUTPUT->continue_button(
                new moodle_url('/mod/newsletter/view.php', ['id' => $id])
            );
            echo $OUTPUT->footer();
        } else {
            redirect(new moodle_url('/mod/newsletter/view.php', ['id' => $id]));
        }
    } else if ($confirm == NEWSLETTER_CONFIRM_NO) {
        redirect(new moodle_url('/mod/newsletter/view.php', ['id' => $id]));
    } else {
        throw new \moodle_exception('Wrong ' . NEWSLETTER_PARAM_CONFIRM . ' code: ' . $confirm . '!');
    }
} else {
    require_capability('mod/newsletter:viewnewsletter', $context);
    if ($confirm == NEWSLETTER_CONFIRM_UNKNOWN) {
        echo $OUTPUT->header();
        $urlparams = [NEWSLETTER_PARAM_USER => $user->id,
            NEWSLETTER_PARAM_CONFIRM => NEWSLETTER_CONFIRM_YES];
        $urlparams2 = [NEWSLETTER_PARAM_USER => $user->id,
            NEWSLETTER_PARAM_CONFIRM => NEWSLETTER_CONFIRM_NO];
        echo $OUTPUT->confirm(
            get_string(
                'subscribe_question',
                'newsletter',
                ['name' => $newsletter->get_instance()->name, 'email' => $user->email]
            ),
            new moodle_url($url, $urlparams),
            new moodle_url($url, $urlparams2)
        );
        echo $OUTPUT->footer();
    } else if ($confirm == NEWSLETTER_CONFIRM_YES) {
        $newsletter->subscribe($user->id);
        redirect(new moodle_url('/mod/newsletter/view.php', ['id' => $id]));
    } else if ($confirm == NEWSLETTER_CONFIRM_NO) {
        redirect(new moodle_url('/mod/newsletter/view.php', ['id' => $id]));
    } else {
        throw new \moodle_exception(
            'Wrong ' . NEWSLETTER_PARAM_CONFIRM . ' code: ' . $confirm . '!',
            'mod_newsletter'
        );
    }
}

die();
