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
 * Unit tests for the activity panoptocourseembed's lib.
 *
 * @package mod_panoptocourseembed
 * @copyright  Panopto 2021
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_panoptocourseembed;

/**
 * Unit tests for the activity panoptocourseembed's lib.
 *
 * @package    mod_panoptocourseembed
 * @category   test
 * @copyright  Panopto 2021
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class lib_test extends \advanced_testcase {

    /**
     * Set up.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Test event actions.
     * @covers ::panoptocourseembed_core_calendar_provide_event_action
     */
    public function test_panoptocourseembed_core_calendar_provide_event_action(): void {

        // Create the activity.
        $course = $this->getDataGenerator()->create_course();
        $panoptocourseembed = $this->getDataGenerator()->create_module('panoptocourseembed', ['course' => $course->id]);

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $panoptocourseembed->id,
            \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event.
        $actionevent = mod_panoptocourseembed_core_calendar_provide_event_action($event, $factory);

        // Confirm the event was decorated.
        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('view'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(1, $actionevent->get_item_count());
        $this->assertTrue($actionevent->is_actionable());
    }

    /**
     * Test event actions as non-user.
     * @covers ::mod_panoptocourseembed_core_calendar_provide_event_action_as_non_user
     */
    public function test_panoptocourseembed_core_calendar_provide_event_action_as_non_user(): void {
        global $CFG;

        // Create the activity.
        $course = $this->getDataGenerator()->create_course();
        $panoptocourseembed = $this->getDataGenerator()->create_module('panoptocourseembed', ['course' => $course->id]);

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $panoptocourseembed->id,
                \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED);

        // Now log out.
        $CFG->forcelogin = true; // We don't want to be logged in as guest, as guest users might still have some capabilities.
        $this->setUser();

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event.
        $actionevent = mod_panoptocourseembed_core_calendar_provide_event_action($event, $factory);

        // Confirm the event is not shown at all.
        $this->assertNull($actionevent);
    }

    /**
     * Test event actions in hidden section.
     * @covers ::mod_panoptocourseembed_core_calendar_provide_event_action_in_hidden_section
     */
    public function test_panoptocourseembed_core_calendar_provide_event_action_in_hidden_section(): void {
        // Create the activity.
        $course = $this->getDataGenerator()->create_course();
        $panoptocourseembed = $this->getDataGenerator()->create_module('panoptocourseembed', ['course' => $course->id]);

        // Create a student.
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $panoptocourseembed->id,
                \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED);

        // Set sections 0 as hidden.
        set_section_visible($course->id, 0, 0);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event for the student.
        $actionevent = mod_panoptocourseembed_core_calendar_provide_event_action($event, $factory, $student->id);

        // Confirm the event is not shown at all.
        $this->assertNull($actionevent);
    }

    /**
     * Test event actions for a user.
     * @covers ::mod_panoptocourseembed_core_calendar_provide_event_action_for_user
     */
    public function test_panoptocourseembed_core_calendar_provide_event_action_for_user(): void {
        global $CFG;

        // Create the activity.
        $course = $this->getDataGenerator()->create_course();
        $panoptocourseembed = $this->getDataGenerator()->create_module('panoptocourseembed', ['course' => $course->id]);

        // Enrol a student in the course.
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $panoptocourseembed->id,
            \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED);

        // Now, log out.
        $CFG->forcelogin = true; // We don't want to be logged in as guest, as guest users might still have some capabilities.
        $this->setUser();

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event for the student.
        $actionevent = mod_panoptocourseembed_core_calendar_provide_event_action($event, $factory, $student->id);

        // Confirm the event was decorated.
        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('view'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(1, $actionevent->get_item_count());
        $this->assertTrue($actionevent->is_actionable());
    }

    /**
     * Test event actions for a user in hidden section.
     * @covers ::mod_panoptocourseembed_core_calendar_provide_event_action_already_completed
     */
    public function test_panoptocourseembed_core_calendar_provide_event_action_already_completed(): void {
        global $CFG;

        $CFG->enablecompletion = 1;

        // Create the activity.
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $panoptocourseembed = $this->getDataGenerator()->create_module('panoptocourseembed',
            ['course' => $course->id],
            [
                'completion' => 2,
                'completionview' => 1,
                'completionexpected' => time() + DAYSECS,
            ]);

        // Get some additional data.
        $cm = get_coursemodule_from_instance('panoptocourseembed', $panoptocourseembed->id);

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $panoptocourseembed->id,
            \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED);

        // Mark the activity as completed.
        $completion = new \completion_info($course);
        $completion->set_module_viewed($cm);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event.
        $actionevent = mod_panoptocourseembed_core_calendar_provide_event_action($event, $factory);

        // Ensure result was null.
        $this->assertNull($actionevent);
    }

    /**
     * Test event actions for a user.
     * @covers ::mod_panoptocourseembed_core_calendar_provide_event_action_already_completed_for_user
     */
    public function test_panoptocourseembed_core_calendar_provide_event_action_already_completed_for_user(): void {
        global $CFG;

        $CFG->enablecompletion = 1;

        // Create the activity.
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $panoptocourseembed = $this->getDataGenerator()->create_module('panoptocourseembed',
            ['course' => $course->id],
            [
                'completion' => 2,
                'completionview' => 1,
                'completionexpected' => time() + DAYSECS,
            ]);

        // Enrol a student in the course.
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        // Get some additional data.
        $cm = get_coursemodule_from_instance('panoptocourseembed', $panoptocourseembed->id);

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $panoptocourseembed->id,
                \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED);

        // Mark the activity as completed for the student.
        $completion = new \completion_info($course);
        $completion->set_module_viewed($cm, $student->id);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event for the student.
        $actionevent = mod_panoptocourseembed_core_calendar_provide_event_action($event, $factory, $student->id);

        // Ensure result was null.
        $this->assertNull($actionevent);
    }

    /**
     * Creates an action event.
     *
     * @param int $courseid The course id.
     * @param int $instanceid The instance id.
     * @param string $eventtype The event type.
     * @return bool|calendar_event
     */
    private function create_action_event($courseid, $instanceid, $eventtype) {
        $event = new \stdClass();
        $event->name = 'Calendar event';
        $event->modulename  = 'panoptocourseembed';
        $event->courseid = $courseid;
        $event->instance = $instanceid;
        $event->type = CALENDAR_EVENT_TYPE_ACTION;
        $event->eventtype = $eventtype;
        $event->timestart = time();

        return \calendar_event::create($event);
    }
}
