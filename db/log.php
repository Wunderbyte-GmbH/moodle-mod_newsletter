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
 * Definition of log events
 *
 * @package    mod_newsletter
 * @copyright  2013 Ivan Šakić <ivan.sakic3@gmail.com>
 * @copyright  2015 onwards David Bogner <info@edulabs.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$logs = array(
    array('module' => 'newsletter', 'action' => 'add', 'mtable' => 'newsletter', 'field' => 'name'),
    array('module' => 'newsletter', 'action' => 'update', 'mtable' => 'newsletter', 'field' => 'name'),
    array('module' => 'newsletter', 'action' => 'view', 'mtable' => 'newsletter', 'field' => 'name'),
    array('module' => 'newsletter', 'action' => 'view all', 'mtable' => 'newsletter',
        'field' => 'name'));
