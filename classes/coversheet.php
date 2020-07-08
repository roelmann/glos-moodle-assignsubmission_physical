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
 * Coversheet class used to construct the PDF coversheet for physical assignments
 *
 * @package   assignsubmission_physical
 * @copyright 2018 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @author    Dez Glidden <dez.glidden@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_physical;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../../../config.php');
require_once(__DIR__ . '/../lib.php');
require_once($CFG->libdir . '/pdflib.php');

/**
 * The coversheet class produces the PDF coversheet
 * @copyright 2018 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class coversheet extends \pdf {
    /**
     * The course id
     * @var int
     */
    protected $courseid;
    /**
     * Course short code
     * @var string
     */
    protected $courseshortcode;
    /**
     * Course title
     * @var string
     */
    protected $coursetitle;
    /**
     * Course summary
     * @var string
     */
    protected $coursesummary;
    /**
     * The first name and surname of the student
     * @var string
     */
    protected $studentname;
    /**
     * The assignment extension date
     * @var datetime
     */
    protected $extensiondate;
    /**
     * The assignment cut off date
     * @var datetime
     */
    protected $cutoffdate;
    /**
     * The assignment due date
     * @var datetime
     */
    protected $duedate;
    /**
     * Submission statement
     * @var string
     */
    protected $submissionstatement;
    /**
     * The barcode
     * @var string
     */
    protected $barcode;
    /**
     * The format for the username, eg. "Student ID"
     * @var string
     */
    protected $usernameformat;
    /**
     * Username value
     * @var string
     */
    protected $username;
    /**
     * User id
     * @var int
     */
    protected $userid;
    /**
     * The default format for displaying the PDF
     * @var string
     */
    protected $defaultformat;
    /**
     * The building for the submission
     * @var string
     */
    protected $location;
    /**
     * The submission instructions
     * @var [type]
     */
    protected $locationinstructions;
    /**
     * The assignment id
     * @var int
     */
    protected $assignmentid;
    /**
     * The assignment title
     * @var string
     */
    protected $assignmenttitle;
    /**
     * The assignment description
     * @var string
     */
    protected $assignmentdescription;
    /**
     * The assign object
     * @var object
     */
    protected $assign;
    /**
     * Is the assignment a group assignment
     * @var boolean
     */
    protected $isgroupsubmission;
    /**
     * Is the assignment a grouping
     * @var boolean
     */
    protected $isgrouping;
    /**
     * The group id, can be "0" if it's not a group assignment
     * @var string
     */
    protected $groupid;
    /**
     * Name of the group grouping, blank or the name if it's a grouping
     * @var string
     */
    protected $groupingname;
    /**
     * Type of assignment, either individual or group
     * @var string
     */
    protected $assignmenttype;
    /**
     * The submission id which is unique to the user and or group submission entry in the database
     * @var int
     */
    public $submissionid;
    /**
     * The course module id
     * @var
     */
    protected $cmid;
    /**
     * The new pdf instance
     * @var pdf Object
     */
    protected $pdf;
    /**
     * The participant id if blind marking
     * @var string
     */
    protected $participantid;
    /**
     * The pdf heading for headingcoventryuniversity
     * @var string
     */
    protected $headingcoventryuniversity;
    /**
     * The heading for headingdeclarationform
     * @var string
     */
    protected $headingdeclarationform;
    /**
     * The heading for headingcompletesections
     * @var string
     */
    protected $headingcompletesections;
    /**
     * The heading for headingparticipant
     * @var string
     */
    protected $headingparticipant;
    /**
     * The heading for headingmodulecode
     * @var string
     */
    protected $headingmodulecode;
    /**
     * The heading for headingmoduletitle
     * @var string
     */
    protected $headingmoduletitle;
    /**
     * The heading for headingmoduletutors
     * @var string
     */
    protected $headingmoduletutors;
    /**
     * The heading for headingtutor
     * @var string
     */
    protected $headingtutor;
    /**
     * The heading for headingassignmenttitle
     * @var string
     */
    protected $headingassignmenttitle;
    /**
     * The heading for headingassignmentdescription
     * @var string
     */
    protected $headingassignmentdescription;
    /**
     * The heading for headingsubmissionlocation
     * @var string
     */
    protected $headingsubmissionlocation;
    /**
     * The heading for headingduedate
     * @var string
     */
    protected $headingduedate;
    /**
     * The heading for headingdescriptionofwork
     * @var string
     */
    protected $headingdescriptionofwork;
    /**
     * The heading for headingblindmarking
     * @var string
     */
    protected $headingblindmarking;
    /**
     * The heading for headingblindmarkinginstruction
     * @var string
     */
    protected $headingblindmarkinginstruction;
    /**
     * The heading for headingassessmenttype
     * @var string
     */
    protected $headingassessmenttype;
    /**
     * The heading for headinggroupingname
     * @var string
     */
    protected $headinggroupingname;
    /**
     * The heading for headingsubmissionstatement
     * @var string
     */
    protected $headingsubmissionstatement;
    /**
     * The heading for headingstudentname
     * @var string
     */
    protected $headingstudentname;
    /**
     * The heading for headingsigned
     * @var string
     */
    protected $headingsigned;
    /**
     * The heading for headingdate
     * @var string
     */
    protected $headingdate;
    /**
     * Whether the assignment is blind marking or not
     * @var string
     */
    protected $isblindmarking;
    /**
     * The user object of the current student
     * @var object
     */
    private $user;
    /**
     * The submission status object for the specific submission
     * @var object
     */
    private $usersubmissionstatus;


    /**
     * Construct a new instance of the coversheet and set all the required properties on instantiation
     * @param assign $assign  The assign object
     * @param user   $USER    The global user object
     * @param object $submission The submission object
     * @param string $format  The format of the coversheet, either download or display in browser
     */
    public function __construct($assign, $USER, $submission, $format) {
        $this->assign = $assign;
        $this->set_user($USER);
        $this->set_user_submission_status();
        $this->set_assignment_submission_statement();
        $this->set_assignment();
        $this->set_user_details();
        $this->defaultformat = $format; // I = Display in the browser, D = Download.
        $this->set_location();
        $this->set_course();
        $this->set_dates();
        $this->set_group();
        $this->set_submission($submission);
        $this->set_barcode();
        $this->set_headings();
        $this->set_blindmarking();
        $this->set_tutors();
        $this->pdf = new \pdf;
    }


    /**
     * Display the 1D CODABAR barcode PDF
     * @return void
     */
    public function display_1d_pdf() {
        $this->pdf->AddFont('helvetica');
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
        $this->pdf->AddPage();
        $this->pdf->writeHTML($this->generate_pdf_content(), true, false, false, false, 'L');
        $this->pdf->write1DBarcode(
            $this->barcode,
            'CODABAR',
            $x = '115',
            $y = '32',
            $w = '300',
            $h = '7',
            $xres = '',
            array('text' => true));
        $this->pdf->Output($this->studentname . '_' . $this->barcode . '.pdf', $this->defaultformat);
    }


    /**
     * Generate the pdf content
     * @return string   Returns the pdf content as a string
     */
    private function generate_pdf_content() {
        $content = <<<EOD
            <style>
                .font-small {
                    font-size:9px;
                    text-align:left;
                }
                .font-blindmarking {
                    font-size: 10px;
                }
                .font-medium {
                    font-size: 12px;
                }
                .font-large {
                     font-size:14;
                     text-align:center;
                     display: block;
                     margin: 0 auto;
                }
                .font-bold {
                    font-weight: 700;
                }
                .text-center {
                    text-align: center;
                }
                table {
                    position: absolute;
                    left: 0;
                    top: 0;
                    width: 100%;
                    border: none;
                    font-family: sans-serif;
                }
                .border-thin {
                    border: 1px solid black;
                }
                .border-thick {
                    border: 3px solid black;
                }
                .border-thin-bottom {
                    border-bottom: 1px solid black;
                }
                .border-thin-left {
                    border-left: 1px solid black;
                    border-bottom: 1px solid black;
                }
                .border-thin-right {
                    border-right: 1px solid black;
                    border-bottom: 1px solid black;
                }
                .border-bottom-none {
                    border-bottom: none;
                    border-left: 1px solid black;
                    border-right: 1px solid black;
                }
                .top-text {
                    vertical-align: top;
                }
                * {
                    position: relative;
                }
                .bottom-border-dashed {
                    border-bottom: 1px dashed black;
                }
                .description-header {
                    height: 100px;
                }
                .description {
                    height: 100px
                    overflow: hidden;
                    white-space: nowrap;
                }
                .addheight {
                    height: 30px;
                }
                .addheight-md {
                    height: 50px;
                }
                .addheight-xl {
                    height: 80px;
                }
            </style>
            <table>
                <tr>
                    <td class="font-medium font-bold text-center" colspan="48">
                        $this->headingcoventryuniversity<br />
                        $this->headingdeclarationform<br />
                        $this->headingcompletesections
                    </td>
                </tr>
                <tr>
                    <td colspan="48"></td>
                </tr>
                <tr>
                    <td colspan="18" class="border-thin">
                        <strong><span class="font-small">$this->headingmodulecode:</span></strong><br />
                        <span class="font-small inner-container">$this->courseshortcode</span>
                    </td>
                    <td colspan="30"></td>
                </tr>
                <tr>
                    <td colspan="48" class="border-thin">
                        <span class="font-bold font-small"><strong>$this->headingmoduletitle:</strong> $this->coursetitle</span>
                    </td>
                </tr>
                <tr>
                    <td colspan="48" rowspan="2" class="border-thin">
                        <span class="font-small"><strong>$this->headingmoduletutors:</strong> $this->tutors</span>
                    </td>
                </tr>
                <tr>
                    <td colspan="48"></td>
                </tr>
                <tr>
                    <td colspan="48" rowspan="2" class="border-thick">
                        <strong><span class="font-small font-bold">$this->headingtutor: </span>
                        </strong>&nbsp;
                    </td>
                </tr>
                <tr>
                    <td colspan="48"></td>
                </tr>
                <tr>
                    <td colspan="48" rowspan="2" class="border-thin">
                        <span class="font-small"><strong>$this->headingassignmenttitle:</strong> $this->assignmenttitle</span>
                    </td>
                </tr>
                <tr>
                    <td colspan="48"></td>
                </tr>
                <tr class="description-header">
                    <td colspan="48" rowspan="2" class="border-thin description">
                        <strong><span class="font-small">$this->headingassignmentdescription:</span></strong>
                        <span class="font-small">$this->assignmentdescription</span>
                    </td>
                </tr>
                <tr>
                    <td colspan="48"></td>
                </tr>
                <tr>
                    <td colspan="48" class="border-thin">
                        <span class="font-small"><strong>$this->headingsubmissionlocation: </strong>$this->location</span><br />
                        <span class="font-small"> $this->locationinstructions</span>
                    </td>
                </tr>
                <tr>
                    <td colspan="48" class="border-thin">
                        <span class="font-small"><strong>$this->headingduedate:</strong> $this->duedate</span>
                    </td>
                </tr>
                <tr>
                    <td colspan="48" rowspan="3" class="border-thin addheight-md">
                        <strong><span class="font-small font-bold">$this->headingdescriptionofwork: </span></strong>
                    </td>
                </tr>
                <tr>
                    <td colspan="48"></td>
                </tr>
                <tr>
                    <td colspan="48"></td>
                </tr>
                <tr>
                    <td colspan="48" class="border-thin">
                        <strong><span class="font-small">$this->headingparticipant: </span></strong>$this->participantid
                    </td>
                </tr>
EOD;

        if ($this->isblindmarking === true) {
            $content .= <<<EOD
                <tr>
                    <td colspan="48"></td>
                </tr>
                <tr>
                    <td colspan="48" class="text-center font-blindmarking bottom-border-dashed">
                        <span class=""><strong>$this->headingblindmarking</strong></span><br />
                        <span class=""><strong>$this->headingblindmarkinginstruction</strong></span><br />
                    </td>
                </tr>
                <tr>
                    <td colspan="48"></td>
                </tr>
                <tr>
                    <td colspan="48"></td>
                </tr>
EOD;
        }

        $content .= <<<EOD
                <tr>
                    <td colspan="22" class="border-thin">
                        <span class="font-small"><strong>$this->headingassessmenttype:</strong> $this->assignmenttype</span>
                    </td>
                    <td colspan="26" class="border-thin">
                        <span class="font-small"><strong>$this->headinggroupingname:</strong> $this->groupingname</span>
                    </td>
                </tr>
                <tr>
                    <td colspan="24" class="border-thin">
                        <span class="font-small"><strong>$this->headingstudentname:</strong> $this->studentname</span>
                    </td>
                    <td colspan="24" class="border-thin">
                        <span class="font-small"><strong>$this->usernameformat:</strong> $this->username</span>
                    </td>
                </tr>
                <tr>
                    <td colspan="48" class="border-thin addheight-xl" rowspan="3">
                        <span class="font-small">
                            <strong>$this->headingsubmissionstatement: </strong>
                            $this->submissionstatement
                        </span>
                    </td>
                </tr>
                <tr>
                    <td colspan="48"></td>
                </tr>
                <tr>
                    <td colspan="48"></td>
                </tr>
                <tr>
                    <td colspan="24" rowspan="2" class="border-thick addheight">
                        <strong><span class="font-small font-bold">$this->headingsigned:</span></strong>
                    </td>
                    <td colspan="24" rowspan="2" class="border-thick addheight">
                        <strong><span class="font-small font-bold">$this->headingdate:</span></strong>
                    </td>
                </tr>
                <tr>
                    <td colspan="24"></td>
                    <td colspan="24"></td>
                </tr>
            </table>
EOD;
        return $content;
    }


    /**
     * Display an example pdf for preview purposes
     * @return void
     */
    public function display_example_pdf() {
        $doc = new pdf;
        $doc->setPrintHeader(false);
        $doc->setPrintFooter(false);
        $doc->AddPage();
        $doc->writeHTML($this->pdf_example_data(), true, false, false, false, 'L');
        $doc->write1DBarcode(7933528155335,
            'CODABAR',
            $x = '72',
            $y = '35',
            $w = '40',
            $h = '10',
            $xres = '',
            array('text' => true));
        $doc->Output('example.pdf', 'I');
        return;
    }


    /**
     * Example pdf content for preview purposes
     * @return string   The pdf content
     */
    private function pdf_example_data() {
        return <<<EOD
            <style>
                .font-small {
                    font-size:8px;
                    text-align:left;
                }
                .font-large {
                     font-size:14;
                     text-align:center;
                     display: block;
                     margin: 0 auto;
                }
                .text-center {
                    text-align: center;
                }
                table {
                    position: absolute;
                    left: 0;
                    top: 0;
                    width: 100%;
                    border: 1px solid black;
                }
                .border-thin {
                    border: 1px solid black;
                }
                .border-thick {
                    border: 3px solid black;
                }
                * {
                    position: relative;
                }
            </style>
            <table>
                <tr>
                    <td class="font-small text-center" colspan="48">
                        Example University<br />
                        Physical Assignment Submission <br />
                        PLEASE COMPLETE ALL FIELDS AND SIGN AND DATE YOUR SUBMISSION
                    </td>
                </tr>
                <tr>
                    <td colspan="15" class="border-thin">
                        <span class="font-small">Course</span><br />
                        <span class="font-large">EC2100</span>
                    </td>
                    <td colspan="12" class="border-thin">
                        <span style="font-size:9">Course Short Code</span><br />
                        <span style="font-size:13">EC</span>
                    </td>
                    <td colspan="3">

                    </td>
                    <td colspan="18" class="border-thin">
                        <span class="font-small">Student Name</span><br />
                        <span class="placeholder-box">Joe Bloggs</span>
                    </td>
                </tr>
                <tr>
                    <td colspan="15" class="border-thin">
                        <span class="font-small">Student ID</span><br />
                        <span class="font-large">331647556</span>
                    </td>
                    <td colspan="12" rowspan="2" class="border-thin">
                    </td>
                    <td colspan="3">

                    </td>
                    <td colspan="18" class="border-thin">
                        <span class="font-small"></span><br />
                        <span class="placeholder-box"></span>
                    </td>
                </tr>
                <tr>
                    <td colspan="15" class="border-thin">
                        <span class="font-small">Due Date:</span><br />
                        <span class="font-large">31/10/2018 16:00</span>
                    </td>
                    <td colspan="3">

                    </td>
                    <td colspan="21" class="border-thick">
                        <span class="font-small"></span><br />
                        <span class="placeholder-box"></span>
                    </td>
                </tr>
                <tr>
                    <td colspan="27" class="border-thin">
                        <span class="font-small">Category:</span>
                        <span class="font-large">Presentation</span>
                    </td>
                    <td colspan="3">

                    </td>
                    <td colspan="18" rowspan="2" class="border-thick">
                        <span class="font-small"></span><br />
                        <span class="placeholder-box"></span>
                    </td>
                </tr>
                <tr>
                    <td colspan="27" class="border-thin">
                        <span class="font-small">Type:</span>
                        <span class="font-large">Standard</span>
                    </td>
                </tr>
                <tr>
                    <td colspan="27" class="border-thin">
                        <span class="font-small">Assessment Type:</span>
                        <span class="font-large">Group</span>
                    </td>
                    <td colspan="3">

                    </td>
                    <td colspan="18" class="font-compact">
                        <span class="font-small">
                            <strong>Please Lorem ipsum dolor sit amet, consectetur adipisicing elit. Dolorem ipsum animi magni
                            </strong>
                        </span><br />
                    </td>
                </tr>
                <tr>
                    <td colspan="12" class="border-thin">
                        <span class="font-small">No. of Group Memebers:</span>
                    </td>
                    <td colspan="15" class="border-thick">
                        <span class="font-large"></span>
                    </td>
                </tr>
                <tr style="margin-top:3px">
                    <td colspan="48" class="border-thin">
                        <span class="heading">LATE WORK</span><br />
                        <span>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod
                        tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,
                        quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo
                        consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse
                        cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non
                        proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</span>
                    </td>
                </tr>
                <tr>
                    <td colspan="28" class="border-thin">
                        <span>Course Title:</span><br />
                        <span></span>
                    </td>
                    <td colspan="20" class="border-thin">
                        <span>Example Heading:</span><br />
                        <span></span>
                    </td>
                </tr>
                <tr>
                    <td colspan="28" class="border-thin">
                        <span>Module Title:</span><br />
                        <span>Lorem ipsum dolor sit amet, consectetur adipisicing elit.</span>
                    </td>
                    <td colspan="20" class="border-thin">
                        <span>Module Title:</span><br />
                        <span>Lorem ipsum dolor</span>
                    </td>
                </tr>
                <tr>
                    <td colspan="48" class="border-thick">
                        Assessment Title:
                        <br />
                    </td>
                </tr>
                <tr>
                    <td colspan="28" class="border-thin">
                        <span>Module Title:</span><br />
                        <span>Lorem ipsum dolor sit amet, consectetur adipisicing elit.</span>
                    </td>
                    <td colspan="20" class="border-thin">
                        <span>Module Title:</span><br />
                        <span>Lorem ipsum dolor</span>
                    </td>
                </tr>
                <tr>
                    <td colspan="48" class="border-thick">
                        <span class="font-bold">Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do</span><br />
                    </td>
                </tr>
                <tr>
                    <td colspan="48">
                        <p>Policy statement:</p>
                        <p>policystatement</p>
                    </td>
                </tr>
                <tr>
                    <td colspan="8" class="border-thin">
                        Mark Achieved<br />
                        <br />
                        <br />
                        <br />
                    </td>
                    <td colspan="40" rowspan="2" style="vertical-align:top" class="border-thin">
                        Feedback Summary (Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do)
                    </td>
                </tr>
                <tr>
                    <td colspan="8" class="border-thin">
                        <span class="font-bold">Mark Signature</span><br /><br /><br />
                    </td>
                </tr>
            </table>
EOD;
    }


    /**
     * Set the assignment submission statement
     * The assignment submission statement is displayed on the physical submission coversheet. "This assignment
     * is my own work, except where I hav..."
     * @return void
     */
    private function set_assignment_submission_statement() {
        global $DB;

        if ($record = $DB->get_record('config_plugins',
                                      array('plugin' => 'assign', 'name' => 'submissionstatement'),
                                      'value',
                                      IGNORE_MISSING)) {
            $this->submissionstatement = $record->value;
        } else {
            $this->submissionstatement = '';
        }
    }


    /**
     * Set the username format and value and student first and last name
     */
    private function set_user_details() {
        $usernamesetting      = get_config('assignsubmission_physical', 'usernamesettings');
        $username             = assignsubmission_physical_get_username([$this->userid, $usernamesetting]);
        $this->usernameformat = $username->name;
        $this->username       = $username->data;
        $this->studentname    = $this->user->firstname . ' ' . $this->user->lastname;
        $this->participantid  = assignsubmission_physical_get_participantid($this->userid, $this->assignmentid);
    }


    /**
     * Set the physical submission location details e.g building & instructions
     */
    public function set_location() {
        $location                   = assignsubmission_physical_get_location_by_assignment($this->assignmentid);
        $details                    = assignsubmission_physical_get_submission_location_details($location->value);
        $this->location             = $details['location'];
        $this->locationinstructions = $details['instructions'];
    }


    /**
     * Set the assignment title and description
     */
    private function set_assignment() {
        $this->assignmentid          = $this->assign->get_instance()->id;
        $this->assignmenttitle       = $this->assign->get_instance()->name;
        $this->assignmentdescription = $this->assign->get_instance()->intro;
    }


    /**
     * Set the course details - short code, title and summary
     */
    private function set_course() {
        $this->courseid        = $this->assign->get_course()->id;
        $this->courseshortcode = $this->assign->get_course()->shortname;
        $this->coursetitle     = $this->assign->get_course()->fullname;
        $this->coursesummary   = $this->assign->get_course()->summary;
        $this->cmid            = get_coursemodule_from_instance('assign', $this->assign->get_instance()->id)->id;
    }


    /**
     * Set assignment dates
     */
    private function set_dates() {
        $this->extensiondate = $this->get_submission_extension_date();
        $this->cutoffdate    = $this->get_formatted_date($this->assign->get_instance()->cutoffdate);
        if (!empty($this->extensiondate)) {
            $this->duedate = $this->extensiondate;
        } else {
            $this->duedate = $this->get_formatted_date($this->assign->get_instance()->duedate);
        }
    }


    /**
     * Set the details of the group submission
     * The details that come through for a group submission only state whether or not the submission is a group submission
     * and if so, then the optional grouping id. This function also sets the grouping name if groupings have been used.
     */
    private function set_group() {
        $this->isgroupsubmission = ($this->assign->get_instance()->teamsubmission === '1') ? true : false;
        $this->isgrouping        = ($this->assign->get_instance()->teamsubmissiongroupingid === '0') ? false : true;
        $this->assignmenttype    = get_string('individual', 'assignsubmission_physical');
        $this->groupingname      = '';
        $this->groupid           = 0;

        if ($this->isgroupsubmission) {
            $this->assignmenttype = get_string('group', 'assignsubmission_physical');
            $this->groupingname   = $this->usersubmissionstatus->submissiongroup->name;
            $this->groupid        = $this->usersubmissionstatus->submissiongroup->id;
        }
    }


    /**
     * Return either a formatted date as a string or an empty string to prevent an
     * invalid date being used
     * @return string   Date formatted as a string or empty string
     */
    private function get_submission_extension_date() {
        global $DB;

        $params = array('userid' => $this->userid, 'assignment'   => $this->assignmentid);
        $extension = $DB->get_record('assign_user_flags', $params, 'extensionduedate', IGNORE_MISSING);

        if ($extension && $extension->extensionduedate !== '0') {
            return date('d/m/Y H:i' , $extension->extensionduedate);
        }
        return '';
    }


    /**
     * Format assignment dates into dd/mm/yyyy hh:mm from a timestamp
     * Where the timestamp has not been set, an empty string is returned
     * @param  string $timestamp    Timestamp
     * @return string               Date format as day/month/year hour:minutes
     */
    private function get_formatted_date($timestamp) {
        return ($timestamp) ? date('d/m/Y H:i' , $timestamp) : '';
    }


    /**
     * Get the grouping name if the submission is a grouping
     * @param  string $id The grouping id from the $assign object
     * @return string     The grouping name
     */
    private function get_grouping($id) {
        global $DB;
        return $DB->get_record('groupings', array('id' => $id), 'name', IGNORE_MISSING);
    }


    /**
     * Set the users barcode
     */
    private function set_barcode() {
        $this->barcode = assignsubmission_physical_get_barcode($this->courseid,
                                                               $this->assignmentid,
                                                               $this->groupid,
                                                               $this->userid,
                                                               $this->submissionid,
                                                               $this->cmid);
    }


    /**
     * Set the submission id using the assign instance
     */
    private function set_submission() {
        if ($this->isgroupsubmission) {
            if (!$submission = $this->assign->get_group_submission($this->userid, $this->groupid, false)) {
                $submission = $this->assign->get_group_submission($this->userid, $this->groupid, true, 1);
            }
        }
        if (!$this->isgroupsubmission) {
            if (!$submission = $this->assign->get_user_submission($this->userid, false)) {
                $submission = $this->assign->get_user_submission($this->userid, true, 1);
            }
        }
        $this->submissionid = $submission->id;
    }


    /**
     * Set the coversheet headings
     */
    private function set_headings() {
        $this->headingcoventryuniversity      = get_string('coventryuniversity', 'assignsubmission_physical');
        $this->headingdeclarationform         = get_string('declarationform', 'assignsubmission_physical');
        $this->headingcompletesections        = get_string('completesections', 'assignsubmission_physical');
        $this->headingparticipant             = get_string('participantheading', 'assignsubmission_physical');
        $this->headingmodulecode              = get_string('modulecode', 'assignsubmission_physical');
        $this->headingmoduletitle             = get_string('moduletitle', 'assignsubmission_physical');
        $this->headingmoduletutors            = get_string('moduletutors', 'assignsubmission_physical');
        $this->headingtutor                   = get_string('tutor', 'assignsubmission_physical');
        $this->headingassignmenttitle         = get_string('assignmenttitle', 'assignsubmission_physical');
        $this->headingassignmentdescription   = get_string('assignmentdescription', 'assignsubmission_physical');
        $this->headingsubmissionlocation      = get_string('submissionlocation', 'assignsubmission_physical');
        $this->headingduedate                 = get_string('duedate', 'assignsubmission_physical');
        $this->headingdescriptionofwork       = get_string('descriptionofwork', 'assignsubmission_physical');
        $this->headingblindmarking            = get_string('blindmarkingheading', 'assignsubmission_physical');
        $this->headingblindmarkinginstruction = get_string('blindmarkinginstruction', 'assignsubmission_physical');
        $this->headingassessmenttype          = get_string('assessmenttype', 'assignsubmission_physical');
        if ($this->isgrouping) {
            $this->headinggroupingname        = get_string('groupingname', 'assignsubmission_physical');
        } else {
            $this->headinggroupingname        = get_string('groupname', 'assignsubmission_physical');
        }
        $this->headingsubmissionstatement     = get_string('submissionstatement', 'assignsubmission_physical');
        $this->headingstudentname             = get_string('studentname', 'assignsubmission_physical');
        $this->headingsigned                  = get_string('signed', 'assignsubmission_physical');
        $this->headingdate                    = get_string('date', 'assignsubmission_physical');
    }


    /**
     * Set whether or not the assignment is setup for blindmarking
     */
    protected function set_blindmarking() {
        $this->isblindmarking = false;
        if (isset($this->assign->get_instance()->blindmarking) &&
                $this->assign->get_instance()->blindmarking != 0) {
            $this->isblindmarking = true;
        }
    }


    /**
     * Get the course manager, teacher and editing teacher and set the tutor names as a concatentated string
     * @return void
     **/
    private function set_tutors() {
        global $DB;

        $sql = "SELECT u.firstname,
                       u.lastname
                  FROM {role_assignments} ra
                  JOIN {role} r ON ra.roleid = r.id
                  JOIN {user} u ON ra.userid = u.id
                  JOIN {context} c ON ra.contextid = c.id
                  JOIN {course} co ON c.instanceid = co.id
                 WHERE c.contextlevel = ?
                   AND c.instanceid = ?
                   AND r.id < ?
              ORDER BY r.sortorder ASC";
        $result = $DB->get_records_sql($sql, array(50, $this->courseid, 5));

        $this->tutors = '';
        $i = 1;
        foreach ($result as $key => $value) {
            $this->tutors .= $value->firstname . ' ' . $value->lastname;
            $this->tutors .= ($i === count($result)) ? '' : ', ';
            $i++;
        }
    }


    /**
     * Set the current user that's viewing their coversheet
     * @param objects $USER The current user object
     */
    private function set_user($USER) {
        $this->user = $USER;
        $this->userid = $this->user->id;
    }


    /**
     * Set the user submission status object that contains the assignment & submission details.
     * Additionally this object includes the submission group for the user.
     */
    private function set_user_submission_status() {
        $this->usersubmissionstatus = $this->assign->get_assign_submission_status_renderable($this->user, false);
    }
}
