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
 * This file contains a renderer for the custom_summary_grading_form class
 *
 * @package   assignsubmission_physical
 * @copyright 2018 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @author    Dez Glidden <dez.glidden@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_physical;

use \mod_assign\output\grading_app;
use moodle_url;
use html_table;
use html_table_row;
use html_table_cell;
use html_writer;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->dirroot . '/mod/assign/renderer.php');

/**
 * Custom sumary forms for both staff and students
 * @copyright 2018 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_summary_grading_form extends \mod_assign_renderer {

    /**
     * Render the assignment grading summary table for a student
     *
     * The student summary table displays the additional button to print the coversheet
     * or cancel button to return to /mod/assign/view.php
     * @param  assign_grading_summary $summary The grading summary object
     * @return string                          The grading sumary table
     */
    public function render_assign_grading_staff_summary($summary) {
        // Create a table for the data.
        $o = '';
        $o .= $this->get_base_grading_table($summary);

        // Link to the barcode submission page for staff members.
        $o .= '<center class="assignsubmission-physical-vertical-breathe">';
        $urlparams = array('id' => $summary->coursemoduleid, 'action' => 'scanning');
        $url = new moodle_url('/local/barcode/submissions.php', $urlparams);
        $o .= '<a href="' . $url . '" class="btn btn-secondary">' .
                get_string('scansubmissions', 'assignsubmission_physical') .
              '</a> ';
        $o .= '</center>';

        // Link to the printable barcode submission page for staff members.
        $o .= '<center class="assignsubmission-physical-vertical-breathe-sm">';
        $urlparams = array('id' => $summary->coursemoduleid);
        $url = new moodle_url('/mod/assign/submission/physical/printsubmissions.php', $urlparams);
        $o .= '<a href="' . $url . '" class="btn btn-secondary" target="_blank">' .
                get_string('printsubmissionbarcodes', 'assignsubmission_physical') .
              '</a> ';
        $o .= '</center>';

        // Display the form submit & cancel buttons.
        $o .= '<center>';
        $o .= $this->output->container_start('submissionlinks');
        $urlparams = array('id' => $summary->coursemoduleid, 'action' => 'grading');
        $url = new moodle_url('/mod/assign/view.php', $urlparams);
        $o .= '<a href="' . $url . '" class="btn btn-secondary">' . get_string('viewgrading', 'mod_assign') . '</a> ';
        $urlparams = array('id' => $summary->coursemoduleid, 'action' => 'grader');
        $url = new moodle_url('/mod/assign/view.php', $urlparams);
        $o .= '<a href="' . $url . '" class="btn btn-primary">' . get_string('grade') . '</a>';
        $o .= $this->output->container_end();

        // Close the container and insert a spacer.
        $o .= $this->output->container_end();
        $o .= '</center>';

        return $o;
    }


    /**
     * Render the assignment grading summary table for a student
     *
     * The student summary table displays the additional button to print the coversheet
     * or cancel button to return to /mod/assign/view.php
     * @param  assign_grading_summary $summary The grading summary object
     * @return string                          The grading sumary table
     */
    public function render_assign_grading_student_summary(assign_grading_summary $summary) {
        $o = '';
        $o .= $this->get_base_grading_table($summary);

        // Link to the printing coversheet page or cancel back to mod/assign/view.php
        // if the assignment has yet to be submitted. ie. hide the call to action
        // buttons if they're not required.
        if ($summary->submissionssubmittedcount === 0) {
            $o .= '<center>';
            $urlparams = array('id' => $summary->coursemoduleid, 'action' => '');
            $url = new moodle_url('/mod/assign/submission/barcode/coversheet.php', $urlparams);
            $o .= '<a href="' . $url . '" class="btn btn-primary">' .
                    get_string('printcoversheet', 'assignsubmission_physical') .
                  '</a> ';

            $urlparams = array('id' => $summary->coursemoduleid);
            $url = new moodle_url('/mod/assign/view.php', $urlparams);
            $o .= '<a href="' . $url . '" class="btn btn-secondary">' . get_string('Cancel') . '</a>';
            $o .= '</center>';
        }
        return $o;
    }


    /**
     * Create the grading summary table that both the student & the staff member share
     *
     * @param  object $summary  The grading summary object
     * @return string           The grading summary table as a string
     */
    private function get_base_grading_table($summary) {
        global $CFG;
        // Create a table for the data.
        $o = '';
        $o .= $this->output->container_start('gradingsummary');
        $o .= $this->output->heading(get_string('gradingsummary', 'assign'), 3);
        $o .= $this->output->box_start('boxaligncenter gradingsummarytable');
        $t = new html_table();

        // Status.
        if ($summary->teamsubmission) {
            if ($summary->warnofungroupedusers) {
                $o .= $this->output->notification(get_string('ungroupedusers', 'assign'));
            }

            if ($summary->warnofallgroupmembers) {
                $editcourselink = "$CFG->wwwroot/course/modedit.php?update=$summary->coursemoduleid&return=1";
                $o .= $this->output->notification(get_string('warnallgroupmemberssubmit',
                                                             'assignsubmission_physical',
                                                             $editcourselink));
            }

            $this->add_table_row_tuple($t, get_string('numberofteams', 'assign'),
                                       $summary->participantcount);
        } else {
            $this->add_table_row_tuple($t, get_string('numberofparticipants', 'assign'),
                                       $summary->participantcount);
        }

        // Drafts count and dont show drafts count when using offline assignment.
        if ($summary->submissiondraftsenabled && $summary->submissionsenabled) {
            $this->add_table_row_tuple($t, get_string('numberofdraftsubmissions', 'assign'),
                                       $summary->submissiondraftscount);
        }

        // Submitted for grading.
        if ($summary->submissionsenabled) {
            $this->add_table_row_tuple($t, get_string('numberofsubmittedassignments', 'assign'),
                                       $summary->submissionssubmittedcount);
            if (!$summary->teamsubmission) {
                $this->add_table_row_tuple($t, get_string('numberofsubmissionsneedgrading', 'assign'),
                                           $summary->submissionsneedgradingcount);
            }
        }

        $time = time();
        if ($summary->duedate) {
            // Due date.
            $duedate = $summary->duedate;
            $this->add_table_row_tuple($t, get_string('duedate', 'assign'),
                                       userdate($duedate));

            // Time remaining.
            $due = '';
            if ($duedate - $time <= 0) {
                $due = get_string('assignmentisdue', 'assign');
            } else {
                $due = format_time($duedate - $time);
            }
            $this->add_table_row_tuple($t, get_string('timeremaining', 'assign'), $due);

            if ($duedate < $time) {
                $cutoffdate = $summary->cutoffdate;
                if ($cutoffdate) {
                    if ($cutoffdate > $time) {
                        $late = get_string('latesubmissionsaccepted', 'assign', userdate($summary->cutoffdate));
                    } else {
                        $late = get_string('nomoresubmissionsaccepted', 'assign');
                    }
                    $this->add_table_row_tuple($t, get_string('latesubmissions', 'assign'), $late);
                }
            }

        }

        // All done - write the table.
        $o .= html_writer::table($t);
        $o .= $this->output->box_end();

        return $o;
    }


    /**
     * Utility function to add a row of data to a table with 2 columns. Modified
     * the table param and does not return a value
     *
     * @param html_table $table The table to append the row of data to
     * @param string $first The first column text
     * @param string $second The second column text
     * @return void
     */
    private function add_table_row_tuple($table, $first, $second) {
        $row = new html_table_row();
        $cell1 = new html_table_cell($first);
        $cell2 = new html_table_cell($second);
        $row->cells = array($cell1, $cell2);
        $table->data[] = $row;
    }

}
