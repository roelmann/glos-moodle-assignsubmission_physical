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
 * Upgrade code for the submission_physical module.
 *
 * @package   assignsubmission_physical
 * @copyright 2018 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @author    Dez Glidden <dez.glidden@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Stub for upgrade code
 * @param int $oldversion
 * @return bool
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the assignsubmission_physical database table where versions are under the last modified date
 * @param  integer $oldversion The version to compare against the change
 * @return boolean             True once compared & if necessary, updated
 */
function xmldb_assignsubmission_physical_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2018051000) {

        // Define field cmid to be added to assignsubmission_physical.
        $table = new xmldb_table('assignsubmission_barcode');
        $field = new xmldb_field('cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'barcode');

        // Conditionally launch add field cmid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Physical savepoint reached.
        upgrade_plugin_savepoint(true, 2018051000, 'assignsubmission', 'physical');
    }

    if ($oldversion < 2018092802) {
        // Rename the capability to same as the plugin name, ie. physical instead of barcode
        $DB->set_field('capabilities', 'name', 'assignsubmission/physical:scan', array('name' => 'assignsubmission/barcode:scan ',
                                                                                       'component' => 'assignsubmission_physical'));

        // Define table assignsubmission_barcode to be renamed to assignsubmission_physical.
        $table = new xmldb_table('assignsubmission_barcode');

        // Launch rename table for assignsubmission_physical.
        $dbman->rename_table($table, 'assignsubmission_physical');

        // Physical savepoint reached.
        upgrade_plugin_savepoint(true, 2018092802, 'assignsubmission', 'physical');
    }

    return true;
}
