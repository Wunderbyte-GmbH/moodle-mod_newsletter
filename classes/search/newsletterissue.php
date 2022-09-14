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
 * FAQ search
 *
 * @package    mod_newsletter
 * @copyright  2022 Wunderbyte GmbH <georg.maisser@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_newsletter\search;

defined('MOODLE_INTERNAL') || die();

/**
 * FAQ search area.
 *
 * @package    local_wb_faq
 * @copyright  2022 Wunderbyte GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class newsletterissue extends \core_search\base {

    private array $data = []; // stores data.

    /**
     * Returns recordset containing required data for indexing Newsletter issues.
     * We only return newsletters which have been modified AND which have been published.
     *
     * @param int $modifiedfrom timestamp
     * @param \context|null $context Optional context to restrict scope of returned results
     * @return moodle_recordset|null Recordset (or null if no results)
     */
    public function get_document_recordset($modifiedfrom = 0, \context $context = null) {
        global $DB;

        $now = time();

        $sql = "SELECT * FROM {newsletter_issues}
                WHERE timemodified >= ?
                AND publishon < ?
                AND delivered = 1
                ORDER BY timemodified ASC";
        return $DB->get_recordset_sql($sql, [$modifiedfrom, $now]);
    }

    /**
     * Returns the document associated with newsletter issue.
     *
     * @param stdClass $record FAQ info.
     * @param array    $options
     * @return \core_search\document
     */
    public function get_document($record, $options = array()) {

        // This is the id of the instance, actually.
        $newsletterid = $record->newsletterid;

        list($course, $cm) = get_course_and_cm_from_instance($newsletterid, 'newsletter');
        $context = \context_module::instance($cm->id);

        $this->data['cmid'] = $cm->id;

        // Prepare associative array with data from DB.
        $doc = \core_search\document_factory::instance($record->id, $this->componentname, $this->areaname);
        $doc->set('title', content_to_text($record->title, false));
        $doc->set('content', content_to_text($record->htmlcontent, FORMAT_HTML));
        $doc->set('contextid', $context->id);
        // Not associated with a course
        $doc->set('courseid', $course->id);
        $doc->set('owneruserid', \core_search\manager::NO_OWNER_ID);
        $doc->set('modified', $record->timemodified);

        // Check if this document should be considered new.
        if (isset($options['lastindexedtime']) && ($options['lastindexedtime'] < $record->timemodified)) {
            // If the document was modified after the last index time, it must be new.
            $doc->set_is_new(true);
        }

        return $doc;
    }

    /**
     * Returns true if this area uses file indexing.
     *
     * @return bool
     */
    public function uses_file_indexing() {
        return false;
    }


    /**
     * Whether the user can access the document or not.
     *
     * @throws \dml_missing_record_exception
     * @throws \dml_exception
     * @param int $id Forum post id
     * @return bool
     */
    public function check_access($id) {
        global $USER;
        return \core_search\manager::ACCESS_GRANTED;
    }

    /**
     * Link to the Newsletter Issue
     *
     * @param \core_search\document $doc
     * @return \moodle_url
     */
    public function get_doc_url(\core_search\document $doc) {

        $cmid = $this->return_cmid($doc);

        return new \moodle_url('/mod/newsletter/view.php', array(
            'issue' => $doc->get('itemid'),
            'id' => $cmid,
            'action' => 'readissue'));
    }

    /**
     * Link to Newsletter Instance.
     *
     * @param \core_search\document $doc
     * @return \moodle_url
     */
    public function get_context_url(\core_search\document $doc) {

        $cmid = $this->return_cmid($doc);

        return new \moodle_url('/mod/newsletter/view.php', array(
            'id' => $cmid
        ));
    }


    /**
     * Confirms that data entries support group restrictions.
     *
     * @return bool false
     */
    public function supports_group_restriction() {
        return false;
    }

    /**
     * Helper function to return cmid from doc data.
     *
     * @param any $doc
     * @return int|bool
     */
    public function return_cmid($doc) {
        global $DB;

        $contextid = $doc->get('contextid');

        $params = [
            'module' => 'newsletter',
            'contextid' => $contextid

        ];

        $sql = "SELECT cm.id
                FROM {course_modules} cm
                JOIN {modules} m ON cm.module=m.id
                JOIN {context} c ON c.instanceid=cm.id
                WHERE m.name=:module
                AND c.id=:contextid";

        return $DB->get_field_sql($sql, $params);
    }
}
