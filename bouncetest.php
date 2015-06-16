<?php
namespace mod_newsletter\bounce;

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/classes/bounce/bounceprocessor.php');
require_once($CFG->libdir.'/adminlib.php');

require_login(0,false);

$context = \context_system::instance();
$section = 'modsettingnewsletter';

$PAGE->set_context($context);
$PAGE->set_url('/admin/settings.php', array('section' => $section));
$PAGE->set_pagetype('page-admin-setting-modsettingnewsletter');
$PAGE->set_pagelayout('admin');
$PAGE->navigation->clear_cache();
\navigation_node::require_admin_tree();

require_capability('moodle/site:config', $context, $USER->id, true, "nopermissions");
$conf = get_config('mod_newsletter');

echo $OUTPUT->header();


$bounceprocessor = new \mod_newsletter\bounce\bounceprocessor($conf);
$bounceprocessor->disable_delete = true;
$bounceprocessor->test_mode = true;


echo \html_writer::tag('h1', 'Testing a single message of the local emls testfolder');
$bounceprocessor->open_mode = CWSMBH_OPEN_MODE_FILE;
if ($bounceprocessor->openFile($CFG->dirroot . '/mod/newsletter/emls/bounce_420_001.eml')) {
    $bounceprocessor->testrun();
}

echo \html_writer::tag('h1', 'Testing settings for the bounce mailbox');
$bounceprocessor->open_mode        = CWSMBH_OPEN_MODE_IMAP;
if ($bounceprocessor->openImapRemote()) {
	$bounceprocessor->testrun();
}

echo $OUTPUT->footer();

//$bounceprocessor->test_mode            = true;                  // default false
//$bounceprocessor->debug_verbose        = CWSMBH_VERBOSE_DEBUG;  // default CWSMBH_VERBOSE_QUIET
//$bounceprocessor->purge              = false;                 // default false
//$bounceprocessor->disable_delete     = false;                 // default false
//$bounceprocessor->open_mode          = CWSMBH_OPEN_MODE_IMAP; // default CWSMBH_OPEN_MODE_IMAP
//$bounceprocessor->move_soft          = false;                 // default false
//$bounceprocessor->folder_soft        = 'INBOX.soft';          // default 'INBOX.hard' - NOTE: for open_mode IMAP it must start with 'INBOX.'
//$bounceprocessor->move_hard          = false;                 // default false
//$bounceprocessor->folder_hard        = 'INBOX.hard';          // default 'INBOX.soft' - NOTE: for open_mode IMAP it must start with 'INBOX.'


/**
 * .eml file
 */
//$bounceprocessor->open_mode = CWSMBH_OPEN_MODE_FILE;
//if ($bounceprocessor->openFile('test/01.eml')) {
//    $bounceprocessor->processMails();
//}

/**
 * Local mailbox
 */
//$bounceprocessor->open_mode     = CWSMBH_OPEN_MODE_IMAP;
//if ($bounceprocessor->openImapLocal('/home/email/temp/mailbox')) {
//    $bounceprocessor->processMails();
//}


?>