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
 * Panopto Course Embed module upgrade
 *
 * @package mod_panoptocourseembed
 * @copyright  Panopto 2021
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This file keeps track of upgrades to
// the panoptocourseembed module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installation to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the methods of database_manager class
//
// Please do not forget to use upgrade_set_timeout()
// before any action that may take longer time to finish.

/**
 * This is ran when the plugin is upgraded
 *
 * @param string $oldversion the version previously installed
 * @return whether the upgrade was a success
 */
function xmldb_panoptocourseembed_upgrade($oldversion) {
    global $CFG, $DB;

    if ($oldversion < 2026092200) {
        require_once($CFG->dirroot . '/mod/panoptocourseembed/lib.php');

        // Intros saved before sanitising was added reach the course page unfiltered, so clean them now.
        $courseids = [];
        $records = $DB->get_recordset('panoptocourseembed', null, 'id', 'id, course, intro, introformat');

        foreach ($records as $record) {
            upgrade_set_timeout();

            $original = (string) $record->intro;

            // Cleaned like a form submission, but without losing an embed saved under an older
            // site address or alongside other markup. Nothing unsanitised is ever written back.
            $intro = panoptocourseembed_upgrade_intro($original, $record->introformat ?? FORMAT_HTML);

            if ($intro !== $original) {
                $DB->set_field('panoptocourseembed', 'intro', $intro, ['id' => $record->id]);
                $courseids[$record->course] = $record->course;
            }
        }

        $records->close();

        // The intro is cached in course modinfo, so drop the stale copies.
        foreach ($courseids as $courseid) {
            rebuild_course_cache($courseid, true);
        }

        upgrade_mod_savepoint(true, 2026092200, 'panoptocourseembed');
    }

    return true;
}
