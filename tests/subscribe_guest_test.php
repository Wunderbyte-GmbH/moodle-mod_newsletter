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
 * Tests for the newsletter guest subscription.
 *
 * @package   mod_newsletter
 * @category  test
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_newsletter;

/**
 * Tests for \mod_newsletter\newsletter::subscribe_guest().
 *
 * @package   mod_newsletter
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \mod_newsletter\newsletter::subscribe_guest
 */
final class subscribe_guest_test extends \advanced_testcase {
    /** @var newsletter the newsletter under test. */
    private $newsletter;

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
        set_config('registerauth', 'email');

        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('newsletter', ['course' => $course->id]);
        $this->newsletter = newsletter::get_newsletter_by_instance($module->id);
    }

    /**
     * Subscribe a guest and return the message that was sent to them.
     *
     * @param string $email the address to subscribe
     * @return \stdClass the captured message
     */
    private function subscribe_and_capture(string $email): \stdClass {
        $sink = $this->redirectEmails();
        $this->assertTrue($this->newsletter->subscribe_guest('Ada', 'Lovelace', $email));
        $messages = $sink->get_messages();
        $sink->close();

        $this->assertCount(1, $messages);
        return $messages[0];
    }

    /**
     * The subscription mail must not carry the account's password.
     *
     * @return void
     */
    public function test_subscription_mail_contains_no_password(): void {
        global $DB;

        $message = $this->subscribe_and_capture('ada@example.com');
        $user = $DB->get_record('user', ['email' => 'ada@example.com'], '*', MUST_EXIST);
        $body = quoted_printable_decode($message->body);

        // The stored hash must never appear, and neither must any password label.
        $this->assertStringNotContainsString($user->password, $body);
        $this->assertStringNotContainsString('Password:', $body);
        $this->assertStringNotContainsString('Passwort:', $body);
    }

    /**
     * The mail points at the forgotten password flow instead, and still carries the activation link.
     *
     * @return void
     */
    public function test_subscription_mail_points_at_forgotten_password(): void {
        global $CFG;

        $body = quoted_printable_decode($this->subscribe_and_capture('ada@example.com')->body);

        $this->assertStringContainsString($CFG->wwwroot . '/login/forgot_password.php', $body);
        $this->assertStringContainsString('/mod/newsletter/confirm.php', $body);
    }

    /**
     * The account is created unconfirmed and its subscription is flagged for the cleanup task.
     *
     * @return void
     */
    public function test_guest_account_is_flagged_for_cleanup(): void {
        global $DB;

        $this->subscribe_and_capture('ada@example.com');
        $user = $DB->get_record('user', ['email' => 'ada@example.com'], '*', MUST_EXIST);

        $this->assertEquals(0, $user->confirmed);
        $this->assertEquals(1, $DB->get_field(
            'newsletter_subscriptions',
            'guestsignup',
            ['userid' => $user->id, 'newsletterid' => $this->newsletter->get_instance()->id]
        ));
    }

    /**
     * An address the site's denyemailaddresses setting rejects never gets an account.
     *
     * @return void
     */
    public function test_denied_email_domain_is_rejected(): void {
        global $DB;

        set_config('denyemailaddresses', 'blocked.com');

        $this->expectException(\moodle_exception::class);
        try {
            $this->newsletter->subscribe_guest('Ada', 'Lovelace', 'ada@blocked.com');
        } finally {
            $this->assertFalse($DB->record_exists('user', ['email' => 'ada@blocked.com']));
        }
    }
}
