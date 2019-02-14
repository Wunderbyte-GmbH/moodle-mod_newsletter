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
 * Process bounces and update health status of subscriptions.
 *
 * @package   mod_newsletter
 * @category  task
 * @copyright 2018 David Bogner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_newsletter\task;

defined('MOODLE_INTERNAL') || die();

class process_bounces extends \core\task\scheduled_task {

    public function get_name() {
        // Shown in admin screens.
        return get_string('process_bounces', 'mod_newsletter');
    }

    public function execute() {
        $config = get_config('mod_newsletter');
        if ($config->enablebounce == 1) {
            $bounceprocessor = new \mod_newsletter\bounce\bounceprocessor($config);
            if ($bounceprocessor->openImapRemote()) {
                $bounceprocessor->process_bounces();
                $bounceprocessor->update_health();
            } else {
                mtrace(
                        "!!! FAILURE to use IMAP: PHP imap does not seem to be enabled on your server!!!");
            }
        }
    }
}