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

namespace mod_newsletter\bounce;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/classes/bounce/bounceprocessor.php');
require_once($CFG->libdir . '/adminlib.php');

require_login(0, false);

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
echo \html_writer::tag('h1', 'Testing a single message of the local emls testfolder');
if ($bounceprocessor->openEmlFolder($CFG->dirroot . '/mod/newsletter/emls')) {
    $bounceprocessor->testrun();
}

echo \html_writer::tag('h1', 'Testing settings for the bounce mailbox');
if ($bounceprocessor->openImapRemote()) {
    $bounceprocessor->testrun();
}

echo $OUTPUT->footer();