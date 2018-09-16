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

/**
 *
 * @package mod_newsletter
 * @copyright 2016 David Bogner
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Instance store for newsletter manager classes.
 */
class mod_newsletter_instance_store {

    /** @var array List of stored instances. */
    protected static $instances = array();

    /**
     * Returns an instance from local store, null otherwise.
     *
     * @param int $newsletterid newsletter id
     * @param string $typename
     * @return mixed Instance object or null
     */
    public static function instance($newsletterid, $typename) {
        if (!empty(self::$instances[$newsletterid][$typename])) {
            return self::$instances[$newsletterid][$typename];
        }
        return null;
    }

    /**
     * Adds instance to local store.
     *
     * @param int newsletter id.
     * @param string Instance type name.
     * @param mixed Instance object.
     * @return object $instance
     */
    public static function register($newsletterid, $typename, $instance) {
        if (empty(self::$instances[$newsletterid])) {
            self::$instances[$newsletterid] = array();
        }
        self::$instances[$newsletterid][$typename] = $instance;
    }

    /**
     * Removes instance from local store.
     *
     * @param int newsletter id.
     * @param string Instance type name.
     * @return void.
     */
    public static function unregister($newsletterid = 0, $typename = null) {
        if (!$newsletterid) {
            self::$instances = array();
        } else if (!$typename) {
            unset(self::$instances[$newsletterid]);
        } else {
            unset(self::$instances[$newsletterid][$typename]);
        }
    }
}
