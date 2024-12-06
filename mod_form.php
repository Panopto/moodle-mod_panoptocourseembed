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
 * Add Panopto course embed instance form
 *
 * @package mod_panoptocourseembed
 * @copyright  Panopto 2021
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot . '/blocks/panopto/lib/lti/panoptoblock_lti_utility.php');

require_login();

/**
 * This class contains the forms to create and edit an instance of this module
 *
 * @package mod_panoptocourseembed
 * @copyright  Panopto 2021
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_panoptocourseembed_mod_form extends moodleform_mod {

    /**
     * Definition function for the form.
     */
    public function definition() {
        global $PAGE, $COURSE;

        define('PANOPTO_PANEL_WIDTH', 800);
        define('PANOPTO_PANEL_HEIGHT', 600);

        $PAGE->force_settings_menu();

        $toolurl = \panoptoblock_lti_utility::get_course_tool_url($COURSE->id, 'panopto_course_embed_tool');

        // If no lti tool exists then we can not continue.
        if (is_null($toolurl)) {
            throw new moodle_exception('no_existing_lti_tools', 'panoptocourseembed');
            return;
        }

        $mform = $this->_form;
        $mform->addElement('header', 'generalhdr', get_string('general'));
        $cimurlparams = ['courseid' => $COURSE->id];
        $cimurl = new moodle_url('/mod/panoptocourseembed/contentitem.php', $cimurlparams);
        $urlparams = [
            'course' => $COURSE->id,
            'contenturl' => $toolurl,
        ];
        $url = new moodle_url('/mod/panoptocourseembed/view_content.php', $urlparams);

        // Default intro should be a folderview.
        $defaultintro = '<p><iframe src="' . $url . '" style="width:100%; height:100%; min-width:800px; min-height:600px;">' .
                '</iframe><br /></p>';
        $mform->addElement('hidden', 'intro', $defaultintro);
        $mform->setType('intro', PARAM_RAW);

        $renderer = $PAGE->get_renderer('mod_panoptocourseembed');
        $mform->addElement('html', $renderer->get_content_selection_buttons($defaultintro));

        // Panopto course embed does not add "Show description" checkbox meaning that 'intro' is always shown on the course page.
        $mform->addElement('hidden', 'showdescription', 1);
        $mform->setType('showdescription', PARAM_INT);

        $this->standard_coursemodule_elements();

        // Buttons.
        $this->add_action_buttons(true, false, null);

        // AMD Modules.
        $urlparams = ['courseid' => $COURSE->id];

        $isresponsive = false;
        if (get_config('mod_panoptocourseembed', 'is_responsive')) {
            $isresponsive = true;
        }

        $params = [
            'selectvidbtnid' => 'id_select_video',
            'folderviewbtnid' => 'id_folder_view',
            'lticimlaunchurl' => $cimurl->out(false),
            'ltilaunchurl' => $url->out(false),
            'height' => PANOPTO_PANEL_HEIGHT,
            'width' => PANOPTO_PANEL_WIDTH,
            'courseid' => $COURSE->id,
            'resourcebase' => sha1(
                $PAGE->url->__toString() . '&' . $COURSE->id
                    . '&' . $COURSE->startdate
            ),
            'isresponsive' => $isresponsive,
        ];

        $PAGE->requires->js_call_amd('mod_panoptocourseembed/contentselectionpanel', 'initselectionpanel', [$params]);
        $PAGE->requires->string_for_js('replacevideo', 'panoptocourseembed');
        $PAGE->requires->string_for_js('selectvideo', 'panoptocourseembed');
    }
}
