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
 * Unit tests for restoring the activity panoptocourseembed.
 *
 * @package mod_panoptocourseembed
 * @copyright  Panopto 2021
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_panoptocourseembed;

/**
 * Unit tests for restoring the activity panoptocourseembed.
 *
 * @package    mod_panoptocourseembed
 * @category   test
 * @copyright  Panopto 2021
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \restore_panoptocourseembed_activity_structure_step
 */
final class restore_test extends \advanced_testcase {
    /**
     * Set up.
     */
    protected function setUp(): void {
        global $CFG;

        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();

        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
    }

    /**
     * A crafted intro in the backup is sanitised on restore, not stored raw.
     */
    public function test_restore_sanitises_intro(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('panoptocourseembed', ['course' => $course->id]);

        // Write straight to the table to stand in for a tampered backup.
        $DB->set_field(
            'panoptocourseembed',
            'intro',
            '<iframe src="https://evil.example/steal" onload="alert(1)"></iframe>',
            ['id' => $module->id]
        );

        $restored = $this->backup_and_restore($course);

        $this->assertStringNotContainsString('evil.example', $restored->intro);
        $this->assertStringNotContainsString('onload=', $restored->intro);
    }

    /**
     * An embed this plugin produced still works after a restore.
     */
    public function test_restore_keeps_plugin_iframe(): void {
        global $CFG;

        $src = $CFG->wwwroot . '/mod/panoptocourseembed/view_content.php?course=1&contenturl=x';
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_module('panoptocourseembed', [
            'course' => $course->id,
            'intro' => '<iframe src="' . s($src) . '" width="640" height="360"></iframe>',
            'introformat' => FORMAT_HTML,
        ]);

        $restored = $this->backup_and_restore($course);

        $this->assertStringContainsString('<iframe src="' . s($src) . '"', $restored->intro);
        $this->assertStringContainsString('width="640"', $restored->intro);
    }

    /**
     * Back up a course, restore it into a new course and return the restored activity.
     *
     * @param \stdClass $course The course to back up.
     * @return \stdClass The restored panoptocourseembed record.
     */
    private function backup_and_restore($course) {
        global $DB, $USER;

        $bc = new \backup_controller(
            \backup::TYPE_1COURSE,
            $course->id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_IMPORT,
            $USER->id
        );
        $backupid = $bc->get_backupid();
        $bc->execute_plan();
        $bc->destroy();

        $newcourse = $this->getDataGenerator()->create_course();
        $rc = new \restore_controller(
            $backupid,
            $newcourse->id,
            \backup::INTERACTIVE_NO,
            \backup::MODE_IMPORT,
            $USER->id,
            \backup::TARGET_CURRENT_ADDING
        );
        $rc->execute_precheck();
        $rc->execute_plan();
        $rc->destroy();

        return $DB->get_record('panoptocourseembed', ['course' => $newcourse->id], '*', MUST_EXIST);
    }
}
