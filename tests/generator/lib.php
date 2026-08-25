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
 * Test data generator for mod_newsletter.
 *
 * @package   mod_newsletter
 * @category  test
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/newsletter/lib.php');

/**
 * Creates newsletter instances for unit and acceptance tests.
 *
 * @package   mod_newsletter
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_newsletter_generator extends testing_module_generator {
    /**
     * Create a newsletter instance, filling in the settings the module form would normally supply.
     *
     * @param array|stdClass|null $record instance settings to override the defaults with
     * @param array|null $options generator options passed on to the parent
     * @return stdClass the created instance
     */
    public function create_instance($record = null, ?array $options = null) {
        $record = (object) (array) $record;

        $defaults = [
            'name' => 'Test newsletter',
            'intro' => 'Test newsletter intro',
            'introformat' => FORMAT_HTML,
            'subscriptionmode' => NEWSLETTER_SUBSCRIPTION_MODE_OPT_IN,
            'allowguestusersubscriptions' => 1,
            'welcomemessage' => '',
            'welcomemessageguestuser' => '',
            'aboprofilefield' => 0,
        ];

        foreach ($defaults as $name => $value) {
            if (!isset($record->$name)) {
                $record->$name = $value;
            }
        }

        return parent::create_instance($record, (array) $options);
    }
}
