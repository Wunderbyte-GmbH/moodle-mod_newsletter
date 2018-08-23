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

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/locallib.php');

$id = required_param(NEWSLETTER_PARAM_ID, PARAM_INT);
$user = optional_param(NEWSLETTER_PARAM_USER, 0, PARAM_INT);
$confirm = optional_param(NEWSLETTER_PARAM_CONFIRM, NEWSLETTER_CONFIRM_UNKNOWN, PARAM_INT);
$secret = optional_param(NEWSLETTER_PARAM_HASH, false, PARAM_TEXT);

// Create a new user if the user has used the guest subscription form.
if ($user) {
    global $DB;
    $select = " userid = $user AND (health = " . NEWSLETTER_SUBSCRIBER_STATUS_OK . " OR health = " . NEWSLETTER_SUBSCRIBER_STATUS_PROBLEMATIC .")";
    $sub = $DB->record_exists_select('newsletter_subscriptions', $select );
    // If we find a subscription we assume the user exists.
    if ($sub && $secret) {
        $user = $DB->get_record('user', array('id' => $user), '*', MUST_EXIST);
        if ($secret == $user->secret) {
            // The secret is used to confirm users, old behaviour.
            if ($confirm == NEWSLETTER_CONFIRM_YES) {
                $DB->set_field('user', 'confirmed', 1, array('id' => $user));
                redirect(new moodle_url('/mod/newsletter/view.php', array('id' => $id)), get_string('welcomeredirec','mod_newsletter'), 5);
            } else if ($confirm == NEWSLETTER_CONFIRM_NO) {
                $DB->delete_records('newsletter_subscriptions', array('userid' => $user));
                $user = $DB->get_record('user', array('id' => $user));
                user_delete_user($user);
                redirect(new moodle_url('/mod/newsletter/view.php', array('id' => $id)), "The creation of your account was cancelled at your request!", 5);
            } else {
                print_error('The link you followed is invalid.');
            }
        } else if ($secret != md5($user->id . "+" . $user->firstaccess)) {
            // The secret is not used to unsubscribe from a newsletter, we don't know what to do.
            print_error('The link you followed is invalid.');
        }
    }
} else {
    $user = $USER;
}

$url = new moodle_url('/mod/newsletter/subscribe.php', array('id' => $id));
$PAGE->set_url($url);
$coursemodule = get_coursemodule_from_id('newsletter', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $coursemodule->course), '*', MUST_EXIST);

// require_login($course, false, $coursemodule);
$PAGE->set_context(context_system::instance()); // No login required.
$context = context_module::instance($coursemodule->id);

require_capability('mod/newsletter:viewnewsletter', $context);

$newsletter = mod_newsletter::get_newsletter_by_course_module($id);

if ($newsletter->is_subscribed($user->id)) {
    if($confirm == NEWSLETTER_CONFIRM_UNKNOWN) {
        echo $OUTPUT->header();
        // Post the secret to the confirm step.
        echo $OUTPUT->confirm(get_string('unsubscribe_question', 'newsletter', array(
            'name' => $newsletter->get_instance()->name, 'email' => $user->email)),
            new moodle_url($url, array(NEWSLETTER_PARAM_USER => $user->id, NEWSLETTER_PARAM_HASH => $secret,
                NEWSLETTER_PARAM_CONFIRM => NEWSLETTER_CONFIRM_YES)),
            new moodle_url($url, array(NEWSLETTER_PARAM_USER => $user->id,
                NEWSLETTER_PARAM_CONFIRM => NEWSLETTER_CONFIRM_NO)));
        echo $OUTPUT->footer();
    } else if($confirm == NEWSLETTER_CONFIRM_YES) {
        // Check if secret value is correct.
        if($secret == md5($user->id . "+" . $user->firstaccess)) {
            $subscriptionid = $newsletter->get_subid($user->id);
            $newsletter->unsubscribe($subscriptionid);
            // TODO: Send mail to user just to be sure.
            echo $OUTPUT->header();
            echo $OUTPUT->box(get_string('unsubscription_succesful','newsletter', array('name' => $newsletter->get_instance()->name, 'email' => $user->email)),'mdl-align');
            echo $OUTPUT->continue_button(new moodle_url('/mod/newsletter/view.php', array('id' => $id)));
            echo $OUTPUT->footer();
        } else {
            redirect(new moodle_url('/mod/newsletter/view.php', array('id' => $id)));
        }
    } else if($confirm == NEWSLETTER_CONFIRM_NO) {
        redirect(new moodle_url('/mod/newsletter/view.php', array('id' => $id)));
    } else {
        print_error('Wrong ' . NEWSLETTER_PARAM_CONFIRM . ' code: ' . $confirm . '!');
    }
} else {
    if($confirm == NEWSLETTER_CONFIRM_UNKNOWN) {
        echo $OUTPUT->header();
        echo $OUTPUT->confirm(get_string('subscribe_question', 'newsletter', array('name' => $newsletter->get_instance()->name, 'email' => $user->email)),
                new moodle_url($url, array(NEWSLETTER_PARAM_USER => $user->id, NEWSLETTER_PARAM_CONFIRM => NEWSLETTER_CONFIRM_YES)),
                new moodle_url($url, array(NEWSLETTER_PARAM_USER => $user->id, NEWSLETTER_PARAM_CONFIRM => NEWSLETTER_CONFIRM_NO)));
        echo $OUTPUT->footer();
    } else if($confirm == NEWSLETTER_CONFIRM_YES) {
        $newsletter->subscribe($user->id);
        redirect(new moodle_url('/mod/newsletter/view.php', array('id' => $id)));
    } else if($confirm == NEWSLETTER_CONFIRM_NO) {
        redirect(new moodle_url('/mod/newsletter/view.php', array('id' => $id)));
    } else {
        print_error('Wrong ' . NEWSLETTER_PARAM_CONFIRM . ' code: ' . $confirm . '!');
    }
}

die;
