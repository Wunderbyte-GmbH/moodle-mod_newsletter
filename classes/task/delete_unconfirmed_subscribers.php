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
 * Remove user accounts created by the newsletter guest signup form that were never confirmed.
 *
 * @package   mod_newsletter
 * @category  task
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_newsletter\task;

use core\task\scheduled_task;
use core_user;

/**
 * Deletes unconfirmed accounts that only exist because of a newsletter guest subscription.
 *
 * Only accounts created by {@see \mod_newsletter\newsletter::subscribe_guest()} are considered, i.e.
 * those whose subscription carries the guestsignup flag. Every other account is left to Moodle core's
 * \core\task\delete_unconfirmed_users_task, which is governed by $CFG->deleteunconfirmed.
 *
 * @package   mod_newsletter
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_unconfirmed_subscribers extends scheduled_task {
    /**
     * Name of the task as shown in the admin screens.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('taskdeleteunconfirmedsubscribers', 'mod_newsletter');
    }

    /**
     * Delete the expired accounts and sweep up orphaned subscription rows.
     *
     * @return void
     */
    public function execute(): void {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/lib/moodlelib.php');

        $config = get_config('mod_newsletter');

        if (empty($config->deleteunconfirmedguests)) {
            mtrace('Deletion of unconfirmed newsletter guest accounts is disabled, skipping.');
        } else {
            $timeout = !empty($config->activation_timeout) ? (int) $config->activation_timeout : DAYSECS;

            // Restricted to accounts the guest signup form created (ns.guestsignup = 1). Accounts that were
            // merely auto-subscribed by mod_newsletter_observer::user_created must never be touched here.
            $sql = "SELECT DISTINCT u.id
                      FROM {user} u
                      JOIN {newsletter_subscriptions} ns ON ns.userid = u.id
                     WHERE u.confirmed = 0
                       AND u.deleted = 0
                       AND u.mnethostid = :mnethostid
                       AND u.timecreated > 0
                       AND u.timecreated < :cutoff
                       AND ns.guestsignup = 1";
            $params = [
                'mnethostid' => $CFG->mnet_localhost_id,
                'cutoff' => time() - $timeout,
            ];

            $deleted = 0;
            $recordset = $DB->get_recordset_sql($sql, $params);
            foreach ($recordset as $record) {
                $user = core_user::get_user($record->id, '*', MUST_EXIST);
                // Logical delete, exactly as core does it. This fires \core\event\user_deleted, which
                // mod_newsletter_observer::user_deleted() observes to remove the subscription rows.
                if (delete_user($user)) {
                    mtrace("  Deleted unconfirmed newsletter guest account (id = {$user->id}).");
                    $deleted++;
                } else {
                    mtrace("  Failed to delete unconfirmed newsletter guest account (id = {$user->id}).");
                }
            }
            $recordset->close();

            mtrace("Deleted {$deleted} unconfirmed newsletter guest account(s).");
        }

        // Sweep subscriptions left behind by accounts deleted before the user_deleted observer existed.
        $orphans = $DB->count_records_select(
            'newsletter_subscriptions',
            'userid NOT IN (SELECT id FROM {user} WHERE deleted = 0)'
        );
        if ($orphans) {
            $DB->delete_records_select(
                'newsletter_subscriptions',
                'userid NOT IN (SELECT id FROM {user} WHERE deleted = 0)'
            );
            mtrace("Removed {$orphans} orphaned newsletter subscription(s).");
        }
    }
}
