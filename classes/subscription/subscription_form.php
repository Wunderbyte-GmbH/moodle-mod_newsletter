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
 * The form for creating and editing newsletter issues
 *
 * @package    mod_newsletter
 * @copyright  2013 Ivan Šakić <ivan.sakic3@gmail.com>
 * @copyright  2015 onwards David Bogner <info@edulabs.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_newsletter\subscription;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');


class mod_newsletter_subscription_form extends \moodleform {

    /**
     * Defines forms elements
     */
    public function definition() {
        $mform = &$this->_form;
        $data = &$this->_customdata;

        $newsletter = $data['newsletter'];
        $subscription = $data['subscription'];

        $mform->addElement('hidden', 'id', $newsletter->get_course_module()->id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'subscription', $subscription ? $subscription->id : 0);
        $mform->setType('subscription', PARAM_INT);
        $mform->addElement('hidden', 'userid', $subscription ? $subscription->userid : 0);
        $mform->setType('userid', PARAM_INT);
        $mform->addElement('hidden', 'action', NEWSLETTER_ACTION_EDIT_SUBSCRIPTION);
        $mform->setType('action', PARAM_ALPHA);
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('static', 'email', get_string('header_email', 'mod_newsletter'), '');
        $mform->addElement('static', 'name', get_string('header_name', 'mod_newsletter'), '');
        $mform->addElement('static', 'timesubscribed',
                get_string('header_timesubscribed', 'mod_newsletter'), '');
        $mform->addElement('static', 'timestatuschanged',
                get_string('header_timestatuschanged', 'mod_newsletter'), '');
        $mform->addElement('static', 'subscriberid',
                get_string('header_subscriberid', 'mod_newsletter'), '');
        $mform->addElement('static', 'unsubscriberid',
                get_string('header_unsubscriberid', 'mod_newsletter'), '');

        $options = array(NEWSLETTER_SUBSCRIBER_STATUS_OK => get_string("health_0", 'newsletter'),
            NEWSLETTER_SUBSCRIBER_STATUS_PROBLEMATIC => get_string("health_1", 'newsletter'),
            NEWSLETTER_SUBSCRIBER_STATUS_BLACKLISTED => get_string("health_2", 'newsletter'),
            NEWSLETTER_SUBSCRIBER_STATUS_UNSUBSCRIBED => get_string("health_4", 'newsletter'));

        $mform->addElement('select', 'health', get_string('header_health', 'mod_newsletter'),
                $options);
        $mform->setType('health', PARAM_INT);

        // Checkbox to not send unsublink. #31.
        $mform->addElement('advcheckbox', 'nounsublink',
                get_string('unsubscribe_nounsub', 'mod_newsletter'),
                get_string('unsubscribe_nounsub_text', 'mod_newsletter'), '', array(0, 1));

        $this->add_action_buttons();

        if ($subscription) {
            global $DB;
            $user = $DB->get_record('user', array('id' => $subscription->userid));
            $values = new \stdClass();
            $values->email = $user->email;
            $values->name = fullname($user);
            $values->health = $subscription->health;
            $values->timesubscribed = userdate($subscription->timesubscribed,
                    get_string('strftimedatefullshort'));
            $values->timestatuschanged = userdate($subscription->timestatuschanged,
                    get_string('strftimedatefullshort'));
            if ($subscription->subscriberid != 0) {
                $values->subscriberid = fullname(
                        $DB->get_record('user', array('id' => $subscription->subscriberid), '*',
                                MUST_EXIST));
            } else {
                $values->subscriberid = "No data present";
            }
            $values->userid = $subscription->userid;
            $values->unsubscriberid = $subscription->unsubscriberid;
            $values->nounsublink = $subscription->nounsublink; // Extend nounsublink #31.
            $this->set_data($values);
        }
    }
}
