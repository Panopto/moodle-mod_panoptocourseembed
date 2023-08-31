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
 * Panopto course embed library functions
 *
 * @package mod_panoptocourseembed
 * @copyright  Panopto 2021
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Get activity name
 *
 * @param object $panoptocourseembed
 * @return string
 */
function get_panoptocourseembed_name($panoptocourseembed) {
    // TODO: Set the name of the panoptocourseembed to the title of the session by storing the session in the intro?

    return get_string('modulename', 'panoptocourseembed');
}
/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $panoptocourseembed
 * @return bool|int
 */
function panoptocourseembed_add_instance($panoptocourseembed) {
    global $DB;

    $panoptocourseembed->name = get_panoptocourseembed_name($panoptocourseembed);
    $panoptocourseembed->timemodified = time();

    $id = $DB->insert_record("panoptocourseembed", $panoptocourseembed);

    $completiontimeexpected = !empty($panoptocourseembed->completionexpected) ? $panoptocourseembed->completionexpected : null;
    \core_completion\api::update_completion_date_event(
        $panoptocourseembed->coursemodule, 'panoptocourseembed', $id, $completiontimeexpected);

    return $id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $panoptocourseembed
 * @param object $mform
 * @return bool
 */
function panoptocourseembed_update_instance($panoptocourseembed, $mform) {
    global $DB;

    $panoptocourseembed->name = get_panoptocourseembed_name($panoptocourseembed);
    $panoptocourseembed->timemodified = time();
    $panoptocourseembed->id = $panoptocourseembed->instance;

    if ($mform) {
        $data = $mform->get_data();
        $panoptocourseembed->intro = $data->intro;
    }

    $completiontimeexpected = !empty($panoptocourseembed->completionexpected) ? $panoptocourseembed->completionexpected : null;
    \core_completion\api::update_completion_date_event(
        $panoptocourseembed->coursemodule, 'panoptocourseembed', $panoptocourseembed->id, $completiontimeexpected);

    return $DB->update_record("panoptocourseembed", $panoptocourseembed);
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id
 * @return bool
 */
function panoptocourseembed_delete_instance($id) {
    global $DB;

    if (! $panoptocourseembed = $DB->get_record("panoptocourseembed", array("id" => $id))) {
        return false;
    }

    $result = true;

    $cm = get_coursemodule_from_instance('panoptocourseembed', $id);
    \core_completion\api::update_completion_date_event($cm->id, 'panoptocourseembed', $panoptocourseembed->id, null);

    if (! $DB->delete_records("panoptocourseembed", array("id" => $panoptocourseembed->id))) {
        $result = false;
    }

    return $result;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 * See get_array_of_activities() in course/lib.php
 *
 * @param object $coursemodule
 * @return cached_cm_info|null
 */
function panoptocourseembed_get_coursemodule_info($coursemodule) {
    global $DB;

    if ($panoptocourseembed = $DB->get_record('panoptocourseembed',
        array('id' => $coursemodule->instance), 'id, name, intro, introformat')) {
        if (empty($panoptocourseembed->name)) {
            // Panoptocourseembed name missing, fix it.
            $panoptocourseembed->name = "panoptocourseembed{$panoptocourseembed->id}";
            $DB->set_field('panoptocourseembed', 'name', $panoptocourseembed->name, array('id' => $panoptocourseembed->id));
        }
        $info = new cached_cm_info();
        // No filtering hre because this info is cached and filtered later.
        $info->content = format_module_intro('panoptocourseembed', $panoptocourseembed, $coursemodule->id, false);
        $info->name  = $panoptocourseembed->name;
        return $info;
    } else {
        return null;
    }
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 *
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function panoptocourseembed_reset_userdata($data) {

    // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
    // See MDL-9367.

    return array();
}

/**
 * Features that this activity supports.
 *
 * @uses FEATURE_IDNUMBER
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature FEATURE_xx constant for requested feature
 * @return bool|null True if module supports feature, false if not, null if doesn't know
 */
function panoptocourseembed_supports($feature) {
    switch($feature) {
        case FEATURE_IDNUMBER:
            return true;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_INTRO:
            return false;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return false;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_NO_VIEW_LINK:
            return true;
        default:
            return null;
    }
}

/**
 * Check if the module has any update that affects the current user since a given time.
 *
 * @param  cm_info $cm course module data
 * @param  int $from the time to check updates from
 * @param  array $filter  if we need to check only specific updates
 * @return stdClass an object with the different type of areas indicating if they were updated or not
 * @since Moodle 3.2
 */
function panoptocourseembed_check_updates_since(cm_info $cm, $from, $filter = array()) {
    $updates = course_check_module_updates_since($cm, $from, array(), $filter);
    return $updates;
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @param int $userid User id to use for all capability checks, etc. Set to 0 for current user (default).
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_panoptocourseembed_core_calendar_provide_event_action(calendar_event $event,
                                                      \core_calendar\action_factory $factory,
                                                      int $userid = 0) {
    $cm = get_fast_modinfo($event->courseid, $userid)->instances['panoptocourseembed'][$event->instance];

    if (!$cm->uservisible) {
        // The module is not visible to the user for any reason.
        return null;
    }

    $completion = new \completion_info($cm->get_course());

    $completiondata = $completion->get_data($cm, false, $userid);

    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    return $factory->create_instance(
        get_string('view'),
        new \moodle_url('/mod/panoptocourseembed/view.php', ['id' => $cm->id]),
        1,
        true
    );
}
