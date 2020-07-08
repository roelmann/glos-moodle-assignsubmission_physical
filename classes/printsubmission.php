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
 * This file contains the class for printing physical submissions from the grading view.
 *
 * @package   assignsubmission_physical
 * @copyright 2018 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @author    Dez Glidden <dez.glidden@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_physical;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/pdflib.php');
require_once($CFG->dirroot . '/mod/assign/gradingtable.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->dirroot . '/mod/assign/submission/physical/lib.php');

/**
 * Print physical submissions, displayed in a HTML table.
 *
 * @copyright 2018 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class printsubmission  {
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
     * The assign object that the submission relates to
     * @var object
     */
    private $assign;
    /**
     * The assignment id
     * @var int
     */
    private $assignmentid;
    /**
     * The assignment name
     * @var string
     */
    private $assignmentname;
    /**
     * The student heading for displaying on the individual submissions pdf printout
     * @var string
     */
    private $studentheading;
    /**
     * The email heading for displaying on the individual submissions pdf printout
     * @var string
     */
    private $emailheading;


    /**
     * Construct a new instance from the context and course module id.
     *
     * @param objects $data Data object containing the context and course module id.
     */
    public function __construct($data) {
        $this->set_context($data->context);
        $this->set_cmid($data->cmid);
        $this->set_course_assignment($data);
        $this->set_coursestudents();
        $this->set_coursestudentids();
        $this->generate_missing_submissions();
        $this->set_coursestudent_submission_details();
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
     * Set the course assignment name.
     *
     * @param object $data The course and assignment parent object.
     */
    private function set_course_assignment($data) {
        $this->assign = $data->assign;
        $this->course = $data->course;
        $this->assignmentid = $this->assign->get_instance()->id;
        $this->assignmentname = $data->assign->get_instance()->name;
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
     * Where there are submissions yet to be generated for the course students, generate them.
     *
     * @return void
     */
    private function generate_missing_submissions() {
        global $DB;

        foreach ($this->coursestudents as $student) {
            $submission = $this->assign->get_user_submission($student->id, false);
            if (! $submission) {
                $submission = new \stdClass();
                $now = time();

                $assignsubmission = new \stdClass();
                $assignsubmission->assignment = $this->assignmentid;
                $assignsubmission->userid = $student->id;
                $assignsubmission->timemodified = $now;
                $assignsubmission->timecreated = $now;
                $assignsubmission->status = ASSIGN_SUBMISSION_STATUS_DRAFT;
                $assignsubmission->groupid = 0;
                $assignsubmission->attemptnumber = 0;
                $assignsubmission->latest = 1;
                $submission->id = $DB->insert_record('assign_submission', $assignsubmission, true);
            }

            if (! $record = $DB->get_record('assignsubmission_physical', array('submissionid' => $submission->id))) {
                $assignsubmissionbarcode = new \stdClass();
                $assignsubmissionbarcode->assignmentid = (int) $this->assignmentid;
                $assignsubmissionbarcode->courseid = (int) $this->course->id;
                $assignsubmissionbarcode->groupid = 0;
                $assignsubmissionbarcode->userid = (int) $student->id;
                $assignsubmissionbarcode->submissionid = (int) $submission->id;
                $assignsubmissionbarcode->barcode = assignsubmission_physical_generate_barcode($this->course->id);
                $assignsubmissionbarcode->cmid = (int) $this->cmid;
                $DB->insert_record('assignsubmission_physical', $assignsubmissionbarcode);
            }
        }
    }


    /**
     * Set the student details, including the barcode & status of the submission.
     */
    private function set_coursestudent_submission_details() {
        global $DB;

        list($insql, $inparams) = $DB->get_in_or_equal($this->coursestudentids);
        $params = array_merge(array($this->assignmentid), $inparams);

        $studentsql = "
                SELECT u.id, u.firstname, u.lastname, u.email,
                       a.status,
                       b.barcode
                  FROM {user} u
                  JOIN {assignsubmission_physical} b ON u.id = b.userid AND b.assignmentid = ?
                  JOIN {assign_submission} a ON a.id = b.submissionid AND a.userid = b.userid
                 WHERE u.id $insql";

        $submissionrecords = $DB->get_records_sql($studentsql, $params);

        foreach ($submissionrecords as $record) {
            $this->coursestudents[$record->id]->status = $record->status;
            $this->coursestudents[$record->id]->barcode = $record->barcode;
        }
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
     * Set the headings for the table of the pdf document
     */
    private function set_htmltable_headings() {
        $this->nameandemailheading     = get_string('nameandemail', 'assignsubmission_physical');
        $this->assignmentstatusheading = get_string('assignmentstatus', 'assignsubmission_physical');
        $this->barcodeheading          = get_string('barcode', 'assignsubmission_physical');
        $this->studentheading          = get_string('student', 'assignsubmission_physical');
        $this->emailheading            = get_string('email', 'assignsubmission_physical');

    }


    /**
     * Generate a new pdf document
     *
     * @return void
     */
    public function generate_pdf_content() {
        $rowsperpage = 11;
        $yposition = 34;
        $content = '';
        $i = 0;

        $pdf = new \pdf();
        $pdf->setPrintHeader(true);
        $pdf->setPrintFooter(true);
        $pdf->AddPage();
        $header = $this->generate_pdf_table_header();
        $pdf->writeHTMLCell(195, 20, 5, 10, $header);

        foreach ($this->coursestudents as $student) {

            $content .= $this->generate_pdf_table_row($student);
            $pdf->writeHTMLCell(115, 2, 5, 31, $content);

            if (!empty($student->barcode)) {
                $pdf->write1DBarcode(
                    $student->barcode,
                    'CODABAR',
                    122,
                    $yposition,
                    180,
                    6,
                    '',
                    ['text' => true]
                );
            }
            $yposition += 21;

            $i++;
            if ($i === $rowsperpage) {
                $pdf->AddPage();
                $yposition = 34;
                $i = 0;
                $header = $this->generate_pdf_table_header();
                $pdf->writeHTMLCell(195, 20, 5, 10, $header);
                $content = '';
            }
        }

        $pdf->Output("$this->assignmentname.pdf");
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
                        <tr rowspan="2">
                            <td colspan="40">
                                <strong>$this->courseassignmentname</strong>
                            </td>
                        </tr>
                        <tr>
                            <td>
                            </td>
                        </tr>
                    </table>
EOD;
    }


    /**
     * Generate a new row of content for the pdf document
     *
     * @param  object $student Student object
     * @return strng           The new row of html
     */
    private function generate_pdf_table_row($student) {
        return <<<EOD
        <table>
            <tr class="tr b">
                <td colspan="20" class="inline-text">
                    <strong>$this->studentheading</strong>: $student->firstname $student->lastname
                </td>
            </tr>
            <tr>
                <td colspan="20" class="inline-text">
                    <strong>$this->assignmentstatusheading</strong>: $student->status
                </td>
            </tr>
            <tr rowspan="2">
                <td colspan="40" class="inline-text">
                    <strong>$this->emailheading</strong>: $student->email
                </td>
            </tr>
            <tr>
                <td>
                </td>
            </tr>
        </table>
EOD;
    }

}
