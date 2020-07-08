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
 * This file contains the moodle functions for the physical submission plugin
 *
 * @package   assignsubmission_physical
 * @copyright 2018 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @author    Dez Glidden <dez.glidden@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/renderer.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->dirroot . '/mod/assign/submission/physical/classes/grading.php');

/**
 * Display the custom grading form with the additional "scan barcode" call to action
 * @param  mixed $data    The course, context and course module objects
 * @param  mixed $summary The assign grading summary renderable object
 * @param  mixed $assign  The assign object
 * @return void
 */
function assignsubmission_physical_render_custom_grading_view($data, $summary, $assign) {
    global $PAGE, $OUTPUT, $USER;

    $grading  = new \assignsubmission_physical\custom_summary_grading_form($PAGE, 'course');
    $gradingsummary = $grading->render_assign_grading_staff_summary($summary);

    $PAGE->set_url(new moodle_url('/mod/assign/submission/physical/grading.php', ['id' => $data->cm->id]));
    $PAGE->set_pagelayout('incourse');
    $PAGE->set_title($data->course->shortname . ': ' . $data->assign->name);
    $PAGE->set_heading($data->course->fullname);
    $PAGE->set_activity_record($data->assign);

    echo $OUTPUT->header();
    echo $OUTPUT->heading($data->assign->name);
    echo $data->assign->intro;
    echo $gradingsummary;
    echo $OUTPUT->footer();
    return;
}


/**
 * Render the student submission view, includes the "print coversheet" button
 * @param  object $data       Course and assignment data
 * @param  object $assign     The assignment summary
 * @param  object $submission Submission data
 * @return void
 */
function assignsubmission_physical_render_custom_student_submission_view($data, $assign, $submission) {
    global $PAGE, $OUTPUT;

    $renderer     = new custom_assign_submission_form($PAGE, 'course');
    $status       = $renderer->render_custom_assign_submission_status($submission, $assign);
    $instructions = $PAGE->get_renderer('assignsubmission_physical');

    echo $OUTPUT->header();
    echo $OUTPUT->heading($data->assign->name);
    echo $data->assign->intro;
    echo $OUTPUT->heading(get_string('howtosubmit', 'assignsubmission_physical'), 4);
    echo $instructions->render_submission_location_details($data);
    echo $status;
    echo $OUTPUT->footer();
    return;
}


/**
 * Check to see if the user is a student and not an admin user
 * @param object $assign    The assignment object
 * @param string $userid    The id of the user to check
 * @return boolean true if the user is a student
 */
function assignsubmission_physical_is_student($assign, $userid) {
    $canview  = $assign->can_view_submission($userid);
    $notadmin = !is_siteadmin($userid);
    if ($notadmin && $canview) {
        return true;
    }
    return false;
}


/**
 * Replace all non alphabetical characters with an underline
 * An example is: Wilson's Building = wilson_s_building
 * @param  string $location     The building location for the physical hand-in
 * @return string               Returns a lowercase alphabetical string
 */
function assignsubmission_physical_clense_location($location) {
    return strtolower(preg_replace('/\W/', '_', trim($location)));
}


/**
 * Get all the locations for physical submissions
 * @return array    An array containing both the location and the location submission details
 */
function assignsubmission_physical_get_submission_locations() {
    global $DB;

    $conditions = array('plugin' => 'assignsubmission_physical', 'name' => 'locationsettings');
    $locations  = $DB->get_record('config_plugins', $conditions, 'value', IGNORE_MISSING);
    $locations  = explode(PHP_EOL, $locations->value);

    for ($i = 0; $i < count($locations); $i++) {
        $building = explode('|', $locations[$i]);
        $buildings[assignsubmission_physical_clense_location($building[0])]['location']     = trim($building[0]);
        $buildings[assignsubmission_physical_clense_location($building[0])]['instructions'] = trim($building[1]);
    }
    return $buildings;
}


/**
 * Get the details of a specific physical submission location
 * @param  string $submission   The clensed (lower cased and underline seperated) submission building
 * An example would be $submission = eec_building which will return an array of that index
 *
 * <code>
 *   eec_building = ['location' => 'EEC Building', 'instructions' => 'Please hand ....']
 * </code>
 * @return array    The location and hand-in instructions
 */
function assignsubmission_physical_get_submission_location_details($submission) {
    $locations = assignsubmission_physical_get_submission_locations();
    foreach ($locations as $key => $value) {
        if ($key === $submission) {
            return $locations[$key];
        }
    }
    return false;
}


/**
 * Get the assignment location using the assignment id
 * @param  int $assignmentid    The assignment id
 * @return object               The assignment location object
 */
function assignsubmission_physical_get_location_by_assignment($assignmentid) {
    global $DB;

    $conditions = array('name' => 'location', 'plugin' => 'physical', 'assignment' => $assignmentid);
    return $DB->get_record('assign_plugin_config', $conditions, 'value', MUST_EXIST);
}


/**
 * Generate the barcode by prefixing the course id with an integer value
 *
 * @param  int $courseid    The course id
 * @return string           The barcode
 */
function assignsubmission_physical_generate_barcode($courseid) {
    global $DB;

    $isunique = false;
    do {
        $barcode  = $courseid . hexdec(hash("crc32", uniqid()));
        if (! $DB->get_record('assignsubmission_physical', array('barcode' => $barcode), 'barcode', IGNORE_MISSING)) {
            $isunique = true;
        }
    } while ($isunique === false);
    return $barcode;
}


/**
 * Save the barcode to the database
 * @param  array $conditions The courseid, assignmentid, groupid and the userid as an array
 * @return boolean           True on success or false on failure
 */
function assignsubmission_physical_save_barcode($conditions = array()) {
    global $DB;
    // Type set the array as an object for the DB insertion.
    settype($conditions, 'object');
    return $DB->insert_record('assignsubmission_physical', $conditions, false, false);
}


/**
 * Check if the barcode exists
 * @param  array  $conditions   Databse fields to check
 * @return object | boolean     Returns false if the barcode does not exist or the barcode if it does
 */
function assignsubmission_physical_barcode_exists($conditions = array()) {
    global $DB;
    if ($record = $DB->get_record('assignsubmission_physical', $conditions, 'barcode', IGNORE_MISSING)) {
        return $record->barcode;
    }
    return false;
}


/**
 * Get the barcode for the assignment
 * @param  string $courseid       Course id
 * @param  string $assignmentid   Assignment id
 * @param  string $groupid        Group id
 * @param  string $userid         User id
 * @param  integer $submissionid  Submission id
 * @param  string $cmid           Course module id
 * @return string                 Returns the barcode as a string
 */
function assignsubmission_physical_get_barcode($courseid, $assignmentid, $groupid, $userid, $submissionid, $cmid) {
    $conditions = array(
        'assignmentid' => $assignmentid,
        'courseid'     => $courseid,
        'groupid'      => $groupid,
        'userid'       => $userid,
        'submissionid' => $submissionid,
        'cmid'         => $cmid
    );
    if ($barcode = assignsubmission_physical_barcode_exists($conditions)) {
        return $barcode;
    }
    $conditions['barcode'] = assignsubmission_physical_generate_barcode($courseid);
    assignsubmission_physical_save_barcode($conditions);
    return $conditions['barcode'];
}


/**
 * Get the specified username format and value.
 * The username can be a custom profile value or a standard value like email. The username format
 * to use is set in the plugin admin settings. This function returns both the format to use and
 * the value for the student.
 * An example would be: Student ID = 334658442
 * @param  array $params    An array of query parameters consisting of the user id and custom user setting id
 * @return object           The username object containing user id, username format and username value
 */
function assignsubmission_physical_get_username($params = null) {
    global $DB;

    $format = explode('_', $params[1]);
    // Check if the format is a custom user profile field. If it is then get the name and value.
    if ($format[0] === 'uif') {
        $sql = "SELECT d.userid,
                       f.name,
                       d.data
                  FROM {user_info_field} f
                  JOIN {user_info_data} d ON d.fieldid = f.id
                 WHERE d.userid = ?
                   AND f.id = ?";

        $params[1] = $format[1];

        if ($username = $DB->get_record_sql($sql, $params, $strictness = IGNORE_MISSING)) {
            return $username;
        }
    }
    // Check if the format is from the user table. Either idnumber, username or email.
    if ($format[0] === 'user') {
        $usernames = assignsubmission_physical_default_username_values();
        $username  = $usernames[$params[1]];
        $name      = $username['name'];
        $data      = $username['field'];
        unset($params[1]);

        $sql = "SELECT id as userid,
                       $data as data
                  FROM {user}
                 WHERE id = ?";

        if ($username = $DB->get_record_sql($sql, $params, $strictness = IGNORE_MISSING)) {
            $username->name = $name;
            return $username;
        }
    }

    $empty = new stdClass();
    $empty->userid = '';
    $empty->name   = '';
    $empty->data   = '';
    return $empty;
}


/**
 * Get the 3 default username formats and values.
 * These are used to retrieve the fields from the user table. For example, where the
 * user has select Email Address, the db query will use 'email'
 *
 * @return array   The 3 default username names and their database field names
 */
function assignsubmission_physical_default_username_values() {
    $fields = array(
        'user_1' => array('name' => 'Username', 'field' => 'username'),
        'user_2' => array('name' => 'ID Number', 'field' => 'idnumber'),
        'user_3' => array('name' => 'Email Address', 'field' => 'email')
    );
    return $fields;
}


/**
 * Get the participant id which is used for blindmarking
 *
 * @param  int $userid     The id of the user
 * @param  int $assignment The assignment id
 * @return string          The database record containing the particpant id
 */
function assignsubmission_physical_get_participantid($userid, $assignment) {
    global $DB;

    $sql = "SELECT id AS participantid
              FROM {assign_user_mapping}
             WHERE userid = ?
               AND assignment = ?";
    if ($record = $DB->get_record_sql($sql, array('userid' => $userid, 'assignment' => $assignment), IGNORE_MISSING)) {
        return $record->participantid;
    }
    return '';
}


/**
 * Check if the submission is a group assignment.
 *
 * @param  object $assign An assign object
 * @return  Returns true or false, depending whether the assignment is a group assignment
 */
function assignsubmission_physical_is_group_assignment($assign) {
    return (isset($assign->get_instance()->teamsubmission) && $assign->get_instance()->teamsubmission === '1') ? true : false;
}
