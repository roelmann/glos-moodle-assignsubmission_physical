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
 * This file contains the definition for the library class for physical submission plugin
 *
 * @package   assignsubmission_physical
 * @copyright 2018 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @author    Dez Glidden <dez.glidden@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once('lib.php');

/**
 * Physical submission class
 * @copyright 2018 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @author    Dez Glidden <dez.glidden@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_submission_physical extends assign_submission_plugin {

    /**
     * Get the name of the submission plugin
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'assignsubmission_physical');
    }


    /**
     * Get the settings section to the assignment creation form for creating a physical submission
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform) {
        global $DB, $PAGE;

        $PAGE->requires->js_call_amd('assignsubmission_physical/index', 'enhanceSettings');

        $conditions = array('plugin' => 'assignsubmission_physical');
        $records    = $DB->get_records('config_plugins', $conditions);
        // Set the initial location and day release settings.
        foreach ($records as $key => $value) {
            if ($records[$key]->name === 'locationsettings') {
                $locations = $records[$key]->value;
            }
            if ($records[$key]->name === 'releasesettings') {
                $dayrelease = $records[$key]->value;
            }
        }

        $buildings  = $this->get_building_locations($locations);

        $selectlocations = $mform->addElement('select',
                                              'location',
                                              get_string('submissionlocation', 'assignsubmission_physical'),
                                              $buildings,
                                              []);
        $mform->addHelpButton('location', 'locationhelp', 'assignsubmission_physical');
        $selectdays = $mform->addElement('select',
                                         'dayrelease',
                                         get_string('releasedays', 'assignsubmission_physical'),
                                         $this->populate_release_days(),
                                         []);
        $mform->addHelpButton('dayrelease', 'dayreleasehelp', 'assignsubmission_physical');
        $mform->disabledIf('location',
                           'assignsubmission_physical_enabled',
                           'notchecked');
        $mform->disabledIf('dayrelease',
                           'assignsubmission_physical_enabled',
                           'notchecked');
        // Set the default value if alraedy set.
        if ($default = $this->get_config('location')) {
            $selectlocations->setSelected($default);
        }
        if ($defaultdays = $this->get_config('dayrelease')) {
            $selectdays->setSelected($defaultdays);
        } else {
            $selectdays->setSelected($dayrelease);
        }
    }


    /**
     * Save the settings for the barcode submission plugin
     * @param stdClass $data    The submitted form data object
     * @return bool
     */
    public function save_settings(stdClass $data) {
        $this->set_config('location', $data->location);
        $this->set_config('dayrelease', $data->dayrelease);
        return true;
    }


    /**
     * Get all the building locations for the physical hand-in
     * @param  string $locations A pipe delimted string consisting of both the building and location description
     * @return array             A list of the buildings only, with the description
     */
    private function get_building_locations($locations) {
        $buildings = array('' => 'Select...');
        $locations = explode(PHP_EOL, $locations);

        for ($i = 0; $i < count($locations); $i++) {
            $building = explode('|', $locations[$i]);
            $buildings[$this->clense_location($building[0])] = $building[0];
        }
        return $buildings;
    }


    /**
     * Replace all non alphabetical characters with an underline
     * An example: Wilson's Building = wilson_s_building
     * @param  string $location     The building location for the physical hand-in
     * @return string               Returns a lowercase alphabetical string
     */
    private function clense_location($location) {
        return strtolower(preg_replace('/\W/', '_', trim($location)));
    }


    /**
     * Check to see if the user is a student and not an admin user
     * @return boolean true if the user is a student
     */
    private function is_student() {
        global $USER;

        $assign   = $this->assignment;
        $canview  = $assign->can_view_submission($USER->id);
        $notadmin = ! is_siteadmin($USER->id);
        if ($notadmin && $canview) {
            return true;
        }
        return false;
    }


    /**
     * This allows a plugin to render an introductory section which is displayed
     * right below the activity's "intro" section on the main assignment page.
     *
     * The header redirects a grader to the custom grading page where the scan barcodes option
     * is displayed alongside the print submission barcodes option. If the user is not a student
     * then they will view the standard submission page.
     *
     * Moodle's submission plugin method view_page($action) is not honoured / is buggy hence
     * the need for a redirect here and not in view_page($action)
     */
    public function view_header() {
        $id                = optional_param('id', 0, PARAM_INT);
        list($course, $cm) = get_course_and_cm_from_cmid($id, 'assign');
        $context           = context_module::instance($cm->id);

        if ($this->assignment->get_instance()->teamsubmission === '0') {
            $groupid = '0';
        } else {
            $groupid = $this->assignment->get_instance()->teamsubmissiongroupingid;
        }

        // If the user can scan barcodes then redirect the user to the custom grading summary view.
        if (has_capability('assignsubmission/physical:scan', $context)) {
            $url = new moodle_url('/mod/assign/submission/physical/grading.php', ['id' => $id]);
            redirect($url);
        }
    }


    /**
     * Confirm the lastest submission has been submitted
     * @param  int $assignmentid    The id of assignment to check
     * @param  string $groupid      The id of group to check if set
     * @return mixed|boolean        Returns record if the latest submission is submitted, false if not
     */
    private function submitted_assignment($assignmentid, $groupid) {
        global $DB, $USER;

        $conditions = array(
            'status'     => 'submitted',
            'latest'     => '1',
            'assignment' => $assignmentid,
            'userid'     => $USER->id,
            'groupid'    => $groupid);

        return $DB->get_record('assign_submission', $conditions, '*', IGNORE_MISSING);
    }


    /**
     * Populate the values for the select dropdown list in the assignment settings
     * @return array The populated number of days with a description for each day
     */
    private function populate_release_days() {
        $days[0] = get_string('duedate', 'assignsubmission_physical');
        $days[1] = 1 . ' ' . get_string('day', 'assignsubmission_physical');
        for ($i = 2; $i <= 30; $i++) {
            $days[$i] = $i . ' ' . get_string('days', 'assignsubmission_physical');
        }
        return $days;
    }


    /**
     * Get any additional fields for the submission/grading form for this assignment.
     *
     * @param mixed $submissionorgrade submission|grade - For submission plugins this is the submission data,
     *                                                    for feedback plugins it is the grade data
     * @param MoodleQuickForm $mform - This is the form
     * @param stdClass $data - This is the form data that can be modified for example by a filemanager element
     * @param int $userid - This is the userid for the current submission.
     *                      This is passed separately as there may not yet be a submission or grade.
     * @return boolean - true if we added anything to the form
     */
    public function get_form_elements_for_user($submissionorgrade, MoodleQuickForm $mform, stdClass $data, $userid) {
        global $CFG;

        $id               = optional_param('id', 0, PARAM_INT);
        // Calculate the release date.
        $days             = $this->get_config('dayrelease');
        $now              = new DateTime();
        $timestamp        = $now->getTimestamp();
        $duedate          = $now->setTimestamp($this->assignment->get_instance()->duedate);
        $releasetimestamp = strtotime("- $days Days", $duedate->getTimestamp());
        $releasedate      = new DateTime();
        $releasedate      = $releasedate->setTimestamp($releasetimestamp);
        $canrelease       = ($timestamp > $releasetimestamp) ? true : false;

        $dateavailable = date('jS F', $releasetimestamp);
        if ($days === '0') {
            $dateavailable .= get_string('onduedate', 'assignsubmission_physical');
        } else {
            $dateavailable .= ' (';
            $dateavailable .= $now->diff($releasedate)->format("%a").get_string('daysbeforedeadline', 'assignsubmission_physical');
            $dateavailable .= ')';
        }

        $mform->addElement('html', '<div class="assignsubmission-physical-submission">');
        $mform->addElement('html', '<h5 class="some">'.get_string('coversheet', 'assignsubmission_physical').'</h5>');
        if ($canrelease) {
            $mform->addElement('html',
                               '<p class="some">'.get_string('coversheetinstruction', 'assignsubmission_physical').'</p>');
            $mform->addElement('html',
                               "<img src=\"$CFG->wwwroot/mod/assign/submission/physical/pix/pdf-image.jpg\" width=\"100\" " .
                               "height=\"100\" alt=\"" . get_string('pdfimage', 'assignsubmission_physical') .
                               "\" class=\"assignsubmission-physical-block-center assignsubmission-physical-vertical-breathe\" />");
            $mform->addElement('html', '<div class="assignsubmission-physical-block-center assignsubmission-physical-vertical-breathe assignsubmission-physical-buttons">');
            $mform->addElement('html',
                               "<a href=\"$CFG->wwwroot/mod/assign/submission/physical/coversheet.php" .
                               "?id=$id&submission=$submissionorgrade->id&format=D\" class=\"btn btn-primary assignsubmission-physical-btn\" download>" .
                               get_string('download', 'assignsubmission_physical') . "</a>");
            $mform->addElement('html',
                               "<a href=\"$CFG->wwwroot/mod/assign/submission/physical/coversheet.php" .
                               "?id=$id&submission=$submissionorgrade->id&format=I\" class=\"btn btn-secondary assignsubmission-physical-btn\"" .
                               "target=\"_blank\">" . get_string('preview', 'assignsubmission_physical') . "</a>");
            $mform->addElement('html', '</div>');
        } else {
            $mform->addElement('html',
                               '<p class="some">' .
                               get_string('coversheetdelayed', 'assignsubmission_physical', $dateavailable) .
                               '</p>');
        }
        $mform->addElement('html', '</div>');
        return true;
    }


    /**
     * Is this assignment plugin empty?
     * This is set to true to permanently prevent the user from submitting an assignment since the submission for
     * physical assignments are via the barcode scanner local plugin.
     *
     * @param stdClass $submissionorgrade assign_submission or assign_grade
     * @return bool
     */
    public function is_empty(stdClass $submissionorgrade) {
        return true;
    }


    /**
     * If this plugin should not include a column in the grading table or a row on the summary page
     * then return false
     *
     * @return bool
     */
    public function has_user_summary() {
        return false;
    }


    /**
     * Check if the submission plugin has all the required data to allow the work
     * to be submitted for grading
     * @param stdClass $submission the assign_submission record being submitted.
     * @return bool|string 'true' if OK to proceed with submission, otherwise a
     *                        a message to display to the user
     */
    public function precheck_submission($submission) {
        return get_string('submissionmessage', 'assignsubmission_physical');
    }


    /**
     * Check if a barcode already exists in the database.
     *
     * @return boolean         True if the barcode exists
     */
    private function barcode_does_exists() {
        global $DB, $USER;

        $conditions = array(
            'assignmentid' => $this->assignment->get_instance()->id,
            'courseid'     => $this->assignment->get_instance()->course,
            'groupid'      => '',
            'userid'       => $USER->id,
            'submissionid' => '',
            'cmid'         => ''
        );
        if ($DB->get_record('assignsubmission_physical', $conditions, 'barcode', IGNORE_MISSING)) {
            return true;
        }
        return false;
    }


    /**
     * Generate a unqiue barcode that is not already stored in the database.
     *
     * @return string The unique barcode
     */
    private function generate_unique_barcode() {
        global $DB;

        $isunique = false;
        do {
            $barcode  = $this->assignment->get_instance()->course . hexdec(hash("crc32", uniqid()));
            if (! $this->barcode_does_exists($barcode)) {
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
    private function save_barcode($conditions = array()) {
        global $DB;
        // Type set the array as an object for the DB insertion.
        settype($conditions, 'object');
        return $DB->insert_record('assignsubmission_physical', $conditions, false, false);
    }
}
