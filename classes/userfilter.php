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

use context_module;
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
    public static function insert_form_elements(&$mform, $newsletterid, $userfilter) {

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
        $nofilter = get_string('nofilter', 'mod_newsletter');
        $userprofilefieldsarray[0] = $nofilter;
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
        $mform->setDefault('userprofilefield1_field', $nofilter);

        $mform->addElement(
            'select',
            'userprofilefield1_operator',
            get_string('userprofilefield_operator', 'mod_newsletter'),
            $operators
        );
        $mform->hideIf('userprofilefield1_operator', 'userprofilefield1_field', 'eq', 0);

        $mform->addElement(
            'text',
            'userprofilefield1_value',
            get_string('userprofilefield_value', 'mod_newsletter')
        );
        $mform->setType('userprofilefield1_value', PARAM_RAW);
        $mform->hideIf('userprofilefield1_value', 'userprofilefield1_field', 'eq', 0);

        $mform->addElement(
            'select',
            'userprofilefield2_addcondition',
            get_string('userprofilefield_addcondition', 'mod_newsletter'),
            $addcondition
        );
        $mform->hideIf('userprofilefield2_addcondition', 'userprofilefield1_field', 'eq', 0);

        $mform->addElement(
            'select',
            'userprofilefield2_field',
            get_string('userprofilefield_field', 'mod_newsletter'),
            $userprofilefieldsarray
        );
        $mform->hideIf('userprofilefield2_field', 'userprofilefield2_addcondition', 'eq', 0);
        $mform->hideIf('userprofilefield2_field', 'userprofilefield1_field', 'eq', 0);

        $mform->addElement(
            'select',
            'userprofilefield2_operator',
            get_string('userprofilefield_operator', 'mod_newsletter'),
            $operators
        );
        $mform->hideIf('userprofilefield2_operator', 'userprofilefield2_addcondition', 'eq', 0);
        $mform->hideIf('userprofilefield2_operator', 'userprofilefield1_field', 'eq', 0);

        $mform->addElement(
            'text',
            'userprofilefield2_value',
            get_string('userprofilefield_value', 'mod_newsletter')
        );
        $mform->setType('userprofilefield2_value', PARAM_RAW);
        $mform->hideIf('userprofilefield2_value', 'userprofilefield2_addcondition', 'eq', 0);
        $mform->hideIf('userprofilefield2_value', 'userprofilefield1_field', 'eq', 0);

        // $mform->createElement('submit', 'submitbutton', get_string('savechanges'));

        $count = self::return_number_of_filtered_recipients($newsletterid, $userfilter);

        $mform->registerNoSubmitButton('calculatefilteredusers');
        $elements = [
            $mform->createElement('static', 'filtereduserscount', "filtereduserscount",
                get_string('filteredusercount', 'mod_newsletter', $count)),
            $mform->createElement('submit', 'calculatefilteredusers', get_string('calculateusers', 'mod_newsletter'))
        ];
        $mform->addGroup($elements, 'calculateusersgroup', 'calculateusersgroup', [' '], false);
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
            if (empty($data->{$fieldprefix . 'field'}) && $counter === 1) {
                return '';
            } else if (empty($data->{$fieldprefix . 'field'})) {
                $counter++;
                continue;
            }

            if (($counter > 1) && empty($data->{$fieldprefix . 'addcondition'})) {
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

        if (!empty($userfilter)) {
            $filterobjects = json_decode($userfilter);
        } else {
            // Do nothing.
            return;
        }

        // We can have two objects.
        $counter = 1;
        $addwhere = '';
        $addselect = '';
        $addfrom = '';
        foreach ($filterobjects as $filterobject) {

            // We don't have an addcondition for the first filter.
            if ($counter > 1) {
                if (!empty($filterobject->addcondition)) {

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
                } else {
                    continue;
                }
            }

            $operator = $filterobject->operator;

            // Decide wether its a userfield or customuserfield

            if (isset($filterobject->pf)) {

                $fieldname = $filterobject->pf;
                $value = $filterobject->value;

                // Add the sql for comparioson.
                $addwhere .= self::return_where("u.$fieldname",
                    $value,
                    $operator,
                    $counter,
                    $params);

            } else if (isset($filterobject->cpf)) {

                // Add this sql for comparison.

                $shortname = $filterobject->cpf;
                $value = $filterobject->value;

                $params['paramcpfa'. $counter] = $shortname;
                $params['paramcpfb' . $counter] = $shortname;

                $addselect .= " , s$counter.data as :paramcpfa$counter ";
                $addfrom .= " LEFT JOIN (
                    SELECT ud.*, uif.shortname
                    FROM {user_info_data} ud
                    JOIN {user_info_field} uif ON ud.fieldid=uif.id
                    WHERE uif.shortname=:paramcpfb$counter
                ) as s$counter
                ON u.id = s$counter.userid ";
                $addwhere .= self::return_where("s$counter.data",
                    $value,
                    $operator,
                    $counter,
                    $params);
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
     * Returns where sql string and adds params, based on input & Operator.
     * @param string $needle
     * @param string $haystack
     * @param string $operator
     * @param array $params
     * @return string
     */
    private static function return_where(string $dbvalue,
        string $formvalue,
        string $operator,
        int $counter,
        array &$params) {
        global $DB;

        $inparams = [];

        switch ($operator) {
            case '=':
                $dbvalue = $DB->sql_compare_text($dbvalue);
                $formvalue = $DB->sql_compare_text($formvalue);
                $inparams["paramop$counter"] = $formvalue;
                $fragment = $DB->sql_equal($dbvalue, ":paramop$counter", false, false);

                break;
            case '!=':
                $dbvalue = $DB->sql_compare_text($dbvalue);
                $formvalue = $DB->sql_compare_text($formvalue);
                $inparams["paramop$counter"] = $formvalue;
                $fragment = $DB->sql_equal($dbvalue, ":paramop$counter", false, false, true);

                // With <> we need to add "OR IS NULL".
                $fragment .= " OR $dbvalue IS NULL ";

                break;
            case '~':
                $dbvalue = $DB->sql_compare_text($dbvalue);
                $formvalue = $DB->sql_compare_text($formvalue);
                $inparams["paramop$counter"] = "%$formvalue%";
                $fragment = $DB->sql_like($dbvalue, ":paramop$counter", false, false);
                break;
            case '!~':
                $dbvalue = $DB->sql_compare_text($dbvalue);
                $formvalue = $DB->sql_compare_text($formvalue);
                $inparams["paramop$counter"] = "%$formvalue%";
                $fragment = $DB->sql_like($dbvalue, ":paramop$counter", false, false, true);

                // With <> we need to add "OR IS NULL".
                $fragment .= " OR $dbvalue IS NULL ";
                break;
            case '[]':
                $array = explode(',', $formvalue);
                list($fragment, $inparams) = $DB->get_in_or_equal($array, SQL_PARAMS_NAMED, "paramop$counter");
                $fragment = $dbvalue . " $fragment";
                break;
            case '[!]':
                $array = explode(',', $formvalue);
                list($fragment, $inparams) = $DB->get_in_or_equal($array, SQL_PARAMS_NAMED, "paramop$counter", false);
                $fragment = $dbvalue . " $fragment";

                // With <> we need to add "OR IS NULL".
                $fragment .= " OR $dbvalue IS NULL ";
                break;
            case '()':
                $fragment = $dbvalue . " IS NULL";
                $fragment .= " OR $dbvalue = ''"; // To also cover empty strings.
                break;
            case '(!)':
                $fragment = $dbvalue . " IS NOT NULL";
                $fragment .= " AND $dbvalue <> ''"; // To also cover empty strings.
                break;
            case '>':
                $inparams["paramop$counter"] = $formvalue;
                $fragment = $dbvalue . " > :paramop$counter";
                break;
            case '<':
                $inparams["paramop$counter"] = $formvalue;
                $fragment = $dbvalue . " < :paramop$counter";
                break;
        }

        $sql = " $fragment ";

        $params = array_merge($params, $inparams);

        return $sql;

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

    /**
     * Undocumented function
     *
     * @param stdClass $issue
     * @return void
     */
    public static function user_can_see_this_issue(stdClass $issue) {

        global $USER, $PAGE;

        list($course, $cm) = get_course_and_cm_from_instance($issue->newsletterid, 'newsletter');
        $context = context_module::instance($cm->id);
        // A user having these rights, can always see the issue.
        if (has_capability('mod/newsletter:editissue', $context)) {
            return true;
        }

        $user = $USER;

        $pereviousresult = true;
        $result = null;

        if (!empty($issue->userfilter)) {
            $userfilters = json_decode($issue->userfilter);

            profile_load_custom_fields($user);

            $counter = 1;
            foreach ($userfilters as $userfilter) {

                if (!empty($userfilter->cp)) {

                    $uservalue = $user->{$userfilter->cp} ?? null;

                    $prelimanaryresult = self::check_user_values($userfilter->cp,
                        $userfilter->value,
                        $uservalue,
                        $userfilter->operator);

                } else if (!empty($userfilter->cpf)) {

                    $uservalue = $user->profile[$userfilter->cpf] ?? null;

                    $prelimanaryresult = self::check_user_values($userfilter->cpf,
                        $userfilter->value,
                        $uservalue,
                        $userfilter->operator);

                } else {
                    // If there is no valid field, we can skip the rest right away.
                    $prelimanaryresult = true;
                }

                if (isset($userfilter->addcondition)) {
                    switch ($userfilter->addcondition) {
                        case 'AND':
                            $result = $pereviousresult && $prelimanaryresult;
                        break;
                        case 'OR':
                            $result = $pereviousresult || $prelimanaryresult;
                        break;
                        default:
                            $result = $pereviousresult;
                    }
                }
                $pereviousresult = $prelimanaryresult;
            }
        }

        $result = $result ?? $pereviousresult;

        return $result;
    }

    /**
     * Undocumented function
     *
     * @param string $fieldname
     * @param string $fieldvalue
     * @param stdClass $user
     * @param string $operator
     * @return bool
     */
    private static function check_user_values(string $fieldname, string $fieldvalue, $uservalue, string $operator) {

        $result = false;

        // We accept null as uservalue only in case of one operator.
        if ($uservalue === null && $operator != '()') {
            return false;
        }

        switch ($operator) {
            case '=':
                return $uservalue == $fieldvalue;
            case '!=':
                return $uservalue != $fieldvalue;
            case '~':
                $pos = strpos($uservalue, $fieldvalue);

                if ($pos == false) {
                    return false;
                } else {
                    return true;
                }
            case '!~':
                $pos = strpos($uservalue, $fieldvalue);

                if ($pos != false) {
                    return false;
                } else {
                    return true;
                }
            case '[]':
                $array = explode(',', $fieldvalue);
                return in_array($uservalue, $array);
            case '[!]':
                $array = explode(',', $fieldvalue);
                return !in_array($uservalue, $array);
            case '()':
                return empty($uservalue);
            case '(!)':
                return !empty($uservalue);
            case '>':
                return $uservalue > $fieldvalue;
            case '<':
                return $uservalue < $fieldvalue;
        }
        return $result;
    }


    private static function return_number_of_filtered_recipients($newsletterid, $userfilter) {

        if (!$userfilter) {
            return 0;
        }
        return count(newsletter_get_all_valid_recipients($newsletterid, $userfilter));
    }
}
