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
 * External mod_panoptocourseembed functions unit tests
 *
 * @package    mod_panoptocourseembed
 * @category   test
 * @copyright  Panopto 2021
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * External mod_panoptocourseembed functions unit tests
 *
 * @package    mod_panoptocourseembed
 * @category   test
 * @copyright  Panopto 2021
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_panoptocourseembed_external_testcase extends externallib_advanced_testcase {

    /**
     * Test test_mod_panoptocourseembed_get_panoptocourseembeds_by_courses
     */
    public function test_mod_panoptocourseembed_get_panoptocourseembeds_by_courses() {
        global $DB;

        $this->resetAfterTest(true);

        $course1 = self::getDataGenerator()->create_course();
        $course2 = self::getDataGenerator()->create_course();

        $student = self::getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($student->id, $course1->id, $studentrole->id);

        // First panoptocourseembed.
        $record = new stdClass();
        $record->course = $course1->id;
        $panoptocourseembed1 = self::getDataGenerator()->create_module('panoptocourseembed', $record);

        // Second panoptocourseembed.
        $record = new stdClass();
        $record->course = $course2->id;
        $panoptocourseembed2 = self::getDataGenerator()->create_module('panoptocourseembed', $record);

        // Execute real Moodle enrolment as we'll call unenrol() method on the instance later.
        $enrol = enrol_get_plugin('manual');
        $enrolinstances = enrol_get_instances($course2->id, true);
        foreach ($enrolinstances as $courseenrolinstance) {
            if ($courseenrolinstance->enrol == "manual") {
                $instance2 = $courseenrolinstance;
                break;
            }
        }
        $enrol->enrol_user($instance2, $student->id, $studentrole->id);

        self::setUser($student);

        $returndescription = mod_panoptocourseembed_external::get_panoptocourseembeds_by_courses_returns();

        // Create what we expect to be returned when querying the two courses.
        $expectedfields = array('id', 'coursemodule', 'course', 'name', 'intro', 'introformat', 'introfiles', 'timemodified',
                                'section', 'visible', 'groupmode', 'groupingid');

        // Add expected coursemodule and data.
        $panoptocourseembed1->coursemodule = $panoptocourseembed1->cmid;
        $panoptocourseembed1->introformat = 1;
        $panoptocourseembed1->section = 0;
        $panoptocourseembed1->visible = true;
        $panoptocourseembed1->groupmode = 0;
        $panoptocourseembed1->groupingid = 0;
        $panoptocourseembed1->introfiles = [];

        $panoptocourseembed2->coursemodule = $panoptocourseembed2->cmid;
        $panoptocourseembed2->introformat = 1;
        $panoptocourseembed2->section = 0;
        $panoptocourseembed2->visible = true;
        $panoptocourseembed2->groupmode = 0;
        $panoptocourseembed2->groupingid = 0;
        $panoptocourseembed2->introfiles = [];

        foreach ($expectedfields as $field) {
            $expected1[$field] = $panoptocourseembed1->{$field};
            $expected2[$field] = $panoptocourseembed2->{$field};
        }

        $expectedpanoptocourseembeds = array($expected2, $expected1);

        // Call the external function passing course ids.
        $result = mod_panoptocourseembed_external::get_panoptocourseembeds_by_courses(array($course2->id, $course1->id));
        $result = external_api::clean_returnvalue($returndescription, $result);

        $this->assertEquals($expectedpanoptocourseembeds, $result['panoptocourseembeds']);
        $this->assertCount(0, $result['warnings']);

        // Call the external function without passing course id.
        $result = mod_panoptocourseembed_external::get_panoptocourseembeds_by_courses();
        $result = external_api::clean_returnvalue($returndescription, $result);
        $this->assertEquals($expectedpanoptocourseembeds, $result['panoptocourseembeds']);
        $this->assertCount(0, $result['warnings']);

        // Unenrol user from second course.
        $enrol->unenrol_user($instance2, $student->id);
        array_shift($expectedpanoptocourseembeds);

        // Call the external function without passing course id.
        $result = mod_panoptocourseembed_external::get_panoptocourseembeds_by_courses();
        $result = external_api::clean_returnvalue($returndescription, $result);
        $this->assertEquals($expectedpanoptocourseembeds, $result['panoptocourseembeds']);

        // Call for the second course we unenrolled the user from, expected warning.
        $result = mod_panoptocourseembed_external::get_panoptocourseembeds_by_courses(array($course2->id));
        $this->assertCount(1, $result['warnings']);
        $this->assertEquals('1', $result['warnings'][0]['warningcode']);
        $this->assertEquals($course2->id, $result['warnings'][0]['itemid']);
    }
}
