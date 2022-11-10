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
 * This file launches LTI-tools enabled to be launched from a rich text editor
 *
 * @package    mod_panoptocourseembed
 * @copyright  2021 Panopto
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Initialize Panopto course embed view.
 */
function init_panoptocourseembed_view() {
    require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
    require_once(dirname(__FILE__) . '/lib/panoptocourseembed_lti_utility.php');
    require_once(dirname(dirname(dirname(__FILE__))) . '/mod/lti/lib.php');
    require_once(dirname(dirname(dirname(__FILE__))) . '/mod/lti/locallib.php');

    $courseid  = required_param('course', PARAM_INT);
    $resourcelinkid  = optional_param('resourcelinkid', '', PARAM_INT);

    // Try to detect if we are viewing content from an iframe nested in course, get the Id param if it exists.
    if (!empty($_SERVER['HTTP_REFERER']) && (strpos($_SERVER['HTTP_REFERER'], "/course/view.php") !== false)) {
        $components = parse_url($_SERVER['HTTP_REFERER']);
        parse_str($components['query'], $results);

        if (!empty($results['id'])) {
            $courseid = $results['id'];
        }
    }

    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
    $context = context_course::instance($courseid);
    $PAGE->set_context($context);
    require_login($course, true);

    // Not quite unique, but better than 99999 for old embeds.
    if (empty($resourcelinkid)) {
        $pageurl = new moodle_url("/mod/panoptocourseembed/view_content.php");
        $resourcelinkid = sha1(
                $pageurl->out(false) . '&' . $course->id
                    . '&' . $course->startdate
            );
    }

    $contenturl = urldecode(optional_param('contenturl', '', PARAM_URL));
    $customdata = urldecode(optional_param('custom', '', PARAM_RAW_TRIMMED));

    $course = get_course($courseid);

    $context = context_course::instance($courseid);

    require_login($course);
    require_capability('mod/lti:view', $context);

    // Get a matching LTI tool for the course.
    $toolid = \panoptocourseembed_lti_utility::get_course_tool_id($courseid);

    // If no lti tool exists then we can not continue.
    if (is_null($toolid)) {
        print_error('no_existing_lti_tools', 'panoptocourseembed');
        return;
    }

    $lti = new stdClass();

    // Give it some random id, this is not used in the code but will create a PHP notice if not provided.
    $lti->id = $resourcelinkid;
    $lti->typeid = $toolid;
    $lti->launchcontainer = LTI_LAUNCH_CONTAINER_WINDOW;
    $lti->toolurl = $contenturl;
    $lti->custom = new stdClass();
    $lti->instructorcustomparameters = [];
    $lti->debuglaunch = false;
    $lti->course = $courseid;

    if ($customdata) {
        $decoded = json_decode($customdata, true);

        foreach ($decoded as $key => $value) {
            $lti->custom->$key = $value;
        }
    }

    // LTI 1.3 login request.
    $config = lti_get_type_type_config($toolid);
    if ($config->lti_ltiversion === LTI_VERSION_1P3) {
        if (!isset($SESSION->lti_initiatelogin_status)) {
            echo lti_initiate_login($courseid,
                "mod_panoptocourseembed,'',{$toolid},{$resourcelinkid},{$contenturl},{$customdata}",
                $lti,
                $config
            );
            exit;
        } else {
            unset($SESSION->lti_initiatelogin_status);
        }
    }

    echo \panoptocourseembed_lti_utility::launch_tool($lti);
}

init_panoptocourseembed_view();
