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
 * Defines restore_panoptocourseembed_activity_task class
 *
 * @package mod_panoptocourseembed
 * @subpackage backup-moodle2
 * @category backup
 * @copyright  Panopto 2021
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Because it exists (must).
require_once($CFG->dirroot . '/mod/panoptocourseembed/backup/moodle2/restore_panoptocourseembed_stepslib.php');

/**
 * Task that provides all the settings and steps to perform complete restore.
 *
 */
class restore_panoptocourseembed_activity_task extends restore_activity_task {
    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Panoptocourseembed only has one structure step.
        $this->add_step(
            new restore_panoptocourseembed_activity_structure_step(
                'panoptocourseembed_structure',
                'panoptocourseembed.xml'
            )
        );
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    public static function define_decode_contents() {
        $contents = [];

        $contents[] = new restore_decode_content('panoptocourseembed', ['intro'], 'panoptocourseembed');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    public static function define_decode_rules() {
        return [];
    }

    /**
     * Define the restore log rules that will be applied
     * by the restore_logs_processor when restoring
     * panoptocourseembed logs. It must return one array
     * of restore_log_rule objects
     */
    public static function define_restore_log_rules() {
        $rules = [];

        $rules[] = new restore_log_rule('panoptocourseembed', 'add', 'view.php?id={course_module}', '{panoptocourseembed}');
        $rules[] = new restore_log_rule('panoptocourseembed', 'update', 'view.php?id={course_module}', '{panoptocourseembed}');
        $rules[] = new restore_log_rule('panoptocourseembed', 'view', 'view.php?id={course_module}', '{panoptocourseembed}');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the restore_logs_processor when restoring
     * course logs. It must return one array
     * of restore_log_rule objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    public static function define_restore_log_rules_for_course() {
        $rules = [];

        $rules[] = new restore_log_rule('panoptocourseembed', 'view all', 'index.php?id={course}', null);

        return $rules;
    }
}
