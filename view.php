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
 * Panopto course embed module
 *
 * @package mod_panoptocourseembed
 * @copyright  Panopto 2021
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");

$id = optional_param('id', 0, PARAM_INT);    // Course Module ID, or
$panoptoid = optional_param('panoptoid', 0, PARAM_INT);     // Panopto course embed ID

if ($id) {
    $PAGE->set_url('/mod/panoptocourseembed/index.php', array('id' => $id));
    if (! $cm = get_coursemodule_from_id('panoptocourseembed', $id)) {
        print_error('invalidcoursemodule');
    }

    if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
        print_error('coursemisconf');
    }

    if (! $panoptocourseembed = $DB->get_record("panoptocourseembed", array("id"=>$cm->instance))) {
        print_error('invalidcoursemodule');
    }

} else {
    $PAGE->set_url('/mod/panoptocourseembed/index.php', array('panoptoid'=>$panoptoid));
    if (! $panoptocourseembed = $DB->get_record("panoptocourseembed", array("id" => $panoptoid))) {
        print_error('invalidcoursemodule');
    }
    if (! $course = $DB->get_record("course", array("id"=>$panoptocourseembed->course)) ){
        print_error('coursemisconf');
    }
    if (! $cm = get_coursemodule_from_instance("panoptocourseembed", $panoptocourseembed->id, $course->id)) {
        print_error('invalidcoursemodule');
    }
}

require_login($course, true, $cm);

redirect("$CFG->wwwroot/course/view.php?id=$course->id");


