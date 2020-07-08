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
 * Display a custom grading form page with the scan barcodes call to action
 *
 * @package   assignsubmission_physical
 * @copyright 2018 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @author    Dez Glidden <dez.glidden@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');
require_once('lib.php');
require_once('./classes/physical_assign.php');

$id    = optional_param('id', 0, PARAM_INT);
$data  = new stdclass();

list($data->course, $data->cm) = get_course_and_cm_from_cmid($id, 'assign');
$data->context                 = context_module::instance($data->cm->id);
$data->assign                  = $DB->get_record('assign', ['id' => $data->cm->instance], '*', MUST_EXIST);

require_login($data->course, true, $data->cm);
require_capability('assignsubmission/physical:scan', $data->context);
require_capability('moodle/grade:viewall', $data->context);

$assign  = new \assignsubmission_physical\physical_assign($data->context, $data->cm, $data->course);
$summary = $assign->get_physical_assign_grading_summary();

assignsubmission_physical_render_custom_grading_view($data, $summary, $assign);
