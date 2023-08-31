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
 * PHPUnit panoptocourseembed generator tests
 *
 * @package    mod_panoptocourseembed
 * @category   test
 * @copyright  Panopto 2021
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * PHPUnit panoptocourseembed generator testcase
 *
 * @package    mod_panoptocourseembed
 * @category   test
 * @copyright  Panopto 2021
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_panoptocourseembed_generator_testcase extends advanced_testcase {
    public function test_generator() {
        global $DB;

        $this->resetAfterTest(true);

        $this->assertEquals(0, $DB->count_records('panoptocourseembed'));

        $course = $this->getDataGenerator()->create_course();

        /** @var mod_panoptocourseembed_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_panoptocourseembed');
        $this->assertInstanceOf('mod_panoptocourseembed_generator', $generator);
        $this->assertEquals('panoptocourseembed', $generator->get_modulename());

        $generator->create_instance(array('course' => $course->id));
        $generator->create_instance(array('course' => $course->id));
        $panoptocourseembed = $generator->create_instance(array('course' => $course->id));
        $this->assertEquals(3, $DB->count_records('panoptocourseembed'));

        $cm = get_coursemodule_from_instance('panoptocourseembed', $panoptocourseembed->id);
        $this->assertEquals($panoptocourseembed->id, $cm->instance);
        $this->assertEquals('panoptocourseembed', $cm->modname);
        $this->assertEquals($course->id, $cm->course);

        $context = context_module::instance($cm->id);
        $this->assertEquals($panoptocourseembed->cmid, $context->instanceid);
    }
}
