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
 * Defines message providers (types of messages being sent)
 *
 * @package mod_booking
 * @copyright 2023 Wunderbyte GmbH <info@wunderbyte.at>
 * @author Georg Maißer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_newsletter\newsletter;

/**
 * To create booking specific behat scearios.
 */
class behat_newsletter extends behat_base {

    /**
     * Create booking option in booking instance
     * @Given /^I create newsletter instance "(?P<optionname_string>(?:[^"]|\\")*)" in "(?P<instancename_string>(?:[^"]|\\")*)"$/
     * @param string $optionname
     * @param string $instancename
     * @return void
     */
    public function i_create_newsletter_instance($optionname, $instancename) {

        $cm = $this->get_cm_by_booking_name($instancename);

        $record = new stdClass();
        $record->text = $optionname;
        $record->courseid = $cm->course;
        $record->description = 'Test description';

        $datagenerator = \testing_util::get_data_generator();
        /** @var mod_booking_generator $plugingenerator */
        $plugingenerator = $datagenerator->get_plugin_generator('mod_newsletter');
    }

    /**
     * Follow a certain link
     * @Given /^newsletter I open the link "(?P<linkurl_string>(?:[^"]|\\")*)"$/
     * @param string $linkurl
     * @return void
     */
    public function newsletter_i_open_the_link($linkurl) {
        $this->getSession()->visit($linkurl);
    }
}
