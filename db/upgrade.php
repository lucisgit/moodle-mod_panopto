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
 * Panopto course module upgrade code.
 *
 * This file keeps track of upgrades to
 * the resource module
 *
 * Sometimes, changes between versions involve
 * alterations to database structures and other
 * major things that may break installations.
 *
 * The upgrade function in this file will attempt
 * to perform all the necessary actions to upgrade
 * your older installation to the current version.
 *
 * If there's something it cannot do itself, it
 * will tell you what you need to do.
 *
 * The commands in here will all be database-neutral,
 * using the methods of database_manager class
 *
 * Please do not forget to use upgrade_set_timeout()
 * before any action that may take longer time to finish.
 *
 *
 * @package    mod_panopto
 * @copyright  2017 Lancaster University (http://www.lancaster.ac.uk/)
 * @author     Ruslan Kabalin (https://github.com/kabalin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

function xmldb_panopto_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2017052405) {
        // Internal upgrade for the old panopto mod. externalpanopto filed contained the full URL,
        // we replace it with Panopto sesssion id (which is a URL param of the filed content).
        $panoptoresources = $DB->get_records('panopto');
        foreach ($panoptoresources as $panoptoresource) {
            if (!empty($panoptoresource->externalpanopto)) {
                $url = new \moodle_url($panoptoresource->externalpanopto);
                $panoptoresource->externalpanopto = $url->get_param('id');
                $DB->update_record('panopto', $panoptoresource);
            } else {
                $DB->delete_record('panopto', array('id' => $panoptoresource->id));
            }
        }

        // Rename the field externalpanopto to panoptosessionid and change its type.
        $table = new xmldb_table('panopto');
        $field = new xmldb_field('externalpanopto', XMLDB_TYPE_TEXT);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'panoptosessionid');
            // Changing type of field panoptosessionid on table panopto to char.
            $field = new xmldb_field('panoptosessionid', XMLDB_TYPE_CHAR, '36', null, XMLDB_NOTNULL, null, null, 'introformat');
            // Launch change of type for field panoptosessionid.
            $dbman->change_field_type($table, $field);
        }

        // Add field for storing Panopto group id.
        $table = new xmldb_table('panopto');
        $field = new xmldb_field('panoptogroupid', XMLDB_TYPE_CHAR, '36', null, XMLDB_NOTNULL, null, null, 'panoptosessionid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define table panopto_user_access and create.
        $table = new xmldb_table('panopto_user_access');

        // Adding fields to table panopto_user_access.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('panoptouserid', XMLDB_TYPE_CHAR, '36', null, XMLDB_NOTNULL, null, null);
        $table->add_field('panoptogroupid', XMLDB_TYPE_CHAR, '36', null, XMLDB_NOTNULL, null, null);
        $table->add_field('panoptoextgroupid', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('timeaccessed', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table panopto_user_access.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table panopto_user_access.
        $table->add_index('timeaccessed', XMLDB_INDEX_NOTUNIQUE, array('timeaccessed'));
        $table->add_index('userid', XMLDB_INDEX_NOTUNIQUE, array('userid'));
        $table->add_index('useridpanoptogroupid', XMLDB_INDEX_UNIQUE, array('userid', 'panoptogroupid'));

        // Conditionally launch create table for panopto_user_access.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Panopto savepoint reached.
        upgrade_mod_savepoint(true, 2017052405, 'panopto');
    }
    if ($oldversion < 2020080405) {
        // Define table panopto_auth_url and create.
        $table = new xmldb_table('panopto_auth_url');

        // Adding fields to table panopto_auth_url.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('panoptosessionid', XMLDB_TYPE_CHAR, '36', null, XMLDB_NOTNULL, null, null);
        $table->add_field('panoptoauthurl', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('validuntil', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table panopto_auth_url.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table panopto_auth_url.
        $table->add_index('validuntil', XMLDB_INDEX_NOTUNIQUE, array('validuntil'));
        $table->add_index('userid', XMLDB_INDEX_NOTUNIQUE, array('userid'));
        $table->add_index('panoptosessionid', XMLDB_INDEX_NOTUNIQUE, array('panoptosessionid'));

        // Conditionally launch create table for panopto_auth_url.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Panopto savepoint reached.
        upgrade_mod_savepoint(true, 2020080405, 'panopto');
    }
    return true;
}
