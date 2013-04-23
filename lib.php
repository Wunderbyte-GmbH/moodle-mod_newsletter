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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

////////////////////////////////////////////////////////////////////////////////
// Newsletter internal constants                                              //
////////////////////////////////////////////////////////////////////////////////

define('NEWSLETTER_CRON_TEMP_FILENAME', 'newsletter_cron.tmp');
define('NEWSLETTER_LOCK_DIR', $CFG->dataroot . '/temp/mod/newsletter');
define('NEWSLETTER_LOCK_SUFFIX', 'lock');
define('NEWSLETTER_TEMP_DIR', NEWSLETTER_LOCK_DIR);
define('NEWSLETTER_FILE_AREA_STYLESHEETS', 'stylesheets');

define('NEWSLETTER_FILE_OPTIONS_SUBDIRS', 0);

define('NEWSLETTER_DELIVERY_STATUS_UNKNOWN', 0);
define('NEWSLETTER_DELIVERY_STATUS_DELIVERED', 1);
define('NEWSLETTER_DELIVERY_STATUS_FAILED', 2);

define('NEWSLETTER_BLACKLIST_STATUS_OK', 0);
define('NEWSLETTER_BLACKLIST_STATUS_BLACKLISTED', 1);

define('NEWSLETTER_ACTION_VIEW_ISSUES_PUBLISHER', 'viewpublisher');
define('NEWSLETTER_ACTION_VIEW_ISSUES_SUBSCRIBER', 'viewsubscriber');
define('NEWSLETTER_ACTION_EDIT_ISSUE', 'editissue');
define('NEWSLETTER_ACTION_READ_ISSUE', 'readissue');
define('NEWSLETTER_ACTION_DELETE_ISSUE', 'deleteissue');

define('NEWSLETTER_GROUP_ISSUES_BY_YEAR', 'year');
define('NEWSLETTER_GROUP_ISSUES_BY_MONTH', 'month');
define('NEWSLETTER_GROUP_ISSUES_BY_WEEK', 'week');
define('NEWSLETTER_GROUP_ISSUES_BY_DAY', 'day');

define('NEWSLETTER_CONTENT_PLAINTEXT_ONLY', 0);
define('NEWSLETTER_CONTENT_HTML_ONLY', 1);
define('NEWSLETTER_CONTENT_ALL', 2);

define('NEWSLETTER_NO_ISSUE', 0);

define('NEWSLETTER_DEFAULT_STYLESHEET', 0);

define('NEWSLETTER_FROM_DEFAULT', 0);
define('NEWSLETTER_TO_DEFAULT', 0);

define('NEWSLETTER_PARAM_ACTION', 'action');
define('NEWSLETTER_PARAM_GROUP_BY', 'groupby');
define('NEWSLETTER_PARAM_ISSUE', 'issue');
define('NEWSLETTER_PARAM_FROM', 'from');
define('NEWSLETTER_PARAM_TO', 'to');

////////////////////////////////////////////////////////////////////////////////
// Moodle core API                                                            //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the information on whether the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function newsletter_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:         return true;
        default:                        return null;
    }
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
function newsletter_add_instance(stdClass $newsletter, mod_newsletter_mod_form $mform = null) {
    global $DB;

    $newsletter->timecreated = time();
    $newsletter->timemodified = time();

    $newsletter->id = $DB->insert_record('newsletter', $newsletter);

    $fileoptions = array('subdirs' => NEWSLETTER_FILE_OPTIONS_SUBDIRS,
                         'maxbytes' => 0,
                         'maxfiles' => -1);

    $cmid = get_coursemodule_from_instance('newsletter', $newsletter->id);
    $context = context_module::instance($cmid->id);

    if ($mform && $mform->get_data() && $mform->get_data()->stylesheets) {
        file_save_draft_area_files($mform->get_data()->stylesheets, $context->id, 'mod_newsletter', 'stylesheets', $newsletter->id, $fileoptions);
    }

    return $DB->insert_record('newsletter', $newsletter->id);
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
function newsletter_update_instance(stdClass $newsletter, mod_newsletter_mod_form $mform = null) {
    global $DB;

    $newsletter->timemodified = time();
    $newsletter->id = $newsletter->instance;

    $fileoptions = array('subdirs' => NEWSLETTER_FILE_OPTIONS_SUBDIRS,
                         'maxbytes' => 0,
                         'maxfiles' => -1);

    $cmid = get_coursemodule_from_instance('newsletter', $newsletter->id);
    $context = context_module::instance($cmid->id);

    if ($mform && $mform->get_data() && $mform->get_data()->stylesheets) {
        file_save_draft_area_files($mform->get_data()->stylesheets, $context->id, 'mod_newsletter', 'stylesheets', $newsletter->id, $fileoptions);
    }

    return $DB->update_record('newsletter', $newsletter);
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

    $DB->delete_records('newsletter', array('id' => $newsletter->id));

    return true;
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * This function will remove all newsletter subscriptions in the database
 * and clean up any related data.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function newsletter_reset_userdata($data) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/mod/newsletter/locallib.php');

    $course = $DB->get_record('course', array('id' => $data->courseid), '*', MUST_EXIST);
    $status = array();

    $sql = "SELECT n.id FROM {newsletter} n WHERE n.course = :courseid";
    $params = array('courseid' => $data->courseid);
    if ($newsletterids = $DB->get_fieldset_sql($sql, $params)) {
        foreach ($newsletterids as $newsletterid) {
            $cm = get_coursemodule_from_instance('newsletter', $newsletterid, $data->courseid, false, MUST_EXIST);
            $context = context_module::instance($cm->id);
            $newsletter = new newsletter($context, $cm, $course);
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
    return false;  //  True if anything was printed, otherwise false
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
function newsletter_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@see newsletter_get_recent_mod_activity()}

 * @return void
 */
function newsletter_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function newsletter_cron() {
    global $DB, $CFG;

    $debugoutput = get_config('newsletter')->debug;
    if ($debugoutput) {
        echo "\n";
    }

    require_once('cron_helper.php');
    cron_helper::lock();

    if (!is_dir(NEWSLETTER_TEMP_DIR)) {
        mkdir(NEWSLETTER_TEMP_DIR, 0777, true);
    }

    $tempfilename = NEWSLETTER_TEMP_DIR . '/' . NEWSLETTER_CRON_TEMP_FILENAME;

    $continue = file_exists($tempfilename);

    $undeliveredissues = array();
    $issuestatuses = array();

    if ($continue) {
        if ($debugoutput) {
            echo "Temp file found, continuing cron job...\n";
            echo "Reading data from temp file...\n";
        }
        $issuestatuses = json_decode(file_get_contents($tempfilename), true);

        $newsletters = $DB->get_records('newsletter');
        foreach ($newsletters as $newsletter) {
            $issues = $DB->get_records('newsletter_issues', array('newsletterid' => $newsletter->id));
            foreach ($issues as $issue) {
                if ($issue->publishon <= time() && !$issue->delivered) {
                    $undeliveredissues[$issue->id] = $issue;
                }
            }
        }
    } else {
        if ($debugoutput) {
            echo "Starting cron job...\n";
            echo "Collecting data...\n";
        }
        $newsletters = $DB->get_records('newsletter');
        foreach ($newsletters as $newsletter) {
            $issues = $DB->get_records('newsletter_issues', array('newsletterid' => $newsletter->id));
            foreach ($issues as $issue) {
                if ($issue->publishon <= time() && !$issue->delivered) {
                    $issue->newsletter = $newsletter;
                    $undeliveredissues[$issue->id] = $issue;
                    if ($issue->status) {
                        $issuestatuses[$issue->id] = json_decode($issue->status, true);
                    } else {
                        $issuestatuses[$issue->id] = array();
                        $recipients = $DB->get_records('newsletter_subscriptions', array('newsletterid' => $newsletter->id));
                        foreach ($recipients as $recipient) {
                            if ($recipient->blackliststatus == NEWSLETTER_BLACKLIST_STATUS_OK) {
                                $issuestatuses[$issue->id][$recipient->userid] = NEWSLETTER_DELIVERY_STATUS_UNKNOWN;
                            }
                        }
                        $DB->set_field('newsletter_issues', 'status', json_encode($issuestatuses[$issue->id]), array('id' => $issue->id));
                    }
                }
            }
        }
        file_put_contents($tempfilename, json_encode($issuestatuses));
    }

    if ($debugoutput) {
        echo "Data collection complete. Delivering...\n";
    }
    foreach ($undeliveredissues as $issueid => $issue) {
        if ($debugoutput) {
            echo "Processing newsletter \"{$issue->newlsetter->name}\" (id = {$issue->newsletterid}), issue \"{$issue->title}\" (id = {$issue->id})...";
        }
        foreach ($issuestatuses[$issueid] as $subscriberid => $status) {
            if ($status != NEWSLETTER_DELIVERY_STATUS_DELIVERED) {
                $recipient = $DB->get_record('user', array('id' => $subscriberid));
                if ($debugoutput) {
                    echo "Sending message to {$recipient->email}... ";
                }
                $result = email_to_user(
                        $recipient,
                        $issue->newsletter->name,
                        $issue->title,
                        $issue->plaincontent,
                        $issue->htmlcontent);
                if ($debugoutput) {
                    echo (NEWSLETTER_DELIVERY_STATUS_DELIVERED ? "OK" : "FAILED") . "!\n";
                }
                $issuestatuses[$issueid][$subscriberid] = $result ? NEWSLETTER_DELIVERY_STATUS_DELIVERED : NEWSLETTER_DELIVERY_STATUS_FAILED;
                file_put_contents($tempfilename, json_encode($issuestatuses));
            }
        }
    }

    if ($debugoutput) {
        echo "Delivery complete. Updating database...\n";
    }
    foreach ($issuestatuses as $issueid => $statuses) {
        $DB->set_field('newsletter_issues', 'status', json_encode($statuses), array('id' => $issueid));
        $completed = true;
        foreach ($statuses as $status) {
            if ($status != NEWSLETTER_DELIVERY_STATUS_DELIVERED) {
                $completed = false;
                break;
            }
        }
        $DB->set_field('newsletter_issues', 'delivered', $completed, array('id' => $issueid));
    }
    if ($debugoutput) {
        echo "Database update complete. Cleaning up...\n";
    }

    unlink($tempfilename);
    cron_helper::unlock();

    return true;
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

////////////////////////////////////////////////////////////////////////////////
// File API                                                                   //
////////////////////////////////////////////////////////////////////////////////

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
    return array();
}

/**
 * File browsing support for newsletter file areas
 *
 * @package mod_newsletter
 * @category files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 */
function newsletter_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
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
function newsletter_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, false, $cm);

    if (!$quiz = $DB->get_record('newsletter', array('id' => $cm->instance))) {
        return false;
    }

    $fileareas = array('issue');
    if (!in_array($filearea, $fileareas)) {
        return false;
    }

    $issueid = (int)array_shift($args);
    if (!$issue = $DB->get_record('newsletter_issues', array('id' => $issueid))) {
        return false;
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/mod_newsletter/$filearea/$issueid/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, true, $options);
}

////////////////////////////////////////////////////////////////////////////////
// Navigation API                                                             //
////////////////////////////////////////////////////////////////////////////////

/**
 * Extends the global navigation tree by adding newsletter nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the newsletter module instance
 * @param stdClass $course
 * @param stdClass $module
 * @param cm_info $cm
 */
function newsletter_extend_navigation(navigation_node $navref, stdclass $course, stdclass $module, cm_info $cm) {
}

/**
 * Extends the settings navigation with the newsletter settings
 *
 * This function is called when the context for the page is a newsletter module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {@link settings_navigation}
 * @param navigation_node $newsletternode {@link navigation_node}
 */
function newsletter_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $newsletternode=null) {
}
