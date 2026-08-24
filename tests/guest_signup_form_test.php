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
 * Tests for the newsletter guest signup form.
 *
 * @package   mod_newsletter
 * @category  test
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_newsletter;

/**
 * Tests for mod_newsletter_guest_signup_form.
 *
 * @package   mod_newsletter
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \mod_newsletter_guest_signup_form::validation
 */
final class guest_signup_form_test extends \advanced_testcase {
    /**
     * Common fixture setup.
     *
     * @return void
     */
    protected function setUp(): void {
        global $CFG;

        parent::setUp();
        require_once($CFG->dirroot . '/mod/newsletter/lib.php');
        require_once($CFG->dirroot . '/mod/newsletter/guest_signup_form.php');

        $this->resetAfterTest();

        // The form's definition() calls signup_captcha_enabled(), which resolves $CFG->registerauth.
        set_config('registerauth', 'email');
    }

    /**
     * Run the form's validation for a single address.
     *
     * @param string $email the address to validate
     * @return array the resulting error messages
     */
    private function validate(string $email): array {
        $form = new \mod_newsletter_guest_signup_form(null, [
            'id' => 1,
            'embed' => 0,
            NEWSLETTER_PARAM_ACTION => NEWSLETTER_ACTION_GUESTSUBSCRIBE,
        ]);

        return $form->validation([
            'id' => 1,
            'firstname' => 'Ada',
            'lastname' => 'Lovelace',
            'email' => $email,
        ], []);
    }

    /**
     * With neither restriction configured any valid address is accepted.
     *
     * @return void
     */
    public function test_no_restrictions_configured(): void {
        $this->assertArrayNotHasKey('email', $this->validate('ada@example.com'));
    }

    /**
     * $CFG->allowemailaddresses restricts signups to the listed domains.
     *
     * @return void
     */
    public function test_allowemailaddresses(): void {
        set_config('allowemailaddresses', 'example.com');

        $this->assertArrayNotHasKey('email', $this->validate('ada@example.com'));
        $this->assertArrayHasKey('email', $this->validate('ada@elsewhere.com'));
    }

    /**
     * $CFG->denyemailaddresses blocks signups from the listed domains.
     *
     * @return void
     */
    public function test_denyemailaddresses(): void {
        set_config('denyemailaddresses', 'blocked.com');

        $this->assertArrayHasKey('email', $this->validate('ada@blocked.com'));
        $this->assertArrayNotHasKey('email', $this->validate('ada@example.com'));
    }

    /**
     * A malformed address is still rejected, and the domain check does not mask that message.
     *
     * @return void
     */
    public function test_invalid_email_still_reported(): void {
        set_config('allowemailaddresses', 'example.com');

        $errors = $this->validate('not-an-email');
        $this->assertArrayHasKey('email', $errors);
        $this->assertEquals(get_string('invalidemail'), $errors['email']);
    }
}
