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
 *
 * @package mod_newsletter
 * @copyright 2022 Georg Maißer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_newsletter;

use stdClass;

/**
 * Holds all the functionality of the user filter.
 */
class userfilter {

    /**
     * Add alls the form elements needed.
     *
     * @param [type] $mform
     * @return void
     */
    public static function insert_form_elements(&$mform) {

        global $DB;

        $userprofilefieldsarray = [];

        // Choose the user profile field which is used to store each user's price category.
        $customuserprofilefields = $DB->get_records('user_info_field', null, '', 'id, name, shortname');

        if (!empty($customuserprofilefields)) {

            // Create an array of key => value pairs for the dropdown.
            foreach ($customuserprofilefields as $cpf) {
                $userprofilefieldsarray['cpf_' . $cpf->shortname] = $cpf->name;
            }
        }

        $stringmanager = get_string_manager();

        // Choose the user profile field which is used to store each user's price category.
        $userprofilefields = $DB->get_columns('user', true);
        // Create an array of key => value pairs for the dropdown.
        foreach ($userprofilefields as $key => $value) {

            if (in_array($key, ['password', 'id'])) {
                continue;
            }

            if ($stringmanager->string_exists($key, 'core')) {
                $userprofilefieldsarray[$key] = get_string($key);
            } else {
                $userprofilefieldsarray[$key] = $key;
            }
        }

        asort($userprofilefieldsarray);

        $operators = [
            '=' => get_string('equals', 'mod_newsletter'),
            '!=' => get_string('equalsnot', 'mod_newsletter'),
            '<' => get_string('lowerthan', 'mod_newsletter'),
            '>' => get_string('biggerthan', 'mod_newsletter'),
            '~' => get_string('contains', 'mod_newsletter'),
            '!~' => get_string('containsnot', 'mod_newsletter'),
            '[]' => get_string('inarray', 'mod_newsletter'),
            '[!]' => get_string('notinarray', 'mod_newsletter'),
            '()' => get_string('isempty', 'mod_newsletter'),
            '(!)' => get_string('isnotempty', 'mod_newsletter')
        ];

        $addcondition = [
            0 => '',
            'AND' => get_string('AND', 'mod_newsletter'),
            'OR' => get_string('OR', 'mod_newsletter')
        ];

        $mform->addElement(
            'select',
            'userprofilefield1_field',
            get_string('userprofilefield_field', 'mod_newsletter'),
            $userprofilefieldsarray
        );

        $mform->addElement(
            'select',
            'userprofilefield1_operator',
            get_string('userprofilefield_operator', 'mod_newsletter'),
            $operators
        );
        $mform->hideIf('userprofilefield_operator1', 'userprofilefield_field', 'eq', 0);

        $mform->addElement(
            'text',
            'userprofilefield1_value',
            get_string('userprofilefield_value', 'mod_newsletter')
        );
        $mform->setType('userprofilefield1_value', PARAM_RAW);

        $mform->addElement(
            'select',
            'userprofilefield2_addcondition',
            get_string('userprofilefield_addcondition', 'mod_newsletter'),
            $addcondition
        );

        $mform->addElement(
            'select',
            'userprofilefield2_field',
            get_string('userprofilefield_field', 'mod_newsletter'),
            $userprofilefieldsarray
        );
        $mform->hideIf('userprofilefield2_field', 'userprofilefield2_addcondition', 'eq', 0);

        $mform->addElement(
            'select',
            'userprofilefield2_operator',
            get_string('userprofilefield_operator', 'mod_newsletter'),
            $operators
        );
        $mform->hideIf('userprofilefield2_operator', 'userprofilefield2_addcondition', 'eq', 0);

        $mform->addElement(
            'text',
            'userprofilefield2_value',
            get_string('userprofilefield_value', 'mod_newsletter')
        );
        $mform->setType('userprofilefield2_value', PARAM_RAW);
        $mform->hideIf('userprofilefield2_value', 'userprofilefield2_addcondition', 'eq', 0);
    }

    /**
     * Interprets the form data and returns the right json generated from it.
     *
     * @param [type] $data
     * @return void
     */
    public static function return_json_from_form($data) {

        $counter = 1;
        $returnarray = [];

        // Right now, we only have two possible conditions.
        while ($counter < 3) {

            $fieldprefix = 'userprofilefield' . $counter . '_';

            // If there is no field set, we don't save.
            if (!isset($data->{$fieldprefix . 'field'})) {
                $counter++;
                continue;
            }

            $filter = new stdClass;

            // We need to know if this is custom profile or normal profile.
            if (substr($data->{$fieldprefix . 'field'}, 0, 4) === 'cpf_') {
                $filter->cpf = substr($data->{$fieldprefix . 'field'}, 4);
            } else {
                $filter->pf = $data->{$fieldprefix . 'field'};
            }

            $filter->operator = $data->{$fieldprefix . 'operator'};
            $filter->value = $data->{$fieldprefix . 'value'};
            $filter->addcondition = $data->{$fieldprefix . 'addcondition'} ?? null;

            $returnarray[] = $filter;
            $counter++;
        }

        return json_encode($returnarray);
    }

    /**
     * Adds the sql code to the existing sql to apply the filter.
     *
     * @param string $select
     * @param string $from
     * @param string $where
     * @param array $params
     * @param string $userfilter
     * @return void
     */
    public static function add_sql(string &$select,
        string &$from,
        string &$where,
        array &$params,
        string $userfilter) {

        if ($userfilter) {
            $filterobjects = json_decode($userfilter);
        }

        // We can have two objects.
        $counter = 1;
        $addwhere = '';
        $addselect = '';
        $addfrom = '';
        foreach ($filterobjects as $filterobject) {

            // We don't have an addcondition for the first filter.
            if ($counter > 1 && !empty($filterobject->addcondition)) {

                // Make sure we add the right markers, because we have a second condition here.
                switch ($filterobject->addcondition) {
                    case 'AND':
                        // Add right sql.

                        $addwhere .= ' AND ';
                        break;
                    case 'OR':
                        // Add right sql.
                        $addwhere .= ' OR ';
                        break;
                }
            }

            // Decide wether its a userfield or customuserfield

            if (isset($filterobject->pf)) {

                $fieldname = $filterobject->pf;
                $value = $filterobject->value;

                $params['parampfa'. $counter] = $value;

                // Add the sql for comparioson.
                $addwhere .= " u.$fieldname = :parampfa$counter ";

            } else if (isset($filterobject->cpf)) {

                // Add this sql for comparison.

                $shortname = $filterobject->cpf;
                $value = $filterobject->value;

                $params['paramcpfa'. $counter] = $shortname;
                $params['paramcpfb' . $counter] = $shortname;
                $params['paramcpfc' . $counter] = $value;

                $addselect .= " , s$counter.data as :paramcpfa$counter ";
                $addfrom .= " LEFT JOIN (
                    SELECT ud.*, uif.shortname
                    FROM m_user_info_data ud
                    JOIN m_user_info_field uif ON ud.fieldid=uif.id
                    WHERE uif.shortname=:paramcpfb$counter
                ) as s$counter
                ON u.id = s$counter.userid ";
                $addwhere .= " s$counter.data = :paramcpfc$counter ";
            }

            $counter++;

        }

        $select .= $addselect;
        $from .= $addfrom;

        // Only close if we have opened above.
        if (count($filterobjects) > 0) {
            $where .= " AND ( $addwhere ) ";
        }
    }

    /**
     *
     */
    public static function set_form_values($issue, &$setarry) {

        // Only if it's a loaded isse from DB, we need to set form values, else we can abort.
        if (empty($issue->id) || empty($issue->userfilter)) {
            return;
        }
        $filterobjects = json_decode($issue->userfilter);

        $counter = 1;
        foreach ($filterobjects as $filterobject) {

            $prefix = "userprofilefield$counter" . "_";

            if (isset($filterobject->cpf)) {
                $setarry[$prefix . "field"] = "cpf_" . $filterobject->cpf;
            } else if (isset($filterobject->pf)) {
                $setarry[$prefix . "field"] = $filterobject->cpf;
            }

            $setarry[$prefix . "value"] = $filterobject->value ?? null;
            $setarry[$prefix . "addcondition"] = $filterobject->addcondition ?? null;
            $setarry[$prefix . "operator"] = $filterobject->operator ?? null;

            $counter++;
        }
    }
}
