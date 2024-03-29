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

defined('MOODLE_INTERNAL') || die();

$observers = array(
    array('eventname' => '\core\event\user_created',
        'callback' => 'mod_newsletter_observer::user_created'),
    array('eventname' => '\core\event\user_updated',
        'callback' => 'mod_newsletter_observer::user_updated'),
    array('eventname' => '\core\event\role_assigned',
        'callback' => 'mod_newsletter_observer::role_assigned'),
    array('eventname' => '\core\event\user_deleted',
        'callback' => 'mod_newsletter_observer::user_deleted'),
    array('eventname' => '\core\event\user_enrolment_deleted',
        'callback' => 'mod_newsletter_observer::user_enrolment_deleted')
    );
