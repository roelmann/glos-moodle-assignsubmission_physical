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
 * Display the printable submissions page
 *
 * @package   assignsubmission_physical
 * @copyright 2018 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @author    Dez Glidden <dez.glidden@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '../../../../../config.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once('./classes/event/printable_submissions.php');
require_once('./classes/printsubmission.php');
require_once('./classes/printgroupsubmission.php');
require_once('./lib.php');

$data = new stdclass();
$data->cmid = optional_param('id', 0, PARAM_INT);

list($data->course, $data->cm) = get_course_and_cm_from_cmid($data->cmid, 'assign');
$data->context                 = context_module::instance($data->cm->id);
$data->assign                  = new assign($data->context, $data->cm, $data->course);

require_login($data->course, true, $data->cm);
require_capability('mod/assignment:grade', $data->context);

$PAGE->set_url('/mod/assign/submission/physical/printsubmissions.php', array('id' => $data->cmid));
$PAGE->set_context($data->context);
$PAGE->set_title(get_string('printsubmissionpageheading', 'assignsubmission_physical') . $data->assign->get_instance()->name);
$PAGE->set_pagelayout('incourse');
$PAGE->set_activity_record($data->assign->get_instance());

$eventdata = array();
$eventdata['context']               = $data->context;
$eventdata['contextid']             = $data->context->id;
$eventdata['other']['cmid']         = $data->cmid;
$eventdata['other']['assignmentid'] = $data->assign->get_instance()->id;

$event = \assignsubmission_physical\event\printable_submissions::create($eventdata);
$event->trigger();

if (assignsubmission_physical_is_group_assignment($data->assign)) {
    $printsubmission = new \assignsubmission_physical\printgroupsubmission($data);
} else {
    $printsubmission = new \assignsubmission_physical\printsubmission($data);
}
$printsubmission->generate_pdf_content();
