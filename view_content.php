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
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/blocks/panopto/lib/lti/panoptoblock_lti_utility.php');
require_once($CFG->dirroot . '/blocks/panopto/lib/block_panopto_lib.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/mod/lti/lib.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/mod/lti/locallib.php');

$courseid  = required_param('course', PARAM_INT);
$resourcelinkid  = optional_param('resourcelinkid', '', PARAM_INT);

// Try to detect if we are viewing content from an iframe nested in course, get the Id param if
// it exists. Anchored on wwwroot: an unanchored search would let a referer of
// https://evil.example/course/view.php?id=99 choose the course, and with it the tool lookup and
// the context the capability below is checked against.
if (
    !empty($_SERVER['HTTP_REFERER']) && is_scalar($_SERVER['HTTP_REFERER'])
        && strpos($_SERVER['HTTP_REFERER'], $CFG->wwwroot . '/course/view.php') === 0
) {
    $components = parse_url($_SERVER['HTTP_REFERER']);
    parse_str($components['query'] ?? '', $results);

    // The is_scalar check guards against ?id[]=1, which would make clean_param throw.
    if (!empty($results['id']) && is_scalar($results['id'])) {
        // Clean and validate the course ID parameter from referer URL for security.
        $referercourseid = clean_param($results['id'], PARAM_INT);

        if ($referercourseid > 0) {
            $courseid = $referercourseid;
        }
    }
}

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);
$PAGE->set_context($context);

// Authenticate before anything else: the LTI 1.3 branch further down exits, so checks placed
// after it never run on the first pass of a 1.3 launch.
require_login($course, true);
require_capability('mod/lti:view', $context);

// Not quite unique, but better than 99999 for old embeds.
if (empty($resourcelinkid)) {
    $pageurl = new moodle_url("/mod/panoptocourseembed/view_content.php");
    $resourcelinkid = sha1(
        $pageurl->out(false) . '&' . $course->id .
        '&' . $course->startdate
    );
}

$contenturl = urldecode(optional_param('contenturl', '', PARAM_URL));
$customdata = urldecode(optional_param('custom', '', PARAM_RAW_TRIMMED));

// The content URL becomes the endpoint a signed launch is posted to, so an unrecognised
// host would leak the launch and its identity claims off-site. An empty value is fine:
// the launch then targets the tool's own configured endpoint.
if (!empty($contenturl) && !panopto_is_allowed_panopto_host($contenturl)) {
    throw new moodle_exception('invalid_content_host', 'panoptocourseembed');
}

// Get a matching LTI tool for the course.
$toolid = \panoptoblock_lti_utility::get_course_tool_id($courseid, 'panopto_course_embed_tool');

// If no lti tool exists then we can not continue.
if (is_null($toolid)) {
    throw new moodle_exception('no_existing_lti_tools', 'panoptocourseembed');
    return;
}

$lti = new stdClass();

// Give it some random id, this is not used in the code but will create a PHP notice if not provided.
$lti->id = $resourcelinkid;
$lti->typeid = $toolid;
$lti->launchcontainer = LTI_LAUNCH_CONTAINER_WINDOW;
$lti->toolurl = $contenturl;
$lti->custom = \panoptoblock_lti_utility::filter_launch_custom_parameters(
    $customdata,
    'mod_panoptocourseembed'
);
$lti->instructorcustomparameters = [];
$lti->debuglaunch = false;
$lti->course = $courseid;

// LTI 1.3 login request.
$config = lti_get_type_type_config($toolid);
if ($config->lti_ltiversion === LTI_VERSION_1P3) {
    if (!isset($SESSION->lti_initiatelogin_status)) {
        echo lti_initiate_login(
            $courseid,
            \panoptoblock_lti_utility::build_playback_launch_hint(
                'mod_panoptocourseembed',
                '',
                $toolid,
                $resourcelinkid,
                $contenturl,
                $lti->custom
            ),
            $lti,
            $config
        );
        exit;
    } else {
        unset($SESSION->lti_initiatelogin_status);
    }
}

// Set the platform-owned flag the allowlist omits.
$lti->custom->grading_not_supported = true;

echo \panoptoblock_lti_utility::launch_tool($lti);
