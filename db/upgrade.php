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
 * This file keeps track of upgrades to the newsletter module
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installation to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in DLL libraries.
 *
 * @package    mod_newsletter
 * @copyright  2013 Ivan Šakić <ivan.sakic3@gmail.com>
 * @copyright  2015 onwards David Bogner <info@edulabs.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute newsletter upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_newsletter_upgrade($oldversion)
{
    global $DB;

    $dbman = $DB->get_manager();
    if ($oldversion < 2015041400) {

        // Change status to be allowed to be null.
        $table = new xmldb_table('newsletter_issues');
        $field = new xmldb_field('status', XMLDB_TYPE_TEXT, 'big', null, null, null, null, 'publishon');

        // Conditionally change field type.
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_type($table, $field);
        }

        // Savepoint reached.
        upgrade_mod_savepoint(true, 2015041400, 'newsletter');
    }

    if ($oldversion < 2015041401) {

        // Change status to be allowed to be null.
        $table = new xmldb_table('newsletter_issues');
        $field = new xmldb_field('delivered', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'status');

        // Conditionally change field type.
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_type($table, $field);
        }

        // Forum savepoint reached.
        upgrade_mod_savepoint(true, 2015041401, 'newsletter');
    }

    if ($oldversion < 2015053000) {

        // Add field.
        $table = new xmldb_table('newsletter_subscriptions');

        // Conditionally add field.
        $field = new xmldb_field('timesubscribed', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'health');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add field.
        $field = new xmldb_field('timestatuschanged', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timesubscribed');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add field.
        $field = new xmldb_field('subscriberid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timestatuschanged');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add field.
        $field = new xmldb_field('unsubscriberid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'subscriberid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Newsletter savepoint reached.
        upgrade_mod_savepoint(true, 2015053000, 'newsletter');
    }
    if ($oldversion < 2015061201) {

        // Add table.
        $table = new xmldb_table('newsletter_bounces');
        // Conditionally add field.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'id');
        $table->add_field('issueid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'userid');
        $table->add_field('statuscode', XMLDB_TYPE_CHAR, '8', null, null, null, null, 'issueid');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'statuscode');
        $table->add_field('type', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'timecreated');
        $table->add_field('timereceived', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'type');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('issueid', XMLDB_KEY_FOREIGN, array('issueid'), 'newsletter_issues', array('id'));
        $table->add_index('userid', XMLDB_INDEX_NOTUNIQUE, array('userid'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        // Newsletter savepoint reached.
        upgrade_mod_savepoint(true, 2015061201, 'newsletter');
    }
    if ($oldversion < 2015061500) {

        // Add field.
        $table = new xmldb_table('newsletter_subscriptions');

        // Conditionally add field.
        $field = new xmldb_field('sentnewsletters', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'unsubscriberid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Newsletter savepoint reached.
        upgrade_mod_savepoint(true, 2015061500, 'newsletter');
    }
    if ($oldversion < 2015061601) {

        // Add field.
        $table = new xmldb_table('newsletter_issues');

        // Conditionally add field.
        $field = new xmldb_field('status', XMLDB_TYPE_INTEGER, 'big', null, null, null, null, 'publishon');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Add table.
        $table = new xmldb_table('newsletter_deliveries');
        // Conditionally add field.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'id');
        $table->add_field('issueid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'userid');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('issueid', XMLDB_KEY_FOREIGN, array('issueid'), 'newsletter_issues', array('id'));
        $table->add_index('userid', XMLDB_INDEX_NOTUNIQUE, array('userid'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Newsletter savepoint reached.
        upgrade_mod_savepoint(true, 2015061601, 'newsletter');
    }

    if ($oldversion < 2015081400) {

        // Conditionally add field.
        $table = new xmldb_table('newsletter_issues');
        $field = new xmldb_field('toc', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'delivered');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Newsletter savepoint reached.
        upgrade_mod_savepoint(true, 2015081400, 'newsletter');
    }

    if ($oldversion < 2015081500) {

        // Conditionally add field.
        $table = new xmldb_table('newsletter_deliveries');
        $field = new xmldb_field('delivered', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'issueid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Newsletter savepoint reached.
        upgrade_mod_savepoint(true, 2015081500, 'newsletter');
    }

    if ($oldversion < 2015081900) {

        // Conditionally add field.
        $table = new xmldb_table('newsletter_deliveries');
        $field = new xmldb_field('newsletterid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'issueid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $table->add_key('newsletterid', XMLDB_KEY_FOREIGN, array('newsletterid'), 'newsletter', array('id'));

        $sql = 'UPDATE {newsletter_deliveries} nd
                INNER JOIN {newsletter_issues} ni
                ON nd.issueid = ni.id
                SET nd.newsletterid = ni.newsletterid';
        $DB->execute($sql);
        // Newsletter savepoint reached.
        upgrade_mod_savepoint(true, 2015081900, 'newsletter');
    }

    if ($oldversion < 2015082504) {

        $table = new xmldb_table('newsletter_deliveries');

        // Change to XMLDB_NOTNULL.
        $field = new xmldb_field('delivered', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'newsletterid');

        // Conditionally change field type.
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_notnull($table, $field);
        }

        // Savepoint reached.
        upgrade_mod_savepoint(true, 2015082504, 'newsletter');
    }

    if ($oldversion < 2016061700) {

        $table = new xmldb_table('newsletter');

        // New field allowguestusersubscriptions in newsletter.
        $field = new xmldb_field('allowguestusersubscriptions', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'subscriptionmode');

        // Add new field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // New field welcomemessage in newsletter.
        $field = new xmldb_field('welcomemessage', XMLDB_TYPE_TEXT, 'big', null, null, null, null, 'allowguestusersubscriptions');

        // add new field
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // New field welcomemessageguestuser in newsletter.
        $field = new xmldb_field('welcomemessageguestuser', XMLDB_TYPE_TEXT, 'big', null, null, null, null, 'welcomemessage');

        // Add new field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $config = get_config('mod_newsletter');
        if ($config->allow_guest_user_subscriptions == 1) {
            $sql = 'UPDATE {newsletter} SET allowguestusersubscriptions = 1 where 1';
            $DB->execute($sql);
        }

        // Savepoint reached.
        upgrade_mod_savepoint(true, 2016061700, 'newsletter');
    }

    // Add possibility not to send unsubscribe link. Add new field nounsublink, #31.
    if ($oldversion < 2018082706) {
        // Define field nounsublink to be added to newsletter_subscriptions.
        $table = new xmldb_table('newsletter_subscriptions');
        $field = new xmldb_field(
            'nounsublink',
            XMLDB_TYPE_INTEGER,
            '1',
            null,
            null,
            null,
            '0',
            'sentnewsletters'
        );

        // Conditionally launch add field nounsublink.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Newsletter savepoint reached.
        upgrade_mod_savepoint(true, 2018082706, 'newsletter');
    }

    // Change delivered field in order to save timestamp instead of bool.
    if ($oldversion < 2019022300) {
        // Define field nounsublink to be added to newsletter_subscriptions.
        $table = new xmldb_table('newsletter_deliveries');
        $field = new xmldb_field('delivered', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'newsletterid');

        // Conditionally launch field precision change.
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_precision($table, $field);
        }
        $sql = "UPDATE {newsletter_deliveries} nd
                SET nd.delivered = (SELECT ni.publishon FROM {newsletter_issues} ni WHERE ni.id = nd.issueid)";
        $DB->execute($sql);
        // Newsletter savepoint reached.
        upgrade_mod_savepoint(true, 2019022300, 'newsletter');
    }

    if ($oldversion < 2021030900) {

        $table = new xmldb_table('newsletter_deliveries');
        // Conditionally add index.
        $table->add_index('userid-delivered', XMLDB_INDEX_NOTUNIQUE, ['userid', 'delivered']);
        $table->add_index('issueid-delivered', XMLDB_INDEX_NOTUNIQUE, ['issueid', 'delivered']);

        // Newsletter savepoint reached.
        upgrade_mod_savepoint(true, 2021030900, 'newsletter');
    }

    if ($oldversion < 2021052600) {
        $table = new xmldb_table('newsletter_deliveries');
        // Conditionally rename field.
        $field = new xmldb_field('delivered', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'newsletterid');
        // Conditionally add field.
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, "deliverytime");
        }

        $field = new xmldb_field('delivered', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'newsletterid');
        // Conditionally launch add field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $sql = "UPDATE {newsletter_deliveries} SET delivered = 1 WHERE deliverytime > 0";
        $DB->execute($sql);
        // Newsletter savepoint reached.
        upgrade_mod_savepoint(true, 2021052600, 'newsletter');
    }

    if ($oldversion < 2022090800) {

        // Define field userfilter to be added to newsletter_issues.
        $table = new xmldb_table('newsletter_issues');
        $field = new xmldb_field('userfilter', XMLDB_TYPE_TEXT, null, null, null, null, null, 'toc');

        // Conditionally launch add field userfilter.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Newsletter savepoint reached.
        upgrade_mod_savepoint(true, 2022090800, 'newsletter');
    }

    if ($oldversion < 2022091400) {

        // Define field timecreated to be added to newsletter_issues.
        $table = new xmldb_table('newsletter_issues');
        $field1 = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'userfilter');
        $field2 = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timecreated');
        // Conditionally launch add field timecreated.
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }
        if (!$dbman->field_exists($table, $field2)) {
            $dbman->add_field($table, $field2);
        }

        // Newsletter savepoint reached.
        upgrade_mod_savepoint(true, 2022091400, 'newsletter');
    }

    if ($oldversion < 2023071201) {

        // Define field id to be added to newsletter.
        $table = new xmldb_table('newsletter');
        $field = new xmldb_field('profilefield', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'welcomemessageguestuser');

        // Conditionally launch add field id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Newsletter savepoint reached.
        upgrade_mod_savepoint(true, 2023071201, 'newsletter');
    }

    if ($oldversion < 2023071307) {

        // Define field id to be added to newsletter.
        $table = new xmldb_table('newsletter');
        $field = new xmldb_field('aboprofilefield', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'welcomemessageguestuser');

        // Conditionally launch add field id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Newsletter savepoint reached.
        upgrade_mod_savepoint(true, 2023071307, 'newsletter');
    }


    return true;
}
