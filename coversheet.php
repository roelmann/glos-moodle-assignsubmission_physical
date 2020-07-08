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
 * Display the coversheet page
 *
 * @package   assignsubmission_physical
 * @copyright 2018 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @author    Dez Glidden <dez.glidden@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once('./classes/coversheet.php');
require_once('./classes/physical_assign.php');
require_once('./classes/event/coversheet_viewed.php');

// Check for the module id and the format url parameter.
$id           = optional_param('id', 0, PARAM_INT);
$preview      = optional_param('preview', '', PARAM_ALPHA);
$submissionid = optional_param('id', 0, PARAM_INT);

if (! $format = optional_param('format', '', PARAM_ALPHA)) {
    $format = 'I';
}

list($course, $cm) = get_course_and_cm_from_cmid($id, 'assign');
$context           = context_module::instance($cm->id);

require_login($course, true, $cm);

$PAGE->set_url('/mod/assign/submission/coversheet.php', array('id' => $id));
$PAGE->set_context($context);
$PAGE->set_title(get_string('coversheetpageheading', 'assignsubmission_physical'));

// Retrieve the assign object based on context and course.
$assign = new \assignsubmission_physical\physical_assign($context, $cm, $course);

// Set the submission for the first time or retrieve the already existing submission.
if (! $submission = $assign->get_user_submission($USER->id, false)) {
    $submission = $assign->get_user_submission($USER->id, true);
}
$coversheet = new \assignsubmission_physical\coversheet($assign, $USER, $submission, $format);

// Log the event that the user viewed the submmission details.
$params = array(
    'context' => $context,
    'courseid' => $course->id
);
$params['relateduserid'] = $submission->userid;

$params['other'] = array(
    'submissionid' => $submission->id,
    'submissionattempt' => $submission->attemptnumber,
    'submissionstatus' => $submission->status
);
$params['objectid'] = $assign->get_instance()->id;

if ($preview === 'yes') {
    $coversheet->display_example_pdf();
} else if ($format === 'I') {
    $event = \assignsubmission_physical\event\coversheet_viewed::create($params);
    $event->set_assign($assign);
    $event->trigger();
    $coversheet->display_1d_pdf();
} else {
    $event = \assignsubmission_physical\event\coversheet_downloaded::create($params);
    $event->set_assign($assign);
    $event->trigger();
    // Generic fallback for IE to force the pdf download.
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="assignment.pdf"');
    $coversheet->display_1d_pdf();
}
