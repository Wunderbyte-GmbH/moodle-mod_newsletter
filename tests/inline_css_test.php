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
 * Tests for the CSS inlining used when an issue is sent.
 *
 * @package   mod_newsletter
 * @category  test
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_newsletter;

/**
 * Tests for \mod_newsletter\newsletter::inline_css().
 *
 * These pin down what the bundled tijsverkoyen/css-to-inline-styles library is expected to do, so
 * that the library can be upgraded with some confidence.
 *
 * @package   mod_newsletter
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \mod_newsletter\newsletter::inline_css
 */
final class inline_css_test extends \advanced_testcase {
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

        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('newsletter', ['course' => $course->id]);
        $this->newsletter = newsletter::get_newsletter_by_instance($module->id);
    }

    /**
     * The bundled library is the version composer.json asks for and thirdpartylibs.xml declares.
     *
     * @return void
     */
    public function test_bundled_library_version_is_declared_correctly(): void {
        global $CFG;

        $lock = json_decode(file_get_contents($CFG->dirroot . '/mod/newsletter/composer.lock'), true);
        $installed = [];
        foreach ($lock['packages'] as $package) {
            $installed[$package['name']] = ltrim($package['version'], 'v');
        }
        $this->assertArrayHasKey('tijsverkoyen/css-to-inline-styles', $installed);

        $xml = simplexml_load_file($CFG->dirroot . '/mod/newsletter/thirdpartylibs.xml');
        $declared = [];
        foreach ($xml->library as $library) {
            $declared[(string) $library->location] = (string) $library->version;
        }

        $location = '/vendor/tijsverkoyen/css-to-inline-styles';
        $this->assertArrayHasKey($location, $declared);
        $this->assertEquals(
            $installed['tijsverkoyen/css-to-inline-styles'],
            $declared[$location],
            'thirdpartylibs.xml must declare the version composer.lock actually installs.'
        );
    }

    /**
     * A rule from the module's own toc.css is written into the matching element.
     *
     * @return void
     */
    public function test_stylesheet_rules_are_inlined(): void {
        $html = $this->newsletter->inline_css('<div id="newsletter-toc"><ol><li>Item</li></ol></div>', 0);

        $this->assertStringContainsString('<ol', $html);
        $this->assertMatchesRegularExpression('/<ol[^>]*style="[^"]*font-weight:\s*bold/i', $html);
    }

    /**
     * Selector specificity is respected: the nested rule wins over the outer one.
     *
     * @return void
     */
    public function test_more_specific_rule_wins(): void {
        $html = $this->newsletter->inline_css(
            '<div id="newsletter-toc"><ol><li><ol><li>Nested</li></ol></li></ol></div>',
            0
        );

        // The rule "#newsletter-toc ol ol" in toc.css sets font-weight: normal.
        $this->assertMatchesRegularExpression('/<ol[^>]*style="[^"]*font-weight:\s*normal/i', $html);
    }

    /**
     * Without $fulldocument the result is unwrapped into a div rather than a whole page.
     *
     * @return void
     */
    public function test_fragment_is_returned_without_document_wrapper(): void {
        $html = $this->newsletter->inline_css('<p>Hello</p>', 0);

        $this->assertStringNotContainsString('<html', $html);
        $this->assertStringNotContainsString('<body', $html);
        $this->assertStringContainsString('Hello', $html);
    }

    /**
     * With $fulldocument the whole document is kept.
     *
     * @return void
     */
    public function test_full_document_is_returned_when_requested(): void {
        $html = $this->newsletter->inline_css('<p>Hello</p>', 0, true);

        $this->assertStringContainsString('<html', $html);
        $this->assertStringContainsString('Hello', $html);
    }

    /**
     * Non-ASCII content survives the conversion.
     *
     * @return void
     */
    public function test_utf8_content_survives(): void {
        $html = $this->newsletter->inline_css('<p>Grüße, Ivan Šakić — 日本語</p>', 0);

        $decoded = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $this->assertStringContainsString('Grüße', $decoded);
        $this->assertStringContainsString('Šakić', $decoded);
        $this->assertStringContainsString('日本語', $decoded);
    }
}
