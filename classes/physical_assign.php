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
 * This file contains extensions to the mod/assign class
 *
 * @package   assignsubmission_physical
 * @copyright 2018 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @author    Dez Glidden <dez.glidden@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_physical;

use \mod_assign\output\grading_app;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->dirroot . '/lib/grouplib.php');

/**
 * Custom physical assign class
 *
 * This adds functionality to the assign class
 *
 * @copyright 2018 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class physical_assign extends \assign {


    /**
     * Creates a custom assign grading summary with the addition of warning that all group members
     * required to submit is not supported.
     *
     * @return summary object
     */
    public function get_physical_assign_grading_summary() {
        $instance = $this->get_instance();
        $draft = ASSIGN_SUBMISSION_STATUS_DRAFT;
        $submitted = ASSIGN_SUBMISSION_STATUS_SUBMITTED;

        if ($instance->teamsubmission) {
            $summary = $this->get_group_assign_grading_summary();
        } else {
            $summary = $this->get_all_students_grading_summary();
        }

        $summary->submissiondraftsenabled = $instance->submissiondrafts;
        $summary->submissiondraftscount = $this->count_submissions_with_status($draft);
        $summary->submissionsenabled = $this->is_any_submission_plugin_enabled();
        $summary->submissionssubmittedcount = $this->count_submissions_with_status($submitted);
        $summary->submissionsneedgradingcount = $this->count_submissions_need_grading();
        $summary->duedate = $instance->duedate;
        $summary->cutoffdate = $instance->cutoffdate;
        $summary->coursemoduleid = $this->get_course_module()->id;
        $summary->teamsubmission = $instance->teamsubmission;
        $summary->cangrade = true;
        return $summary;
    }


    /**
     * Get the grading details specific for group assignments
     * @return object The group assignment grading summary
     */
    private function get_group_assign_grading_summary() {
        $instance = $this->get_instance();
        $activitygroup = groups_get_activity_group($this->get_course_module());
        $defaultteammembers = $this->get_submission_group_members(0, true);

        $summary = new stdClass();
        $summary->participantcount = $this->count_teams($activitygroup);
        $summary->warnofungroupedusers = (count($defaultteammembers) > 0 && $instance->preventsubmissionnotingroup);
        $summary->warnofallgroupmembers = ($instance->requireallteammemberssubmit === '1') ? true : false;
        return $summary;
    }


    /**
     * Get the grading details specific for the students grading summary
     * @return object The student specific grading summary
     */
    private function get_all_students_grading_summary() {
        $activitygroup = groups_get_activity_group($this->get_course_module());

        $summary = new stdClass();
        $summary->participantcount = $this->count_participants($activitygroup);
        $summary->warnofungroupedusers = false;
        $summary->warnofallgroupmembers = false;
        return $summary;
    }
}
