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
 * Defines backup_panoptocourseembed_stepslib class.
 *
 * @package mod_panoptocourseembed
 * @category backup
 * @copyright  Panopto 2021
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the backup steps that will be used by the backup_panoptocourseembed_stepslib
 */

/**
 * Define the complete panoptocourseembed structure for backup, with file and id annotations
 */
class backup_panoptocourseembed_activity_structure_step extends backup_activity_structure_step {

    /**
     * Define standard activity structure.
     */
    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $panoptocourseembed = new backup_nested_element('panoptocourseembed', ['id'],
            ['name', 'intro', 'introformat', 'timemodified']);

        // Build the tree.
        // (love this).

        // Define sources.
        $panoptocourseembed->set_source_table('panoptocourseembed', ['id' => backup::VAR_ACTIVITYID]);

        // Define id annotations.
        // (none).

        // Define file annotations.
        $panoptocourseembed->annotate_files('mod_panoptocourseembed', 'intro', null); // This file area hasn't itemid.

        // Return the root element (panoptocourseembed), wrapped into standard activity structure.
        return $this->prepare_activity_structure($panoptocourseembed);
    }
}
