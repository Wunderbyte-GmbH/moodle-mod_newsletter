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
 * Library of interface functions and constants for module newsletter
 *
 * @package    mod_newsletter
 * @copyright  2013 Ivan Šakić <ivan.sakic3@gmail.com>
 * @copyright  2015 onwards David Bogner <info@edulabs.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_newsletter\userfilter;

defined('MOODLE_INTERNAL') || die();

// Newsletter internal constants.

define('NEWSLETTER_CRON_TEMP_FILENAME', 'newsletter_cron.tmp');
define('NEWSLETTER_LOCK_DIR', $CFG->dataroot . '/temp/mod/newsletter');
define('NEWSLETTER_LOCK_SUFFIX', 'lock');
define('NEWSLETTER_TEMP_DIR', NEWSLETTER_LOCK_DIR);
define('NEWSLETTER_BASE_STYLESHEET_PATH', 'reset.css');

define('NEWSLETTER_FILE_AREA_STYLESHEET', 'stylesheets');
define('NEWSLETTER_FILE_AREA_ATTACHMENT', 'attachments');
define('NEWSLETTER_FILE_AREA_ISSUE', 'issue');

define('NEWSLETTER_FILE_OPTIONS_SUBDIRS', 0);

define('NEWSLETTER_DELIVERY_STATUS_UNKNOWN', 0);
define('NEWSLETTER_DELIVERY_STATUS_DELIVERED', 1);
define('NEWSLETTER_DELIVERY_STATUS_INPROGRESS', 2);
define('NEWSLETTER_DELIVERY_STATUS_FAILED', 3);

define('NEWSLETTER_SUBSCRIBER_STATUS_OK', 0);
define('NEWSLETTER_SUBSCRIBER_STATUS_PROBLEMATIC', 1);
define('NEWSLETTER_SUBSCRIBER_STATUS_BLACKLISTED', 2);
define('NEWSLETTER_SUBSCRIBER_STATUS_UNSUBSCRIBED', 4);

define('NEWSLETTER_ACTION_VIEW_NEWSLETTER', 'view');
define('NEWSLETTER_ACTION_CREATE_ISSUE', 'createissue');
define('NEWSLETTER_ACTION_DUPLICATE_ISSUE', 'duplicateissue');
define('NEWSLETTER_ACTION_EDIT_ISSUE', 'editissue');
define('NEWSLETTER_ACTION_READ_ISSUE', 'readissue');
define('NEWSLETTER_ACTION_DELETE_ISSUE', 'deleteissue');
define('NEWSLETTER_ACTION_MANAGE_SUBSCRIPTIONS', 'managesubscriptions');
define('NEWSLETTER_ACTION_EDIT_SUBSCRIPTION', 'editsubscription');
define('NEWSLETTER_ACTION_DELETE_SUBSCRIPTION', 'deletesubscription');
define('NEWSLETTER_ACTION_SUBSCRIBE_COHORTS', 'subscribecohorts');
define('NEWSLETTER_ACTION_SUBSCRIBE', 'subscribe');
define('NEWSLETTER_ACTION_UNSUBSCRIBE', 'unsubscribe');
define('NEWSLETTER_ACTION_GUESTSUBSCRIBE', 'guestsubscribe');

define('NEWSLETTER_GROUP_ISSUES_BY_YEAR', 'year');
define('NEWSLETTER_GROUP_ISSUES_BY_MONTH', 'month');
define('NEWSLETTER_GROUP_ISSUES_BY_WEEK', 'week');
define('NEWSLETTER_GROUP_ISSUES_BY_DAY', 'day');

define('NEWSLETTER_SUBSCRIPTION_MODE_OPT_IN', 0);
define('NEWSLETTER_SUBSCRIPTION_MODE_OPT_OUT', 1);
define('NEWSLETTER_SUBSCRIPTION_MODE_FORCED', 2);
define('NEWSLETTER_SUBSCRIPTION_MODE_NONE', 3);

define('NEWSLETTER_NEW_USER', -1);

define('NEWSLETTER_NO_ISSUE', 0);
define('NEWSLETTER_NO_USER', 0);

define('NEWSLETTER_DEFAULT_STYLESHEET', 0);

define('NEWSLETTER_GROUP_BY_DEFAULT', NEWSLETTER_GROUP_ISSUES_BY_WEEK);
define('NEWSLETTER_FROM_DEFAULT', 0);
define('NEWSLETTER_COUNT_DEFAULT', 30);
define('NEWSLETTER_TO_DEFAULT', 0);
define('NEWSLETTER_SUBSCRIPTION_DEFAULT', 0);

define('NEWSLETTER_PREFERENCE_COUNT', 'newsletter_count');
define('NEWSLETTER_PREFERENCE_GROUP_BY', 'newsletter_group_by');

define('NEWSLETTER_PARAM_ID', 'id');
define('NEWSLETTER_PARAM_ACTION', 'action');
define('NEWSLETTER_PARAM_ISSUE', 'issue');
define('NEWSLETTER_PARAM_GROUP_BY', 'groupby');
define('NEWSLETTER_PARAM_FROM', 'from');
define('NEWSLETTER_PARAM_COUNT', 'count');
define('NEWSLETTER_PARAM_TO', 'to');
define('NEWSLETTER_PARAM_USER', 'user');
define('NEWSLETTER_PARAM_CONFIRM', 'confirm');
define('NEWSLETTER_PARAM_HASH', 'hash');
define('NEWSLETTER_PARAM_SUBSCRIPTION', 'sub');
define('NEWSLETTER_PARAM_DATA', 'data');
define('NEWSLETTER_PARAM_SEARCH', 'search');
define('NEWSLETTER_PARAM_STATUS', 'status');
define('NEWSLETTER_PARAM_RESETBUTTON', 'resetbutton');
define('NEWSLETTER_PARAM_ORDERBY', 'orderby');
define('NEWSLETTER_PARAM_ADD_SUBSCRIBERS', 'add');
define('NEWSLETTER_PARAM_REMOVE_SUBSCRIBERS', 'remove');
define('NEWSLETTER_PARAM_EMBED', 'embed');


define('NEWSLETTER_CONFIRM_YES', 1);
define('NEWSLETTER_CONFIRM_NO', 0);
define('NEWSLETTER_CONFIRM_UNKNOWN', -1);

define('NEWSLETTER_SUBSCRIPTION_LIST_COLUMN_EMAIL', 'col_email');
define('NEWSLETTER_SUBSCRIPTION_LIST_COLUMN_NAME', 'col_name');
define('NEWSLETTER_SUBSCRIPTION_LIST_COLUMN_HEALTH', 'col_health');
define('NEWSLETTER_SUBSCRIPTION_LIST_COLUMN_BOUNCERATIO', 'col_bounceratio');
define('NEWSLETTER_SUBSCRIPTION_LIST_COLUMN_TIMESUBSCRIBED', 'col_timesubscribed');
define('NEWSLETTER_SUBSCRIPTION_LIST_COLUMN_ACTIONS', 'col_actions');

// Moodle core API.

/**
 * Returns the information on whether the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function newsletter_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the newsletter.
 *
 * @param moodleform $mform form passed by reference
 */
function newsletter_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'newsletterheader',
            get_string('modulenameplural', 'mod_newsletter'));
    $name = get_string('delete_all_subscriptions', 'mod_newsletter');
    $mform->addElement('advcheckbox', 'reset_newsletter_subscriptions', $name);
}

/**
 * Course reset form defaults.
 *
 * @param object $course
 * @return array
 */
function newsletter_reset_course_form_defaults($course) {
    return array('reset_newsletter_subscriptions' => 1);
}

/**
 * Saves a new instance of the newsletter into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $newsletter An object from the form in mod_form.php
 * @param mod_newsletter_mod_form $mform
 * @return int The id of the newly inserted newsletter record
 */
function newsletter_add_instance(stdClass $data, mod_newsletter_mod_form $mform = null) {
    $newsletter = new mod_newsletter\newsletter(context_module::instance($data->coursemodule));
    return $newsletter->add_instance($data, $mform);
}

/**
 * Updates an instance of the newsletter in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $newsletter An object from the form in mod_form.php
 * @param mod_newsletter_mod_form $mform
 * @return boolean Success/Fail
 */
function newsletter_update_instance(stdClass $data, mod_newsletter_mod_form $mform = null) {
    $context = context_module::instance($data->coursemodule);
    $newsletter = new mod_newsletter\newsletter($context, false);
    return $newsletter->update_instance($data, $mform);
}

/**
 * Removes an instance of the newsletter from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function newsletter_delete_instance($id) {
    global $DB;

    if (!$newsletter = $DB->get_record('newsletter', array('id' => $id))) {
        return false;
    }

    $cm = get_coursemodule_from_instance('newsletter', $newsletter->id);
    $context = context_module::instance($cm->id);

    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_newsletter', NEWSLETTER_FILE_AREA_STYLESHEET,
            $newsletter->id);
    foreach ($files as $file) {
        $file->delete();
    }

    $issues = $DB->get_records('newsletter_issues', array('newsletterid' => $newsletter->id));
    foreach ($issues as $issue) {
        $files = $fs->get_area_files($context->id, 'mod_newsletter', NEWSLETTER_FILE_AREA_ATTACHMENT,
                $issue->id);
        foreach ($files as $file) {
            $file->delete();
        }
    }

    $DB->delete_records_list('newsletter_bounces', 'issueid', array_keys($issues));
    $DB->delete_records('newsletter_subscriptions', array('newsletterid' => $newsletter->id));
    $DB->delete_records('newsletter_issues', array('newsletterid' => $newsletter->id));
    $DB->delete_records('newsletter_deliveries', array('newsletterid' => $newsletter->id));
    $DB->delete_records('newsletter', array('id' => $newsletter->id));
    return true;
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * This function will remove all newsletter subscriptions in the database
 * and clean up any related data.
 *
 * @param \stdClass $data data submitted from the reset course.
 * @return array status array
 */
function newsletter_reset_userdata($data) {
    global $DB;
    $status = array();

    $sql = "SELECT n.id FROM {newsletter} n WHERE n.course = :courseid";
    $params = array('courseid' => $data->courseid);
    if ($newsletterids = $DB->get_fieldset_sql($sql, $params)) {
        foreach ($newsletterids as $newsletterid) {
            $newsletter = mod_newsletter\newsletter::get_newsletter_by_instance($newsletterid);
            $status = array_merge($status, $newsletter->reset_userdata($data));
        }
    }
    return $status;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return stdClass|null
 */
function newsletter_user_outline($course, $user, $mod, $newsletter) {
    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $newsletter the module instance record
 * @return void, is supposed to echo directly
 */
function newsletter_user_complete($course, $user, $mod, $newsletter) {
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in newsletter activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 */
function newsletter_print_recent_activity($course, $viewfullnames, $timestart) {
    return false; // True if anything was printed, otherwise false.
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link newsletter_print_recent_mod_activity()}.
 *
 * @param array $activities sequentially indexed array of objects with the 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 * @return void adds items into $activities and increases $index
 */
function newsletter_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid,
        $userid = 0, $groupid = 0) {
}

/**
 * Prints single activity item prepared by {@see newsletter_get_recent_mod_activity()}
 *
 * @return void
 */
function newsletter_print_recent_mod_activity($activity, $courseid, $detail, $modnames,
        $viewfullnames) {
}

/**
 * Given the id of the newsletter, returns all recipients who have an
 * acceptable health status
 * Also applies the new user filter and only returns filtered recipients.
 *
 * @param integer $newsletterid
 * @param string $userfilter // the new filter conditions as json string.
 * @return array of objects indexed by userid
 */
function newsletter_get_all_valid_recipients($newsletterid, $userfilter = null) {
    global $DB;
    $validstatuses = array(NEWSLETTER_SUBSCRIBER_STATUS_OK, NEWSLETTER_SUBSCRIBER_STATUS_PROBLEMATIC);
    $guestuserid = guest_user()->id;

    list($insql, $params) = $DB->get_in_or_equal($validstatuses, SQL_PARAMS_NAMED);
    $params['newsletterid'] = $newsletterid;
    $select = "SELECT ns.* ";
    $from = " FROM {newsletter_subscriptions} ns
        INNER JOIN {user} u ON ns.userid = u.id ";

    $where = "WHERE ns.newsletterid = :newsletterid
               AND u.confirmed = 1
               AND ns.health $insql
               AND u.id <> $guestuserid ";

    // Depending on the filter, we add the right sql code.
    if (!empty($userfilter)) {
        userfilter::add_sql($select, $from, $where, $params, $userfilter);
    }

    $sql = $select . $from . $where;
    return $DB->get_records_sql($sql, $params);
}

/**
 * Returns all other caps used in the module
 *
 * @example return array('moodle/site:accessallgroups');
 * @return array
 */
function newsletter_get_extra_capabilities() {
    return array();
}

// Find the base url from $_GET variables, for print_paging_bar.
function newsletter_get_baseurl() {
    $getcopy  = $_GET;

    unset($getcopy['blogpage']);

    if (!empty($getcopy)) {
        $first = false;
        $querystring = '';

        foreach ($getcopy as $var => $val) {
            if (!$first) {
                $first = true;
                $querystring .= "?$var=$val";
            } else {
                $querystring .= '&amp;'.$var.'='.$val;
                $hasparam = true;
            }
        }
    } else {
        $querystring = '?';
    }

    return strip_querystring(qualified_me()) . $querystring;

}
// File API.

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function newsletter_get_file_areas($course, $cm, $context) {
    return array(NEWSLETTER_FILE_AREA_ATTACHMENT => 'attachments',
        NEWSLETTER_FILE_AREA_STYLESHEET => 'stylesheets',
        NEWSLETTER_FILE_AREA_ISSUE => 'htmlcontent of editor');
}

/**
 * File browsing support for newsletter file areas
 *
 * @package mod_newsletter
 * @category files
 *
 * @param \file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return \file_info instance or null if not found
 */
function newsletter_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid,
        $filepath, $filename) {
    global $CFG, $DB, $USER;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return null;
    }

    // Filearea must contain a real area.
    if (!isset($areas[$filearea])) {
        return null;
    }

    // Note that newsletter_user_can_see_post() additionally allows access for parent roles
    // and it explicitly checks qanda newsletter type, too. One day, when we stop requiring
    // course:managefiles, we will need to extend this.
    if (!has_capability('mod/newsletter:viewnewsletter', $context)) {
        return null;
    }

    if (is_null($itemid)) {
        require_once($CFG->dirroot . '/mod/forum/locallib.php');
        return new \forum_file_info_container($browser, $course, $cm, $context, $areas, $filearea);
    }

    static $cached = array();
    // Variable $cached will store last retrieved post, discussion and newsletter. To make sure that the
    // cache is cleared between unit tests we check if this is the same session.
    if (!isset($cached['sesskey']) || $cached['sesskey'] != sesskey()) {
        $cached = array('sesskey' => sesskey());
    }

    if (isset($cached['issue']) && $cached['issue']->id == $itemid) {
        $issue = $cached['issue'];
    } else if ($issue = $DB->get_record('newsletter_issues', array('id' => $itemid))) {
        $cached['issue'] = $issue;
    } else {
        return null;
    }

    if (isset($cached['newsletter']) && $cached['newsletter']->id == $cm->instance) {
        $newsletter = $cached['newsletter'];
    } else if ($newsletter = $DB->get_record('newsletter', array('id' => $cm->instance))) {
        $cached['newsletter'] = $newsletter;
    } else {
        return null;
    }

    $fs = get_file_storage();
    $filepath = is_null($filepath) ? '/' : $filepath;
    $filename = is_null($filename) ? '.' : $filename;
    if (!($storedfile = $fs->get_file($context->id, 'mod_newsletter', $filearea, $itemid, $filepath,
            $filename))) {
        return null;
    }

    // Checks to see if the user can manage files or is the owner.
    // TODO MDL-33805 - Do not use userid here and move the capability check above.
    if (!has_capability('moodle/course:managefiles', $context) &&
            $storedfile->get_userid() != $USER->id) {
        return null;
    }

    $urlbase = $CFG->wwwroot . '/pluginfile.php';
    return new \file_info_stored($browser, $context, $storedfile, $urlbase, $itemid, true, true,
            false, false);
}

/**
 * Serves the files from the newsletter file areas
 *
 * @package mod_newsletter
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the newsletter's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function newsletter_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload,
                               array $options = array()) {
    global $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);

    if (!$newsletter = $DB->get_record('newsletter', array('id' => $cm->instance))) {
        return false;
    }

    $fileareas = newsletter_get_file_areas($course, $cm, $context);
    // Filearea must contain a real area.
    if (!isset($fileareas[$filearea])) {
        return false;
    }

    $issueid = (int) array_shift($args);

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    if ($filearea == NEWSLETTER_FILE_AREA_STYLESHEET) {
        if ($newsletter->id != $issueid) {
            return false;
        }
        $fullpath = "/$context->id/mod_newsletter/$filearea/$issueid/$relativepath";
    } else {
        $fullpath = "/$context->id/mod_newsletter/$filearea/$issueid/$relativepath";
    }

    $file = $fs->get_file_by_hash(sha1($fullpath));
    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, true, $options);
}

// Navigation API.

/**
 * Extends the global navigation tree by adding newsletter nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the newsletter
 *        module instance
 * @param stdClass $course
 * @param stdClass $module
 * @param cm_info $cm
 */
function newsletter_extend_navigation(navigation_node $navref, stdclass $course, stdclass $module,
        cm_info $cm) {
    global $DB;

    $action = optional_param(NEWSLETTER_PARAM_ACTION, NEWSLETTER_ACTION_VIEW_NEWSLETTER, PARAM_ALPHA);
    $context = $cm->context;

    switch ($action) {
        case NEWSLETTER_ACTION_CREATE_ISSUE:
            require_capability('mod/newsletter:createissue', $context);
            $url = new moodle_url('/mod/newsletter/view.php',
                    array(NEWSLETTER_PARAM_ID => $cm->id, 'action' => NEWSLETTER_ACTION_CREATE_ISSUE));
            $issuenode = $navref->add(get_string('create_new_issue', 'mod_newsletter'), $url);
            $issuenode->make_active();
            break;
        case NEWSLETTER_ACTION_DUPLICATE_ISSUE:
            require_capability('mod/newsletter:createissue', $context);
            $url = new moodle_url('/mod/newsletter/view.php',
                    array(NEWSLETTER_PARAM_ID => $cm->id, 'action' => NEWSLETTER_ACTION_CREATE_ISSUE));
            $issuenode = $navref->add(get_string('create_new_issue', 'mod_newsletter'), $url);
            $issuenode->make_active();
            break;
        case NEWSLETTER_ACTION_EDIT_ISSUE:
            require_capability('mod/newsletter:editissue', $context);
            $issueid = optional_param(NEWSLETTER_PARAM_ISSUE, NEWSLETTER_NO_ISSUE, PARAM_INT);
            $url = new moodle_url('/mod/newsletter/view.php',
                    array(NEWSLETTER_PARAM_ID => $cm->id, 'action' => NEWSLETTER_ACTION_EDIT_ISSUE,
                        NEWSLETTER_PARAM_ISSUE => $issueid));
            $issuenode = $navref->add(get_string('edit_issue', 'mod_newsletter'), $url);
            $issuenode->make_active();
            break;
        case NEWSLETTER_ACTION_READ_ISSUE:
            require_capability('mod/newsletter:readissue', $context);
            $issueid = optional_param(NEWSLETTER_PARAM_ISSUE, NEWSLETTER_NO_ISSUE, PARAM_INT);
            $issuename = $DB->get_field('newsletter_issues', 'title',
                    array('id' => $issueid, 'newsletterid' => $module->id));
            $url = new moodle_url('/mod/newsletter/view.php',
                    array(NEWSLETTER_PARAM_ID => $cm->id, 'action' => NEWSLETTER_ACTION_READ_ISSUE,
                        NEWSLETTER_PARAM_ISSUE => $issueid));
            $issuenode = $navref->add($issuename, $url);
            $issuenode->make_active();
            break;
        case NEWSLETTER_ACTION_DELETE_ISSUE:
            require_capability('mod/newsletter:deleteissue', $context);
            $issueid = optional_param(NEWSLETTER_PARAM_ISSUE, NEWSLETTER_NO_ISSUE, PARAM_INT);
            $url = new moodle_url('/mod/newsletter/view.php',
                    array(NEWSLETTER_PARAM_ID => $cm->id, 'action' => NEWSLETTER_ACTION_DELETE_ISSUE,
                        NEWSLETTER_PARAM_ISSUE => $issueid));
            $issuenode = $navref->add(get_string('delete_issue', 'mod_newsletter'), $url);
            $issuenode->make_active();
            break;
        case NEWSLETTER_ACTION_MANAGE_SUBSCRIPTIONS:
            require_capability('mod/newsletter:managesubscriptions', $context);
            $url = new moodle_url('/mod/newsletter/view.php',
                    array(NEWSLETTER_PARAM_ID => $cm->id,
                        'action' => NEWSLETTER_ACTION_MANAGE_SUBSCRIPTIONS));
            $subnode = $navref->add(get_string('newsletter:managesubscriptions', 'mod_newsletter'),
                    $url);
            $subnode->make_active();
            break;
        default:
            break;
    }
}

/**
 * Extends the settings navigation with the newsletter settings
 *
 * This function is called when the context for the page is a newsletter module. This is not called
 * by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {@link settings_navigation}
 * @param navigation_node $newsletternode {@link navigation_node}
 */
function newsletter_extend_settings_navigation(settings_navigation $settingsnav,
        navigation_node $newsletternode = null) {
    global $PAGE;

    if (!isset($PAGE->cm->id)) {
        return;
    }
    $newsletter = mod_newsletter\newsletter::get_newsletter_by_course_module($PAGE->cm->id);

    if (has_capability('mod/newsletter:managesubscriptions', $newsletter->get_context())) {
        $newsletternode->add(get_string('manage_subscriptions', 'mod_newsletter'),
                new moodle_url('/mod/newsletter/view.php',
                        array('id' => $newsletter->get_course_module()->id,
                            'action' => NEWSLETTER_ACTION_MANAGE_SUBSCRIPTIONS)));
    }
    if (has_capability('mod/newsletter:createissue', $newsletter->get_context())) {
        $newsletternode->add(get_string('newsletter:createissue', 'mod_newsletter'),
                new moodle_url('/mod/newsletter/view.php',
                        array('id' => $newsletter->get_course_module()->id,
                            'action' => NEWSLETTER_ACTION_CREATE_ISSUE)));
    }
}

/**
 * Create a message-id string to use in the custom headers of forum notification emails
 *
 * message-id is used by email clients to identify emails and to nest conversations
 *
 * @param int $postid The ID of the forum post we are notifying the user about
 * @param int $usertoid The ID of the user being notified
 * @param string $hostname The server's hostname
 * @return string A unique message-id
 */
function newsletter_get_email_message_id($postid, $usertoid, $hostname) {
    return '<' . hash('sha256', $postid . 'to' . $usertoid) . '@' . $hostname . '>';
}