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
 * LTI launch script for the Panopto Course Embed module.
 *
 * @package mod_panoptocourseembed
 * @copyright  Panopto 2021
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/mod/lti/lib.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/mod/lti/locallib.php');
require_once($CFG->dirroot . '/blocks/panopto/lib/lti/panoptoblock_lti_utility.php');

$courseid = required_param('courseid', PARAM_INT);

// Check access and capabilities.
$course = get_course($courseid);
require_login($course);

$toolid = \panoptoblock_lti_utility::get_course_tool_id($courseid, 'panopto_course_embed_tool');

// If no lti tool exists then we can not continue.
if (is_null($toolid)) {
    print_error('no_existing_lti_tools', 'panoptocourseembed');
    return;
}

// LTI 1.3 login request.
$config = lti_get_type_type_config($toolid);
if ($config->lti_ltiversion === LTI_VERSION_1P3) {
    if (!isset($SESSION->lti_initiatelogin_status)) {
        echo lti_initiate_login($courseid, "mod_panoptocourseembed", null, $config);
        exit;
    } else {
        unset($SESSION->lti_initiatelogin_status);
    }
}

// Set the return URL. We send the launch container along to help us avoid
// frames-within-frames when the user returns.
$returnurlparams = [
    'course' => $course->id,
    'id' => $toolid,
    'sesskey' => sesskey()
];
$returnurl = new \moodle_url('/mod/panoptocourseembed/contentitem_return.php', $returnurlparams);

// Prepare the request.
$request = lti_build_content_item_selection_request(
    $toolid, $course, $returnurl, '', '', [], [],
    false, false, false, false, false
);

// Get the launch HTML.
$content = lti_post_launch_html($request->params, $request->url, false);

echo $content;