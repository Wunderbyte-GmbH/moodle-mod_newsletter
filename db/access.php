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
 * Capability definitions for the newsletter module
 *
 * @package    mod_newsletter
 * @copyright  2013 Ivan Šakić <ivan.sakic3@gmail.com>
 * @copyright  2015 onwards David Bogner <info@edulabs.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
$capabilities = [
    'mod/newsletter:addinstance' => ['captype' => 'write', 'contextlevel' => CONTEXT_MODULE,
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'legacy' => ['editingteacher' => CAP_ALLOW, 'manager' => CAP_ALLOW]],

    'mod/newsletter:viewnewsletter' => ['captype' => 'read', 'contextlevel' => CONTEXT_MODULE,
        'archetypes' => ['guest' => CAP_ALLOW, 'student' => CAP_ALLOW, 'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW, 'manager' => CAP_ALLOW, 'user' => CAP_ALLOW,
            'frontpage' => CAP_ALLOW]],

    'mod/newsletter:readissue' => ['captype' => 'read', 'contextlevel' => CONTEXT_MODULE,
        'archetypes' => ['guest' => CAP_ALLOW, 'student' => CAP_ALLOW, 'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW, 'manager' => CAP_ALLOW, 'user' => CAP_ALLOW,
            'frontpage' => CAP_ALLOW]],

    'mod/newsletter:createissue' => ['captype' => 'write', 'contextlevel' => CONTEXT_MODULE,
        'archetypes' => ['teacher' => CAP_ALLOW, 'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW]],

    'mod/newsletter:duplicateissue' => ['captype' => 'write', 'contextlevel' => CONTEXT_MODULE,
        'archetypes' => ['teacher' => CAP_ALLOW, 'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW]],

    'mod/newsletter:editissue' => ['captype' => 'write', 'contextlevel' => CONTEXT_MODULE,
        'archetypes' => ['teacher' => CAP_ALLOW, 'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW]],

    'mod/newsletter:deleteissue' => ['captype' => 'write', 'contextlevel' => CONTEXT_MODULE,
        'archetypes' => ['editingteacher' => CAP_ALLOW, 'manager' => CAP_ALLOW]],

    'mod/newsletter:publishissue' => ['captype' => 'write', 'contextlevel' => CONTEXT_MODULE,
        'archetypes' => ['editingteacher' => CAP_ALLOW, 'manager' => CAP_ALLOW]],

    'mod/newsletter:subscribeuser' => ['captype' => 'write', 'contextlevel' => CONTEXT_MODULE,
        'archetypes' => ['teacher' => CAP_ALLOW, 'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW]],

    'mod/newsletter:managesubscriptions' => ['captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => ['teacher' => CAP_ALLOW, 'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW]],

    'mod/newsletter:manageownsubscription' => ['captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => ['student' => CAP_ALLOW, 'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW, 'manager' => CAP_ALLOW, 'frontpage' => CAP_ALLOW,
            'user' => CAP_ALLOW]],

    'mod/newsletter:editsubscription' => ['captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => ['teacher' => CAP_ALLOW, 'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW]],

    'mod/newsletter:deletesubscription' => ['captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => ['teacher' => CAP_ALLOW, 'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW]],

    'mod/newsletter:subscribecohort' => ['captype' => 'write', 'contextlevel' => CONTEXT_MODULE,
        'archetypes' => ['editingteacher' => CAP_ALLOW, 'manager' => CAP_ALLOW]],

    'mod/newsletter:unsubscribecohort' => ['captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => ['editingteacher' => CAP_ALLOW, 'manager' => CAP_ALLOW]]];
