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
 * Defines backup_newsletter_activity_task class
 *
 * @package   mod_newsletter
 * @category  backup
 * @copyright 2018 onwards David Bogner {@link http://www.edulabs.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/newsletter/backup/moodle2/backup_newsletter_stepslib.php');
require_once($CFG->dirroot . '/mod/newsletter/backup/moodle2/backup_newsletter_settingslib.php');

/**
 * Provides the steps to perform one complete backup of the newsletter instance
 */
class backup_newsletter_activity_task extends backup_activity_task {

    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
    }

    /**
     * Defines a backup step to store the instance data in the newsletter.xml file
     */
    protected function define_my_steps() {
        $this->add_step(new backup_newsletter_activity_structure_step('newsletter structure', 'newsletter.xml'));
    }

    /**
     * Encodes URLs to the index.php, view.php and discuss.php scripts
     *
     * @param string $content some HTML text that eventually contains URLs to the activity instance
     *        scripts
     * @return string the content with the URLs encoded
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of newsletters.
        $search = "/(" . $base . "\/mod\/newsletter\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@NEWSLETTERINDEX*$2@$', $content);

        // Link to newsletter view by moduleid.
        $search = "/(" . $base . "\/mod\/newsletter\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@NEWSLETTERVIEWBYID*$2@$', $content);

        // Link to newsletter issue with action syntax.
        $search = "/(" . $base .
                "\/mod\/newsletter\/issue.php\?id\=)([0-9]+)(?:\&amp;|\&)action\=readissue(?:\&amp;|\&)issue\=([0-9]+)/";
        $content = preg_replace($search, '$@NEWSLETTERREADISSUE*$2*$3@$', $content);

        // Link to newsletter issue with relative syntax.
        $search = "/(" . $base . "\/mod\/newsletter\/issue.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@NEWSLETTERISSUE*$2*$3@$', $content);

        return $content;
    }
}
