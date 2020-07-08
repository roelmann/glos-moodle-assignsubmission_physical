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
 * This file defines teh admin settings for the physical submission plugin
 *
 * @package   assignsubmission_physical
 * @copyright 2018 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @author    Dez Glidden <dez.glidden@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $DB;

// Add a checkbox to determine whether or not the assignment type should be set as default.
$settings->add(new admin_setting_configcheckbox('assignsubmission_physical/default',
                   new lang_string('default', 'assignsubmission_physical'),
                   new lang_string('default_help', 'assignsubmission_physical'), 0));

// Add a checkbox to determine whether or not the assignment type should be set as default.
$settings->add(new admin_setting_configcheckbox('assignsubmission_physical/submitontime',
                   new lang_string('submitontime', 'assignsubmission_physical'),
                   new lang_string('submitontime_help', 'assignsubmission_physical'), 0));

// Add a select list to display the user name formats using the custom user profile fields.
$options = $DB->get_records('user_info_field', [], '', $fields = 'id, name', 0, 0);
$choices = array();
// Add the default values from the user table.
$defaults = array(
    'user_1' => array('name' => 'Username', 'field' => 'username'),
    'user_2' => array('name' => 'ID Number', 'field' => 'idnumber'),
    'user_3' => array('name' => 'Email Address', 'field' => 'email')
);

foreach ($defaults as $key => $value) {
    $choices[$key] = $value['name'];
}

foreach ($options as $option) {
    $choices['uif_'.$option->id] = $option->name;
}

$settings->add(new admin_setting_configselect('assignsubmission_physical/usernamesettings',
                    new lang_string('usernamechoice', 'assignsubmission_physical'),
                    new lang_string('usernamedescription', 'assignsubmission_physical'),
                    'user_1',
                    $choices));

// The default number of days to release the coversheet.
$days[0] = get_string('duedate', 'assignsubmission_physical');
$days[1] = 1 . ' ' . get_string('day', 'assignsubmission_physical');
for ($i = 2; $i <= 30; $i++) {
    $days[$i] = $i . ' ' . get_string('days', 'assignsubmission_physical');
}
$settings->add(new admin_setting_configselect('assignsubmission_physical/releasesettings',
                    new lang_string('releasedays', 'assignsubmission_physical'),
                    new lang_string('releasedescription', 'assignsubmission_physical'),
                    14,
                    $days));

// Add a textarea input for pipe delimted location|instructions.
$settings->add(new admin_setting_configtextarea('assignsubmission_physical/locationsettings',
                    new lang_string('locationname', 'assignsubmission_physical'),
                    new lang_string('locationdescription', 'assignsubmission_physical'),
                    new lang_string('locationdefaultsetting', 'assignsubmission_physical')));
