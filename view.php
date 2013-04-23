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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot . '/mod/newsletter/lib.php');
require_once($CFG->dirroot . '/mod/newsletter/locallib.php');

$id = required_param('id', PARAM_INT);

$coursemodule = get_coursemodule_from_id('newsletter', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $coursemodule->course), '*', MUST_EXIST);

require_login($course, true, $coursemodule);
$context = context_module::instance($coursemodule->id);

require_capability('mod/newsletter:view', $context);

$url = new moodle_url('/mod/newsletter/view.php', array('id' => $id));
$PAGE->set_url($url);

$newsletter = new newsletter($context, $coursemodule, $course);

$params = array(
    NEWSLETTER_PARAM_ACTION => optional_param(NEWSLETTER_PARAM_ACTION, NEWSLETTER_ACTION_VIEW_ISSUES_PUBLISHER, PARAM_ALPHA),
    NEWSLETTER_PARAM_GROUP_BY => optional_param(NEWSLETTER_PARAM_GROUP_BY, NEWSLETTER_GROUP_ISSUES_BY_WEEK, PARAM_ALPHA),
    NEWSLETTER_PARAM_ISSUE => optional_param(NEWSLETTER_PARAM_ISSUE, NEWSLETTER_NO_ISSUE, PARAM_INT),
    NEWSLETTER_PARAM_FROM => optional_param(NEWSLETTER_PARAM_FROM, NEWSLETTER_FROM_DEFAULT, PARAM_INT),
    NEWSLETTER_PARAM_TO => optional_param(NEWSLETTER_PARAM_TO, NEWSLETTER_TO_DEFAULT, PARAM_INT),
);

echo $newsletter->view($params);

die;
