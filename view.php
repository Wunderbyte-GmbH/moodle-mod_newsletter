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
 * Prints a particular instance of newsletter
 *
 * @package    mod_newsletter
 * @copyright  2013 Ivan Šakić <ivan.sakic3@gmail.com>
 * @copyright  2015 onwards David Bogner <info@edulabs.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot . '/mod/newsletter/lib.php');
require_once($CFG->dirroot . '/mod/newsletter/locallib.php');

$id = required_param(NEWSLETTER_PARAM_ID, PARAM_INT);

$newsletter = mod_newsletter::get_newsletter_by_course_module($id,true);

$coursemodule = $newsletter->get_course_module();
$course = $newsletter->get_course();

require_course_login($course, true, $coursemodule);

$url = new moodle_url('/mod/newsletter/view.php', array(NEWSLETTER_PARAM_ID => $id));
$PAGE->set_url($url);

$params = array(
    NEWSLETTER_PARAM_ACTION => optional_param(NEWSLETTER_PARAM_ACTION, NEWSLETTER_ACTION_VIEW_NEWSLETTER, PARAM_ALPHA),
    NEWSLETTER_PARAM_GROUP_BY => optional_param(NEWSLETTER_PARAM_GROUP_BY, get_user_preferences(NEWSLETTER_PREFERENCE_GROUP_BY, NEWSLETTER_GROUP_BY_DEFAULT), PARAM_ALPHA),
    NEWSLETTER_PARAM_ISSUE => optional_param(NEWSLETTER_PARAM_ISSUE, NEWSLETTER_NO_ISSUE, PARAM_INT),
    NEWSLETTER_PARAM_FROM => optional_param(NEWSLETTER_PARAM_FROM, NEWSLETTER_FROM_DEFAULT, PARAM_INT),
    NEWSLETTER_PARAM_COUNT => optional_param(NEWSLETTER_PARAM_COUNT, get_user_preferences(NEWSLETTER_PREFERENCE_COUNT, NEWSLETTER_COUNT_DEFAULT), PARAM_INT),
    NEWSLETTER_PARAM_TO => optional_param(NEWSLETTER_PARAM_TO, NEWSLETTER_TO_DEFAULT, PARAM_INT),
    NEWSLETTER_PARAM_SUBSCRIPTION => optional_param(NEWSLETTER_PARAM_SUBSCRIPTION, NEWSLETTER_SUBSCRIPTION_DEFAULT, PARAM_INT),
    NEWSLETTER_PARAM_CONFIRM => optional_param(NEWSLETTER_PARAM_CONFIRM, NEWSLETTER_CONFIRM_UNKNOWN, PARAM_INT),
    NEWSLETTER_PARAM_USER => optional_param(NEWSLETTER_PARAM_USER, NEWSLETTER_NO_USER, PARAM_INT),
    NEWSLETTER_PARAM_SEARCH => optional_param(NEWSLETTER_PARAM_SEARCH, '', PARAM_CLEAN),
    NEWSLETTER_PARAM_STATUS => optional_param(NEWSLETTER_PARAM_STATUS, 10, PARAM_INT),
	NEWSLETTER_PARAM_RESETBUTTON => optional_param(NEWSLETTER_PARAM_RESETBUTTON, '', PARAM_RAW),
	NEWSLETTER_PARAM_ORDERBY => optional_param(NEWSLETTER_PARAM_ORDERBY, '', PARAM_ALPHA),
);

if (get_user_preferences(NEWSLETTER_PREFERENCE_GROUP_BY, false) || $params[NEWSLETTER_PARAM_GROUP_BY] != NEWSLETTER_GROUP_BY_DEFAULT) {
    set_user_preference(NEWSLETTER_PREFERENCE_GROUP_BY, $params[NEWSLETTER_PARAM_GROUP_BY]);
}

if (get_user_preferences(NEWSLETTER_PREFERENCE_COUNT, false) ||  $params[NEWSLETTER_PARAM_COUNT] != NEWSLETTER_COUNT_DEFAULT) {
    set_user_preference(NEWSLETTER_PREFERENCE_COUNT, $params[NEWSLETTER_PARAM_COUNT]);
}

echo $newsletter->view($params);

die;
