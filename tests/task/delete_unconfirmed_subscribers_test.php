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
 * Tests for the cleanup of unconfirmed newsletter guest accounts.
 *
 * @package   mod_newsletter
 * @category  test
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_newsletter\task;

/**
 * Tests for \mod_newsletter\task\delete_unconfirmed_subscribers.
 *
 * @package   mod_newsletter
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \mod_newsletter\task\delete_unconfirmed_subscribers
 */
final class delete_unconfirmed_subscribers_test extends \advanced_testcase {
    /** @var int Newsletter instance id used by the fixtures. */
    private $newsletterid = 1;

    /**
     * Common fixture setup.
     *
     * @return void
     */
    protected function setUp(): void {
        global $CFG;

        parent::setUp();
        require_once($CFG->dirroot . '/mod/newsletter/lib.php');

        $this->resetAfterTest();
        set_config('deleteunconfirmedguests', 1, 'mod_newsletter');
        set_config('activation_timeout', DAYSECS, 'mod_newsletter');
    }

    /**
     * Create a user together with a newsletter subscription.
     *
     * @param bool $confirmed whether the account is activated
     * @param int $ageindays how long ago the account was created
     * @param int $guestsignup 1 when the account came from the guest signup form
     * @return \stdClass the created user
     */
    private function create_subscriber(bool $confirmed, int $ageindays, int $guestsignup): \stdClass {
        global $DB;

        $timecreated = time() - ($ageindays * DAYSECS);
        $user = $this->getDataGenerator()->create_user([
            'confirmed' => (int) $confirmed,
            'timecreated' => $timecreated,
        ]);

        $DB->insert_record('newsletter_subscriptions', (object) [
            'newsletterid' => $this->newsletterid,
            'userid' => $user->id,
            'health' => NEWSLETTER_SUBSCRIBER_STATUS_OK,
            'timesubscribed' => $timecreated,
            'timestatuschanged' => $timecreated,
            'subscriberid' => $user->id,
            'guestsignup' => $guestsignup,
        ]);

        return $user;
    }

    /**
     * Run the task, swallowing its mtrace output.
     *
     * @return string the captured output
     */
    private function run_task(): string {
        ob_start();
        (new delete_unconfirmed_subscribers())->execute();
        return (string) ob_get_clean();
    }

    /**
     * An unconfirmed guest signup account past the activation timeout is deleted.
     *
     * @return void
     */
    public function test_expired_guest_account_is_deleted(): void {
        global $DB;

        $user = $this->create_subscriber(false, 3, 1);

        $this->run_task();

        // Moodle's logical delete: the row survives but is flagged and anonymised.
        $this->assertEquals(1, $DB->get_field('user', 'deleted', ['id' => $user->id]));
        $this->assertFalse($DB->record_exists('newsletter_subscriptions', ['userid' => $user->id]));
    }

    /**
     * An unconfirmed guest signup account still within the activation timeout is kept.
     *
     * @return void
     */
    public function test_recent_guest_account_is_kept(): void {
        global $DB;

        $user = $this->create_subscriber(false, 0, 1);

        $this->run_task();

        $this->assertEquals(0, $DB->get_field('user', 'deleted', ['id' => $user->id]));
        $this->assertTrue($DB->record_exists('newsletter_subscriptions', ['userid' => $user->id]));
    }

    /**
     * A guest signup account that was activated is never touched, however old it is.
     *
     * @return void
     */
    public function test_confirmed_guest_account_is_kept(): void {
        global $DB;

        $user = $this->create_subscriber(true, 30, 1);

        $this->run_task();

        $this->assertEquals(0, $DB->get_field('user', 'deleted', ['id' => $user->id]));
        $this->assertTrue($DB->record_exists('newsletter_subscriptions', ['userid' => $user->id]));
    }

    /**
     * Regression test for issue #47.
     *
     * An ordinary unconfirmed account that merely happens to be subscribed - for instance because
     * mod_newsletter_observer::user_created auto-subscribed it - must be left to Moodle core.
     *
     * @return void
     */
    public function test_regular_unconfirmed_subscriber_is_kept(): void {
        global $DB;

        $user = $this->create_subscriber(false, 30, 0);

        $this->run_task();

        $this->assertEquals(0, $DB->get_field('user', 'deleted', ['id' => $user->id]));
        $this->assertTrue($DB->record_exists('newsletter_subscriptions', ['userid' => $user->id]));
    }

    /**
     * With the setting turned off no account is deleted.
     *
     * @return void
     */
    public function test_setting_disabled_keeps_everything(): void {
        global $DB;

        set_config('deleteunconfirmedguests', 0, 'mod_newsletter');
        $user = $this->create_subscriber(false, 30, 1);

        $this->run_task();

        $this->assertEquals(0, $DB->get_field('user', 'deleted', ['id' => $user->id]));
        $this->assertTrue($DB->record_exists('newsletter_subscriptions', ['userid' => $user->id]));
    }

    /**
     * Subscriptions whose user no longer exists are swept up.
     *
     * @return void
     */
    public function test_orphaned_subscriptions_are_removed(): void {
        global $DB;

        $orphanid = $DB->get_field_sql('SELECT MAX(id) FROM {user}') + 1000;
        $DB->insert_record('newsletter_subscriptions', (object) [
            'newsletterid' => $this->newsletterid,
            'userid' => $orphanid,
            'health' => NEWSLETTER_SUBSCRIBER_STATUS_OK,
            'timesubscribed' => time(),
            'timestatuschanged' => time(),
            'subscriberid' => $orphanid,
            'guestsignup' => 0,
        ]);
        $keeper = $this->create_subscriber(true, 1, 0);

        $this->run_task();

        $this->assertFalse($DB->record_exists('newsletter_subscriptions', ['userid' => $orphanid]));
        $this->assertTrue($DB->record_exists('newsletter_subscriptions', ['userid' => $keeper->id]));
    }
}
