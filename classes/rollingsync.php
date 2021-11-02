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
 * add event handlers to Panopto course embed
 *
 * @package mod_panoptocourseembed
 * @copyright  Panopto 2021
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Handlers for each different event type.
 *
 * @package mod_panoptocourseembed
 * @copyright  Panopto 2021
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * Upon course creation: coursecreated is triggered
 */
class mod_panoptocourseembed_rollingsync {

    /**
     * Called when a course has been created.
     *
     * @param \core\event\course_created $event
     */
    public static function coursecreated(\core\event\course_created $event) {

        require_once(dirname(__FILE__) . '/../../../course/lib.php');
        require_once(dirname(__FILE__) . '/../../../lib/filelib.php');
        require_once(dirname(__FILE__) . '/../lib/panopto_lti_utility.php');

        if (get_config('mod_panoptocourseembed', 'auto_create_folderview_on_new_courses')) {

            $toolurl = \panopto_lti_utility::panoptocourseembed_get_course_tool_url($event->courseid);
            $urlparams = array(
                'course' => $event->courseid,
                'contenturl' => $toolurl
            );
            $url = new moodle_url('/mod/panoptocourseembed/view_content.php', $urlparams);

            $draftid_editor = file_get_submitted_draft_itemid('introeditor');
            file_prepare_draft_area($draftid_editor, null, null, null, null, array('subdirs'=>true));

            // default intro should be a folderview
            $moduleinfo = new stdClass();
            $moduleinfo->modulename = 'panoptocourseembed';
            $moduleinfo->course = $event->courseid;
            $moduleinfo->section = 0;
            $moduleinfo->intro = '<p><iframe src="' . $url . '" height=480 width=720></iframe><br /></p>';
            $moduleinfo->introeditor = array('text'=> $moduleinfo->intro, 'format'=>FORMAT_HTML, 'itemid'=>$draftid_editor);
            $moduleinfo->visible = true;
            create_module($moduleinfo);
        }
    }
}
