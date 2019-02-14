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
 * Scheduled task to remove already sent deliveries from newsletter_deliveries.
 *
 * @package    mod_newsletter
 * @copyright  2018 michael pollak <moodle@michaelpollak.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_newsletter\task;

defined('MOODLE_INTERNAL') || die();

class remove_delivered extends \core\task\scheduled_task {

    public function get_name() {
        return 'Remove details for delivered newsletters.'; // TODO: Multilang.
    }

    /**
     * Find obsolete information and clear table.
     */
    public function execute() {
        global $DB;
        // Check what issues are still in deliveries and shouldn't be.
        $sql = "SELECT issueid, publishon
            FROM {newsletter_deliveries} del
            JOIN {newsletter_issues} iss ON del.issueid = iss.id WHERE iss.delivered=1
            GROUP BY issueid";
        $issues = $DB->get_records_sql($sql);
        $now = strtotime('now');
        foreach ($issues as $issue) {
            // Check if they are old enough that nobody cares.
            if ($now > strtotime('+1 month', $issue->publishon)) {
                $DB->delete_records('newsletter_deliveries', array ('issueid' => $issue->issueid));
            }
        }
    }
}
