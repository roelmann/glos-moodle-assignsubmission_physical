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
 * Scan submission barcodes view page
 *
 * @package   assignsubmission_physical
 * @copyright 2018 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @author    Dez Glidden <dez.glidden@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->dirroot . '/mod/assign/submission/physical/classes/summary.php');
require_once('lib.php');

$id   = optional_param('id', 0, PARAM_INT);
$data = new stdclass();

list($data->course, $data->cm) = get_course_and_cm_from_cmid($id, 'assign');
$data->context                 = context_module::instance($data->cm->id);
$data->assign                  = $DB->get_record('assign', ['id' => $data->cm->instance], '*', MUST_EXIST);

require_login($data->course, true, $data->cm);

$PAGE->set_url(new moodle_url('/mod/assign/submission/physical/summary.php', ['id' => $data->cm->id]));
$PAGE->set_pagelayout('incourse');
$PAGE->set_title($data->course->shortname . ': ' . $data->assign->name);
$PAGE->set_heading($data->course->fullname);
$PAGE->set_activity_record($data->assign);

$assign     = new assign($data->context, $data->cm, $data->course);
$submission = $assign->get_assign_submission_status_renderable($USER, true);

// Get the location details based on the assigment id.
$location                     = assignsubmission_physical_get_location_by_assignment($data->assign->id);
$details                      = assignsubmission_physical_get_submission_location_details($location->value);
$data->submisisonbuilding     = $details['location'];
$data->submisisoninstructions = $details['instructions'];

assignsubmission_physical_render_custom_student_submission_view($data, $assign, $submission);
