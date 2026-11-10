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
        $event = $this->create_action_event(
            $course->id,
            $panoptocourseembed->id,
            \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED
        );

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
        $event = $this->create_action_event(
            $course->id,
            $panoptocourseembed->id,
            \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED
        );

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
        $event = $this->create_action_event(
            $course->id,
            $panoptocourseembed->id,
            \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED
        );

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
        $event = $this->create_action_event(
            $course->id,
            $panoptocourseembed->id,
            \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED
        );

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
        $panoptocourseembed = $this->getDataGenerator()->create_module(
            'panoptocourseembed',
            ['course' => $course->id],
            [
                'completion' => 2,
                'completionview' => 1,
                'completionexpected' => time() + DAYSECS,
            ]
        );

        // Get some additional data.
        $cm = get_coursemodule_from_instance('panoptocourseembed', $panoptocourseembed->id);

        // Create a calendar event.
        $event = $this->create_action_event(
            $course->id,
            $panoptocourseembed->id,
            \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED
        );

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
        $panoptocourseembed = $this->getDataGenerator()->create_module(
            'panoptocourseembed',
            ['course' => $course->id],
            [
                'completion' => 2,
                'completionview' => 1,
                'completionexpected' => time() + DAYSECS,
            ]
        );

        // Enrol a student in the course.
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        // Get some additional data.
        $cm = get_coursemodule_from_instance('panoptocourseembed', $panoptocourseembed->id);

        // Create a calendar event.
        $event = $this->create_action_event(
            $course->id,
            $panoptocourseembed->id,
            \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED
        );

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

    /**
     * Intros that this plugin produced are rebuilt from a fixed iframe template.
     *
     * @covers ::panoptocourseembed_sanitise_intro
     */
    public function test_sanitise_intro_rebuilds_plugin_iframe(): void {
        global $CFG;

        $src = $CFG->wwwroot . '/mod/panoptocourseembed/view_content.php?course=3&contenturl=' .
            urlencode('https://demo.panopto.com/Panopto/Pages/Viewer.aspx?id=1');
        $intro = '<p><iframe src="' . $src . '" width="720" height="480" onload="alert(1)"></iframe><br /></p>';

        $stored = panoptocourseembed_sanitise_intro($intro, FORMAT_HTML);

        $this->assertStringContainsString('src="' . s($src) . '"', $stored);
        $this->assertStringContainsString('width="720"', $stored);
        $this->assertStringContainsString('height="480"', $stored);
        $this->assertStringNotContainsString('onload=', $stored);
        $this->assertSame(1, preg_match_all('/<iframe\b/i', $stored));
    }

    /**
     * Responsive embeds keep their aspect ratio and drop any extra attributes.
     *
     * @covers ::panoptocourseembed_sanitise_intro
     */
    public function test_sanitise_intro_preserves_aspect_ratio(): void {
        global $CFG;

        $src = $CFG->wwwroot . '/mod/panoptocourseembed/view_content.php?course=3';
        $intro = '<iframe src="' . $src . '" style="width:100%; height:auto; aspect-ratio: 16 / 9;"' .
            ' onclick="alert(1)"></iframe>';

        $stored = panoptocourseembed_sanitise_intro($intro, FORMAT_HTML);

        $this->assertStringContainsString('aspect-ratio: 16 / 9', $stored);
        $this->assertStringNotContainsString('onclick=', $stored);
    }

    /**
     * The default folder-view iframe has no dimensions and is regenerated without extras.
     *
     * @covers ::panoptocourseembed_sanitise_intro
     */
    public function test_sanitise_intro_default_folder_iframe(): void {
        global $CFG;

        $src = $CFG->wwwroot . '/mod/panoptocourseembed/view_content.php';
        $stored = panoptocourseembed_sanitise_intro('<iframe src="' . $src . '"></iframe>', FORMAT_HTML);

        $this->assertStringContainsString('src="' . s($src) . '"', $stored);
        $this->assertStringContainsString('width:100%; max-width:100%; height:600px;', $stored);
        $this->assertStringNotContainsString('min-width', $stored);
    }

    /**
     * An empty intro is stored as empty so a brand-new activity can be saved before selection.
     *
     * @covers ::panoptocourseembed_sanitise_intro
     */
    public function test_sanitise_intro_allows_empty(): void {
        $this->assertSame('', panoptocourseembed_sanitise_intro('   ', FORMAT_HTML));
        $this->assertSame('', panoptocourseembed_sanitise_intro('', FORMAT_HTML));
    }

    /**
     * An iframe that does not point at this site's view_content.php is cleaned, not stored raw.
     *
     * @covers ::panoptocourseembed_sanitise_intro
     */
    public function test_sanitise_intro_rejects_external_iframe(): void {
        $stored = panoptocourseembed_sanitise_intro(
            '<iframe src="https://evil.example/steal"></iframe>',
            FORMAT_HTML
        );

        $this->assertStringNotContainsString('evil.example', $stored);
        $this->assertStringNotContainsString('<iframe', $stored);
    }

    /**
     * A host that only has wwwroot as a prefix is not this site's view_content.php.
     *
     * @covers ::panoptocourseembed_sanitise_intro
     */
    public function test_sanitise_intro_rejects_wwwroot_prefix_bypass(): void {
        global $CFG;

        $stored = panoptocourseembed_sanitise_intro(
            '<iframe src="' . $CFG->wwwroot . '.evil.example/mod/panoptocourseembed/view_content.php"></iframe>',
            FORMAT_HTML
        );

        $this->assertStringNotContainsString('evil.example', $stored);
        $this->assertStringNotContainsString('<iframe', $stored);
    }

    /**
     * A path that continues after view_content.php without a query string is not a plugin embed.
     *
     * @covers ::panoptocourseembed_sanitise_intro
     */
    public function test_sanitise_intro_rejects_path_continuation(): void {
        global $CFG;

        $stored = panoptocourseembed_sanitise_intro(
            '<iframe src="' . $CFG->wwwroot . '/mod/panoptocourseembed/view_content.php/../evil"></iframe>',
            FORMAT_HTML
        );

        $this->assertStringNotContainsString('<iframe', $stored);
    }

    /**
     * Two iframes are not markup this plugin produced, so they go through clean_text().
     *
     * @covers ::panoptocourseembed_sanitise_intro
     */
    public function test_sanitise_intro_rejects_multiple_iframes(): void {
        global $CFG;

        $src = $CFG->wwwroot . '/mod/panoptocourseembed/view_content.php';
        $stored = panoptocourseembed_sanitise_intro(
            '<iframe src="' . $src . '"></iframe><iframe src="https://evil.example/"></iframe>',
            FORMAT_HTML
        );

        $this->assertStringNotContainsString('evil.example', $stored);
    }

    /**
     * Plain text from the test generator is cleaned rather than rejected.
     *
     * @covers ::panoptocourseembed_sanitise_intro
     */
    public function test_sanitise_intro_plain_text(): void {
        $stored = panoptocourseembed_sanitise_intro('A session title', FORMAT_HTML);

        $this->assertStringContainsString('A session title', $stored);
    }

    /**
     * add_instance stores the sanitised intro, not the posted markup.
     *
     * @covers ::panoptocourseembed_add_instance
     * @covers ::panoptocourseembed_sanitise_intro
     */
    public function test_add_instance_sanitises_intro(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('panoptocourseembed', [
            'course' => $course->id,
            'intro' => '<iframe src="https://evil.example/steal" onload="alert(1)"></iframe>',
            'introformat' => FORMAT_HTML,
        ]);

        $stored = $DB->get_record('panoptocourseembed', ['id' => $module->id], '*', MUST_EXIST);

        $this->assertStringNotContainsString('evil.example', $stored->intro);
        $this->assertStringNotContainsString('onload=', $stored->intro);
    }

    /**
     * update_instance stores the sanitised intro, not the posted markup.
     *
     * @covers ::panoptocourseembed_update_instance
     * @covers ::panoptocourseembed_sanitise_intro
     */
    public function test_update_instance_sanitises_intro(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('panoptocourseembed', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('panoptocourseembed', $module->id, $course->id, false, MUST_EXIST);

        $update = $DB->get_record('panoptocourseembed', ['id' => $module->id], '*', MUST_EXIST);
        $update->instance = $module->id;
        $update->coursemodule = $cm->id;
        $update->course = $course->id;
        $update->intro = '<iframe src="https://evil.example/steal" onload="alert(1)"></iframe>';
        $update->introformat = FORMAT_HTML;

        panoptocourseembed_update_instance($update, null);

        $stored = $DB->get_record('panoptocourseembed', ['id' => $module->id], '*', MUST_EXIST);

        $this->assertStringNotContainsString('evil.example', $stored->intro);
        $this->assertStringNotContainsString('onload=', $stored->intro);
    }

    /**
     * The LTI return page must reject a missing sesskey and escape JSON used in a script tag.
     *
     * @coversNothing
     */
    public function test_contentitem_return_requires_sesskey_and_escapes_json(): void {
        global $CFG;

        $src = file_get_contents($CFG->dirroot . '/mod/panoptocourseembed/contentitem_return.php');

        $this->assertStringContainsString('require_sesskey()', $src);
        $this->assertStringContainsString(
            'JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT',
            $src
        );
    }

    /**
     * A stored embed is pointed at the course passed in, whatever course it was authored in.
     *
     * @covers ::panoptocourseembed_set_intro_course
     */
    public function test_set_intro_course_replaces_stored_course(): void {
        global $CFG;

        $src = $CFG->wwwroot . '/mod/panoptocourseembed/view_content.php?course=3&contenturl=' .
            urlencode('https://demo.panopto.com/Panopto/Pages/Viewer.aspx?id=1');
        $intro = panoptocourseembed_sanitise_intro('<iframe src="' . $src . '" width="720" height="480"></iframe>');

        $rendered = panoptocourseembed_set_intro_course($intro, 17);

        $expected = $CFG->wwwroot . '/mod/panoptocourseembed/view_content.php?course=17&contenturl=' .
            urlencode('https://demo.panopto.com/Panopto/Pages/Viewer.aspx?id=1');
        $this->assertStringContainsString('src="' . s($expected) . '"', $rendered);
    }

    /**
     * An embed without a course parameter gets one.
     *
     * @covers ::panoptocourseembed_set_intro_course
     */
    public function test_set_intro_course_adds_missing_course(): void {
        global $CFG;

        $viewurl = $CFG->wwwroot . '/mod/panoptocourseembed/view_content.php';

        $this->assertStringContainsString(
            'src="' . $viewurl . '?course=5"',
            panoptocourseembed_set_intro_course('<iframe src="' . $viewurl . '"></iframe>', 5)
        );
        $this->assertStringContainsString(
            'src="' . $viewurl . '?contenturl=x&amp;course=5"',
            panoptocourseembed_set_intro_course('<iframe src="' . $viewurl . '?contenturl=x"></iframe>', 5)
        );
    }

    /**
     * Only this plugin's view_content.php sources are touched.
     *
     * @covers ::panoptocourseembed_set_intro_course
     */
    public function test_set_intro_course_ignores_other_sources(): void {
        $intro = '<iframe src="https://example.com/view.php?course=3"></iframe>';

        $this->assertSame($intro, panoptocourseembed_set_intro_course($intro, 17));
    }

    /**
     * An activity copied into another course launches against the course it now lives in.
     *
     * @covers ::panoptocourseembed_get_coursemodule_info
     */
    public function test_course_page_embed_uses_activity_course(): void {
        global $CFG;

        $original = $this->getDataGenerator()->create_course();
        $copy = $this->getDataGenerator()->create_course();
        $src = $CFG->wwwroot . '/mod/panoptocourseembed/view_content.php?course=' . $original->id;
        $module = $this->getDataGenerator()->create_module('panoptocourseembed', [
            'course' => $copy->id,
            'intro' => '<iframe src="' . $src . '" width="720" height="480"></iframe>',
            'introformat' => FORMAT_HTML,
        ]);

        $content = get_fast_modinfo($copy)->get_cm($module->cmid)->content;

        $this->assertStringContainsString('view_content.php?course=' . $copy->id . '"', $content);
        $this->assertStringNotContainsString('course=' . $original->id . '"', $content);
    }

    /**
     * An embed saved under an older site address is repointed at this site.
     *
     * Without this the sanitiser treats the old address as foreign, so the upgrade that
     * re-sanitises every stored intro would strip the embed and write the empty result back.
     *
     * @covers ::panoptocourseembed_rewrite_embed_host
     */
    public function test_rewrite_embed_host_repoints_a_foreign_host(): void {
        global $CFG;

        $src = $CFG->wwwroot . '/mod/panoptocourseembed/view_content.php?course=3&contenturl=' .
            urlencode('https://demo.panopto.com/Panopto/Pages/Viewer.aspx?id=1');
        $local = '<p><iframe src="' . $src . '" width="720" height="480"></iframe><br /></p>';

        // The same embed as it would have been stored under the site's previous address.
        $foreign = str_replace($CFG->wwwroot, 'http://old.example.com', $local);

        $rewritten = panoptocourseembed_rewrite_embed_host($foreign);

        $this->assertSame($local, $rewritten);

        // Which is what lets the sanitiser keep the embed, instead of stripping it and leaving
        // the upgrade to write an empty intro back over it.
        $stored = panoptocourseembed_sanitise_intro($rewritten, FORMAT_HTML);

        $this->assertSame(panoptocourseembed_sanitise_intro($local, FORMAT_HTML), $stored);
        $this->assertStringContainsString('<iframe', $stored);
    }

    /**
     * An embed already pointing at this site is left exactly as it is.
     *
     * @covers ::panoptocourseembed_rewrite_embed_host
     */
    public function test_rewrite_embed_host_leaves_this_site_alone(): void {
        global $CFG;

        $intro = '<iframe src="' . $CFG->wwwroot . '/mod/panoptocourseembed/view_content.php?course=3"></iframe>';

        $this->assertSame($intro, panoptocourseembed_rewrite_embed_host($intro));
    }

    /**
     * Only this plugin's own path is rewritten, so other links are untouched.
     *
     * @covers ::panoptocourseembed_rewrite_embed_host
     */
    public function test_rewrite_embed_host_ignores_other_urls(): void {
        $intro = '<p><a href="https://demo.panopto.com/Panopto/Pages/Viewer.aspx?id=1">Video</a></p>';

        $this->assertSame($intro, panoptocourseembed_rewrite_embed_host($intro));
    }

    /**
     * The rewrite does not rescue a foreign iframe, so the sanitiser still strips it.
     *
     * @covers ::panoptocourseembed_rewrite_embed_host
     */
    public function test_rewrite_embed_host_does_not_weaken_the_sanitiser(): void {
        $intro = '<iframe src="https://evil.example.com/player.html"></iframe>';

        $rewritten = panoptocourseembed_rewrite_embed_host($intro);

        $this->assertSame($intro, $rewritten);
        $this->assertStringNotContainsString('<iframe', panoptocourseembed_sanitise_intro($rewritten, FORMAT_HTML));
    }

    /**
     * A hostile intro is cleaned by the upgrade even though it contains an iframe.
     *
     * The case raised in review: skipping any intro whose iframe disappears on sanitising would
     * keep the script, which then runs for every student because the intro is rendered noclean.
     *
     * @covers ::panoptocourseembed_upgrade_intro
     */
    public function test_upgrade_intro_cleans_a_hostile_intro(): void {
        $intro = '<iframe src="https://evil.example.com/x"></iframe><script>steal()</script>';

        $stored = panoptocourseembed_upgrade_intro($intro, FORMAT_HTML);

        $this->assertStringNotContainsString('<script', $stored);
        $this->assertStringNotContainsString('steal()', $stored);
        $this->assertStringNotContainsString('evil.example.com', $stored);
    }

    /**
     * An embed saved under the site's previous address survives the upgrade, repointed here.
     *
     * @covers ::panoptocourseembed_upgrade_intro
     */
    public function test_upgrade_intro_keeps_an_embed_from_an_older_address(): void {
        global $CFG;

        $src = $CFG->wwwroot . '/mod/panoptocourseembed/view_content.php?course=3';
        $local = '<p><iframe src="' . $src . '" width="720" height="480"></iframe><br /></p>';
        $foreign = str_replace($CFG->wwwroot, 'http://old.example.com', $local);

        $stored = panoptocourseembed_upgrade_intro($foreign, FORMAT_HTML);

        $this->assertSame(panoptocourseembed_sanitise_intro($local, FORMAT_HTML), $stored);
        $this->assertStringContainsString('src="' . s($src) . '"', $stored);
    }

    /**
     * Script stored next to a genuine embed is dropped and the embed is kept.
     *
     * @covers ::panoptocourseembed_upgrade_intro
     */
    public function test_upgrade_intro_drops_script_next_to_an_embed(): void {
        global $CFG;

        $src = $CFG->wwwroot . '/mod/panoptocourseembed/view_content.php?course=3';
        $intro = '<p><iframe src="' . $src . '" width="720" height="480"></iframe></p><script>steal()</script>';

        $stored = panoptocourseembed_upgrade_intro($intro, FORMAT_HTML);

        $this->assertStringContainsString('src="' . s($src) . '"', $stored);
        $this->assertStringNotContainsString('<script', $stored);
    }

    /**
     * When other iframes share the intro, only this plugin's embed is kept and the rest is cleaned.
     *
     * Two iframes make the sanitiser fall back to clean_text, which strips both. The upgrade then
     * keeps the plugin's own embed on its own, so nothing is lost and nothing unsanitised survives.
     *
     * @covers ::panoptocourseembed_upgrade_intro
     */
    public function test_upgrade_intro_keeps_only_the_embed_when_other_iframes_share_it(): void {
        global $CFG;

        $src = $CFG->wwwroot . '/mod/panoptocourseembed/view_content.php?course=3';
        $intro = '<iframe src="' . $src . '" width="720" height="480"></iframe>' .
            '<iframe src="https://evil.example.com/x"></iframe><script>steal()</script>';

        $stored = panoptocourseembed_upgrade_intro($intro, FORMAT_HTML);

        $this->assertSame(1, substr_count($stored, '<iframe'));
        $this->assertStringContainsString('src="' . s($src) . '"', $stored);
        $this->assertStringNotContainsString('evil.example.com', $stored);
        $this->assertStringNotContainsString('<script', $stored);
    }
}
