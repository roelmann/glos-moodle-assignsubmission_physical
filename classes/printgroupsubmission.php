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
 * This file contains the class for printing group physical submissions from the grading view.
 *
 * @package   assignsubmission_physical
 * @copyright 2018 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @author    Dez Glidden <dez.glidden@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_physical;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/pdflib.php');
require_once($CFG->dirroot . '/lib/grouplib.php');
require_once($CFG->dirroot . '/mod/assign/submission/physical/lib.php');

/**
 * Print physical submissions, displayed in a HTML table.
 *
 * @copyright 2018 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class printgroupsubmission  {
    /**
     * The course context object.
     * @var object
     */
    private $context;
    /**
     * The course assignment name.
     * @var string
     */
    private $courseassignmentname;
    /**
     * The users of the course, an array of objects.
     * @var array
     */
    private $coursestudents;
    /**
     * The database ids of the users.
     * @var array
     */
    private $coursestudentids;
    /**
     * The course module id.
     * @var integer
     */
    private $cmid;
    /**
     * Html barcode submission table.
     * @var string
     */
    private $htmltable;
    /**
     * The html table barcode heading
     * @var string
     */
    private $barcodeheading;
    /**
     * The html table name and email heading
     * @var string
     */
    private $nameandemailheading;
    /**
     * The html table assignment status heading
     * @var string
     */
    private $assignmentstatusheading;
    /**
     * The status of the group assignment
     * @var string
     */
    private $groupassignmentstatus = 'no submission details';
    /**
     * The assign object
     * @var object
     */
    private $assign;
    /**
     * The assignment name
     * @var string
     */
    private $assignmentname;
    /**
     * Set the assignment object
     * @var object
     */
    private $printableassignment;
    /**
     * The submission is a group submission
     * @var boolean
     */
    private $isgroupsubmission;
    /**
     * Is the submission a group submission
     * @var boolean
     */
    private $isgrouping;
    /**
     * Is the submission a gropuing submission
     * @var boolean
     */
    private $groupingid;
    /**
     * The course
     * @var object
     */
    private $course;
    /**
     * The course id
     * @var int
     */
    private $courseid;
    /**
     * The assignment groups
     * @var array
     */
    private $groups;
    /**
     * The name of the submission grouping
     * @var string
     */
    private $groupingname;
    /**
     * The grouping members
     * @var string
     */
    private $groupingmembers;
    /**
     * The group heading to display
     * @var string
     */
    private $groupsubmissionheading;
    /**
     * The group members heading
     * @var string
     */
    private $studentmembersheading;
    /**
     * The assignment id
     * @var string
     */
    private $assignmentid;
    /**
     * The assignment barcode;
     * @var integer
     */
    private $barcode;
    /**
     * The chosen username format eg. email, username or student id etc.
     * @var object
     */
    private $usernameformat;
    /**
     * The ids of all the groups in the assignment
     * @var array
     */
    private $groupids = array();
    /**
     * The name of the group in the pdf heading
     * @var string
     */
    private $groupheading;
    /**
     * The name of the grouping in the pdf heading
     * @var string
     */
    private $groupingheading;

    /**
     * Construct a new instance from the context and course module id.
     *
     * @param objects $data Data object containing the context and course module id.
     */
    public function __construct($data) {
        $this->set_context($data->context);
        $this->set_cmid($data->cmid);
        $this->set_course_assignment($data);
        $this->set_username_format();
        $this->set_coursestudents();
        $this->set_coursestudentids();
        $this->set_printable_assignment($data);
        $this->set_submission_type();
        $this->set_course_details($data);
        $this->set_grouping();
        $this->set_groups();
        $this->generate_missing_submissions();
        $this->set_group_submission_details();
        $this->set_group_members();
        $this->set_htmltable_headings();
    }


    /**
     * Set the context.
     *
     * @param object $context The course context object.
     */
    private function set_context($context) {
        $this->context = $context;
    }


    /**
     * Set the course module id.
     *
     * @param integer $cmid Course module id
     */
    private function set_cmid($cmid) {
        $this->cmid = $cmid;
    }


    /**
     * Set the course assignment name.
     *
     * @param object $data The course and assignment parent object.
     */
    private function set_course_assignment($data) {
        $this->assign = $data->assign;
        $this->assignmentname = $data->assign->get_instance()->name;
        $this->course = $data->course;
        $this->courseassignmentname = $data->course->fullname . ' | ' . $this->assignmentname;
        if (strlen($this->courseassignmentname) > 108) {
            $this->courseassignmentname = substr($this->courseassignmentname, 0, 104) . '...';
        }
    }


    /**
     * Set the students for the specified course.
     */
    private function set_coursestudents() {
        $this->coursestudents = get_enrolled_users($this->context, 'mod/assign:submit');
    }


    /**
     * Set the ids for the students of the course.
     */
    private function set_coursestudentids() {
        $this->coursestudentids = array_column($this->coursestudents, 'id');
    }


    /**
     * Set the group submission details, including the barcode & status of the submission.
     */
    private function set_group_submission_details() {
        global $DB;

        list($insql, $inparams) = $DB->get_in_or_equal($this->groupids);
        $params = array_merge($inparams, array($this->assignmentid));
        $sql = "SELECT s.id, s.status, s.groupid,
                       b.barcode, b.assignmentid
                  FROM mdl_assign_submission s
                  JOIN mdl_assignsubmission_physical b ON s.id = b.submissionid AND s.groupid = b.groupid
                 WHERE s.groupid $insql AND b.assignmentid = ?";
        $records = $DB->get_records_sql($sql, $params);

        foreach ($records as $record) {
            $this->groups[$record->groupid]->barcode = $record->barcode;
            $this->groups[$record->groupid]->groupassignmentstatus = $record->status;
        }
    }


    /**
     * Set the course and course id
     * @param object $data The data object that contains the course object
     */
    private function set_course_details($data) {
        $this->course = $data->course;
        $this->courseid = $data->course->id;
    }


    /**
     * Set the groupings
     */
    private function set_grouping() {
        $this->isgrouping = ($this->printableassignment->teamsubmissiongroupingid === '0') ? false : true;
        $this->groupingid = $this->printableassignment->teamsubmissiongroupingid;
        $this->groupingname = '';
        $this->groupingmembers = '';

        if ($this->isgrouping) {
            $this->groupingname = groups_get_grouping($this->groupingid, 'name', IGNORE_MISSING)->name;
            $this->groupingmembers = groups_get_grouping_members($this->groupingid,
                                                                 'u.id, u.firstname, u.lastname',
                                                                 'lastname ASC');

            $count = count($this->groupingmembers);
            $temp = '';
            $i = 0;
            foreach ($this->groupingmembers as $key) {
                $temp .= "$key->firstname $key->lastname";
                $i++;
                if ($i < $count) {
                    $temp .= ', ';
                }
            }
            $this->groupingmembers = $temp;
            unset($temp);
            unset($count);
            unset($i);
        }

        if (!empty($this->get_grouping_physical_assignment_details())) {
            $this->groupassignmentstatus = $this->get_grouping_physical_assignment_details()->status;
            $this->barcode = $this->get_grouping_physical_assignment_details()->barcode;
        }
    }


    /**
     * Set the details of each group related to the submission
     */
    private function set_groups() {
        $this->groups = array();
        if ($this->isgroupsubmission) {
            // Get the groups.
            $this->groups = groups_get_all_groups($this->courseid, 0, $this->groupingid, 'g.*');
        }
        foreach ($this->groups as $group) {
            $this->groupids[] = $group->id;
        }
    }


    /**
     * Where there are submissions yet to be generated for the course groups, generate them.
     *
     * @return void
     */
    private function generate_missing_submissions() {
        global $DB;
        foreach ($this->groups as $group) {
            $submission = $this->assign->get_group_submission(0, $group->id, false);
            if (! $submission) {
                $submission = $this->assign->get_group_submission(0, $group->id, true);
            }

            if (! $record = $DB->get_record('assignsubmission_physical', array('submissionid' => $submission->id))) {
                $assignsubmissionbarcode = new \stdClass();
                $assignsubmissionbarcode->assignmentid = (int) $this->assignmentid;
                $assignsubmissionbarcode->courseid = (int) $this->courseid;
                $assignsubmissionbarcode->groupid = $group->id;
                $assignsubmissionbarcode->userid = 0;
                $assignsubmissionbarcode->submissionid = (int) $submission->id;
                $assignsubmissionbarcode->barcode = assignsubmission_physical_generate_barcode($this->courseid);
                $assignsubmissionbarcode->cmid = (int) $this->cmid;
                $DB->insert_record('assignsubmission_physical', $assignsubmissionbarcode);
            }
        }
    }


    /**
     * Set the headings for the table of the pdf document
     */
    private function set_htmltable_headings() {
        $this->nameandemailheading     = get_string('nameandemail', 'assignsubmission_physical');
        $this->assignmentstatusheading = get_string('assignmentstatus', 'assignsubmission_physical');
        $this->barcodeheading          = get_string('barcode', 'assignsubmission_physical');
        $this->groupheading            = get_string('groupheading', 'assignsubmission_physical');
        $this->groupingheading         = get_string('groupingheading', 'assignsubmission_physical');
        $this->studentmembersheading   = get_string('groupmembersheading', 'assignsubmission_physical');
        if ($this->isgroupsubmission && !$this->isgrouping) {
            $this->groupsubmissionheading = $this->groupheading;
        }
        if ($this->isgrouping) {
            $this->groupingheading .= ": $this->groupingname";
            $this->groupsubmissionheading = $this->groupingheading;
        }
    }


    /**
     * Set the printable assignent object and id
     * @param object $data The data object passed to the constructor which contains the child assign object
     */
    private function set_printable_assignment($data) {
        $this->printableassignment = $data->assign->get_instance();
        $this->assignmentid = $data->assign->get_instance()->id;
    }


    /**
     * Set whether or not the submission is a group submission
     */
    private function set_submission_type() {
        $this->isgroupsubmission = ($this->printableassignment->teamsubmission === '1') ? true : false;
    }


    /**
     * Set the members of each group
     */
    private function set_group_members() {
        foreach ($this->groups as $group) {
            $this->groups[$group->id]->headingname = get_string('group', 'assignsubmission_physical') .
                                                       ': ' . $this->groups[$group->id]->name;
            $temp = '';
            $i = 0;
            if ($this->usernameformat->format === 'user') {
                $this->groups[$group->id]->membernames = groups_get_members($group->id,
                                                                            "u.id, u.firstname, u.lastname,
                                                                             u.{$this->usernameformat->field} AS username",
                                                                            'lastname ASC');
                $count = count($this->groups[$group->id]->membernames);

                foreach ($this->groups[$group->id]->membernames as $member) {
                    $temp .= "$member->firstname $member->lastname ($member->username)";
                    $i++;
                    if ($i < $count) {
                        $temp .= ', ';
                    }
                }
            } else if ($this->usernameformat->format === 'uif') {
                $this->groups[$group->id]->membernames = groups_get_members($group->id,
                                                                            'u.id, u.firstname, u.lastname',
                                                                            'lastname ASC');
                $count = count($this->groups[$group->id]->membernames);
                $userids = array_column($this->groups[$group->id]->membernames, 'id');
                $usernames = $this->get_custom_usernames($userids);

                foreach ($this->groups[$group->id]->membernames as $member) {
                    $username = (isset($usernames[$member->id])) ? $usernames[$member->id]->username : '';
                    $temp .= "$member->firstname $member->lastname ($username)";
                    $i++;
                    if ($i < $count) {
                        $temp .= ', ';
                    }
                }
            } else {
                $this->groups[$group->id]->membernames = groups_get_members($group->id,
                                                                            "u.id, u.firstname, u.lastname",
                                                                            'lastname ASC');
                $count = count($this->groups[$group->id]->membernames);
                foreach ($this->groups[$group->id]->membernames as $member) {
                    $temp .= "$member->firstname $member->lastname";
                    $i++;
                    if ($i < $count) {
                        $temp .= ', ';
                    }
                }
            }
            $this->groups[$group->id]->membernames = $temp;
        }
    }


    /**
     * Get the grouping assignment details
     *
     * @return object The assignent details containing the barcode & assignment status
     */
    private function get_grouping_physical_assignment_details() {
        global $DB;

        $sql = "
            SELECT s.id,
                   s.status,
                   b.barcode
              FROM {assign_submission} s
         LEFT JOIN {assignsubmission_physical} b ON s.assignment = b.assignmentid
               AND s.groupid = b.groupid
             WHERE s.assignment = ?
               AND s.groupid = ?
               AND s.userid = 0
        ";
        $records = $DB->get_records_sql($sql, array('assignmentid' => $this->assignmentid, 'groupid' => $this->groupingid));
        return reset($records);
    }


    /**
     * Generate a new pdf document
     *
     * @return void
     */
    public function generate_pdf_content() {
        if ($this->isgrouping) {
            return $this->generate_grouping_pdf();
        }
        return $this->generate_group_pdf();
    }


    /**
     * Generate the grouping pdf
     *
     * @return void
     */
    private function generate_grouping_pdf() {
        $content = '';
        $pdf = new \pdf();
        $pdf->setPrintHeader(true);
        $pdf->setPrintFooter(true);
        $pdf->AddPage();
        $content .= $this->add_new_grouping_pdf_page();
        $content .= $this->add_pdf_grouping_members();
        $content .= '</tbody>';
        $pdf->write1DBarcode(
            $this->barcode,
            'CODABAR',
            $x = '110',
            $y = "30",
            $w = '180',
            $h = '8'
        );
        $pdf->writeHTML($content, true, false, false, false, 'L');
        $pdf->Output("$this->assignmentname.pdf");
    }


    /**
     * Generate the group assignment pdf
     *
     * @return void
     */
    private function generate_group_pdf() {
        $content = '';
        $pdf = new \pdf();
        $pdf->setPrintHeader(true);
        $pdf->setPrintFooter(true);
        foreach ($this->groups as $group) {
            $content = '';
            $pdf->AddPage();
            $content .= $this->add_pdf_group_page_heading($group);
            $content .= $this->add_pdf_group_page_members($group);
            $pdf->writeHTMLCell(195, 20, 5, 10, $content);
            $pdf->write1DBarcode(
                $group->barcode,
                'CODABAR',
                $x = '120',
                $y = "24",
                $w = '180',
                $h = '8',
                '',
                ['text' => true]
            );
        }
        $pdf->Output("$this->assignmentname.pdf");
    }


    /**
     * Add the gorup page heading section to the pdf
     * This adds the assignment name, the group name and the assignment status to the top of each pdf page.
     *
     * @param object $group The individual group details
     */
    private function add_pdf_group_page_heading($group) {
        return <<<EOD
            <style>
                table {
                    width: 100%;
                    table-layout:fixed;
                }
                th {
                    font-weight: bold;
                }
                .tr {
                    line-height: 55px;
                    height: 55px;
                }
                td {
                    overflow: hidden;
                    white-space: nowrap;
                }
                .inline-text {
                    line-height: 16px;
                    height: 16px;
                    vertical-align: bottom;
                    padding-top: 5px;
                }
                .inline-text-center {
                    line-height: 32px;
                    height: 32px;
                    vertical-align: bottom;
                }
            </style>
            <table>
                <thead>
                    <tr rowspan="2">
                        <th colspan="40">
                            <strong>$this->courseassignmentname</strong>
                        </th>
                    </tr>
                    <tr>
                        <th>
                        </th>
                    </tr>
                    <tr rowspan="2">
                        <th colspan="28">
                            <strong>$group->headingname</strong>
                        </th>
                    </tr>
                    <tr>
                        <th colspan="28">
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr rowspan="2">
                        <td colspan="40">
                            <strong>$this->assignmentstatusheading: </strong>
                            $group->groupassignmentstatus
                        </td>
                    </tr>
                    <tr>
                        <td colspan="40">
                        </td>
                    </tr>
EOD;
    }


    /**
     * Generate a new row of content that displays the group members
     *
     * @return strng           The new row of html
     */
    private function add_pdf_group_page_members($group) {
        return <<<EOD
                <tr>
                    <td colspan="40">
                        <strong>$this->studentmembersheading</strong>
                    </td>
                </tr>
                <tr class="tr">
                    <td colspan="40" class="inline-text">
                        <span>$group->membernames</span>
                    </td>
                </tr>
            </tbody>
EOD;
    }


    /**
     * Add a warning to the pdf that the assignment barcode has not been generated yet.
     */
    private function add_pdf_warning_barcode_empty() {
        $warning = get_string('barcodenotgeneratedyet', 'assignsubmission_physical');
        return <<<EOD
            <tr class="tr" rowspan="2">
                <td colspan="40" class="inline-text" style="text-align:center">
                    <strong>$warning</strong>
                </td>
            </tr>
            <tr>
                <td colspan="40">
                </td>
            </tr>
EOD;
    }


    /**
     * Add a new group page to the generated pdf document
     */
    private function add_new_grouping_pdf_page() {
        return <<<EOD
            <style>
                table {
                    width: 100%;
                    table-layout:fixed;
                }
                th {
                    font-weight: bold;
                }
                .tr {
                    line-height: 55px;
                    height: 55px;
                }
                td {
                    overflow: hidden;
                    white-space: nowrap;
                }
                .inline-text {
                    line-height: 16px;
                    height: 16px;
                    vertical-align: bottom;
                    padding-top: 5px;
                }
                .inline-text-center {
                    line-height: 32px;
                    height: 32px;
                    vertical-align: bottom;
                }
            </style>
            <table>
                <thead>
                    <tr rowspan="2">
                        <th colspan="40">
                            <strong>$this->courseassignmentname</strong>
                        </th>
                    </tr>
                    <tr>
                        <th>
                        </th>
                    </tr>
                    <tr rowspan="2">
                        <th colspan="28">
                            <strong>$this->groupsubmissionheading</strong>
                        </th>
                    </tr>
                    <tr>
                        <th colspan="28">
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr rowspan="2">
                        <td colspan="40">
                            <strong>$this->assignmentstatusheading: </strong>
                            $this->groupassignmentstatus
                        </td>
                    </tr>
                    <tr>
                        <td colspan="40">
                        </td>
                    </tr>
                    <tr>
                        <td colspan="40">
                            <strong>$this->studentmembersheading</strong>
                        </td>
                    </tr>
EOD;
    }


    /**
     * Generate the header for a new pdf table
     *
     * @return string The html string for a new page table heading section
     */
    private function generate_pdf_table_header() {
        return <<<EOD
                    <style>
                        table {
                            width: 100%;
                            table-layout:fixed;
                        }
                        th {
                            font-weight: bold;
                        }
                        .tr {
                            line-height: 55px;
                            height: 55px;
                        }
                        td {
                            overflow: hidden;
                            white-space: nowrap;
                        }
                        .inline-text {
                            line-height: 16px;
                            height: 16px;
                            vertical-align: bottom;
                            padding-top: 5px;
                        }
                        .inline-text-center {
                            line-height: 32px;
                            height: 32px;
                            vertical-align: bottom;
                        }
                    </style>
                    <table>
                        <thead>
                            <tr rowspan="2">
                                <th colspan="40">
                                    <strong>$this->courseassignmentname</strong>
                                </th>
                            </tr>
                            <tr>
                                <th>
                                </th>
                            </tr>
                            <tr>
                                <th colspan="18">$this->nameandemailheading</th>
                                <th colspan="10">$this->assignmentstatusheading</th>
                                <th colspan="12">$this->barcodeheading</th>
                            </tr>
                        </thead>
                        <tbody>
EOD;
    }


    /**
     * Generate a new row of content that displays the group members
     *
     * @return strng           The new row of html
     */
    private function add_pdf_grouping_members() {
        return <<<EOD
            <tr class="tr">
                <td colspan="40" class="inline-text">
                    <span>$this->groupingmembers</span>
                </td>
            </tr>
EOD;
    }


    /**
     * Set the format of the username which is configured in the plugin admin settings.
     */
    private function set_username_format() {
        $usernamesetting = get_config('assignsubmission_physical', 'usernamesettings');
        $format = preg_split('/_/', get_config('assignsubmission_physical', 'usernamesettings'), -1, null);
        $this->usernameformat = new \stdClass();
        $this->usernameformat->format = $format[0];
        $this->usernameformat->value = $format[1];

        if ($this->usernameformat->format === 'user') {
            $this->usernameformat->name = $this->get_default_usernames()[$usernamesetting]['name'];
            $this->usernameformat->field = $this->get_default_usernames()[$usernamesetting]['field'];
            return;
        }
        if ($this->usernameformat->format === 'uif') {
            $this->usernameformat->name = '';
            $this->usernameformat->field = '';
            return;
        }
    }


    /**
     * Get the default usernames
     *
     * @return array The default username choices set in the user table
     */
    private function get_default_usernames() {
        return array(
            'user_1' => array('name' => 'Username', 'field' => 'username'),
            'user_2' => array('name' => 'ID Number', 'field' => 'idnumber'),
            'user_3' => array('name' => 'Email Address', 'field' => 'email')
        );
    }


    /**
     * Get the custom usernames set under custom user profiles by site admins
     * @param  array $userids An array of each users id used to retrieve the usernames
     * @return array An array of the user id and the username
     */
    private function get_custom_usernames($userids) {
        global $DB;

        list($insql, $inparams) = $DB->get_in_or_equal($userids);
        $params = array($this->usernameformat->value);
        $params = array_merge($params, $inparams);
        $sql = "SELECT d.userid,
                       d.data as username
                  FROM {user_info_field} f
                  JOIN {user_info_data} d ON d.fieldid = f.id
                 WHERE f.id = ?
                   AND d.userid $insql";
        if ($records = $DB->get_records_sql($sql, $params)) {
            return $records;
        }
        return array();
    }
}
