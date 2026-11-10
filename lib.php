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
 * Panopto course embed library functions
 *
 * @package mod_panoptocourseembed
 * @copyright  Panopto 2021
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Get activity name
 *
 * @param object $panoptocourseembed
 * @return string
 */
function panoptocourseembed_get_name($panoptocourseembed) {
    // TODO: Set the name of the panoptocourseembed to the title of the session by storing the session in the intro?
    return get_string('modulename', 'panoptocourseembed');
}

/**
 * Point an embed saved under a different site address at this site's view_content.php.
 *
 * Sites that moved from http to https, changed domain, or added or dropped the www prefix hold
 * intros carrying the old address. panoptocourseembed_sanitise_intro treats those as foreign and
 * strips the embed, so the host is rewritten before it runs. Only this plugin's own
 * view_content.php path is matched and only the scheme and host are replaced, so the query string
 * reaches view_content.php unchanged and its capability check and Panopto host allowlist still
 * apply to whatever it launches.
 *
 * The restore step does the same thing with the wwwroot recorded in the backup. Nothing records
 * the address a site used to have, so here the plugin's own path is what identifies the embed.
 *
 * @param string $intro The stored intro.
 * @return string The intro with any foreign host replaced by this site's.
 */
function panoptocourseembed_rewrite_embed_host($intro) {
    global $CFG;

    $intro = (string) $intro;
    $path = '/mod/panoptocourseembed/view_content.php';
    $target = $CFG->wwwroot . $path;

    return preg_replace_callback(
        '#https?://[^\s"\x27<>]*?' . preg_quote($path, '#') . '#i',
        function () use ($target) {
            return $target;
        },
        $intro
    );
}

/**
 * Work out what the upgrade stores for one intro: cleaned, without losing this plugin's embed.
 *
 * The upgrade re-sanitises every stored intro, because intros saved before sanitising was added
 * reach the course page unfiltered. Two things must hold for every record:
 *
 * - Nothing unsanitised is written back. A hostile intro must not escape the cleanup just by
 *   containing an iframe.
 * - A genuine embed is not lost. The site address may have changed since it was saved, or the
 *   embed may share the intro with other markup, which makes the sanitiser discard everything.
 *
 * So the host is rewritten first and the result sanitised. If that dropped every iframe while one
 * of them was this plugin's own embed, that embed is sanitised on its own and kept, and the rest of
 * the intro is still discarded. Every path returns sanitiser output.
 *
 * @param string $intro The stored intro.
 * @param int $format The stored intro format.
 * @return string The intro to store.
 */
function panoptocourseembed_upgrade_intro($intro, $format = FORMAT_HTML) {
    global $CFG;

    $rewritten = panoptocourseembed_rewrite_embed_host($intro);
    $sanitised = panoptocourseembed_sanitise_intro($rewritten, $format);

    if (stripos($sanitised, '<iframe') !== false) {
        return $sanitised;
    }

    $own = preg_quote($CFG->wwwroot . '/mod/panoptocourseembed/view_content.php', '/');

    if (preg_match('/<iframe\b[^>]*\bsrc\s*=\s*["\']' . $own . '[?"\'][^>]*>/i', $rewritten, $match)) {
        $embed = panoptocourseembed_sanitise_intro($match[0] . '</iframe>', $format);

        if (stripos($embed, '<iframe') !== false) {
            return $embed;
        }
    }

    return $sanitised;
}

/**
 * Sanitise a submitted activity intro.
 *
 * For this module the intro IS the embed: mod_form.php posts it as a hidden PARAM_RAW field and
 * panoptocourseembed_get_coursemodule_info() renders it through format_module_intro(), which
 * hardcodes noclean - so whatever is stored here reaches every student on the course page
 * unfiltered. Cleaning on read would strip the iframe and empty the activity, so the markup has
 * to be made safe on the way in instead.
 *
 * An intro this plugin produced is a single iframe pointing at its own view_content.php. When that
 * is what arrives, take only the source and the dimensions and regenerate the markup from a fixed
 * template, so nothing else in the POST can survive into storage. Anything else - a plain-text
 * intro from the test generator, a legacy embed, a tampered field - goes through clean_text()
 * instead, which is the treatment it would have received had noclean not been forced downstream.
 *
 * @param string $intro the submitted intro markup
 * @param int $format the intro format, used when falling back to clean_text()
 * @return string the intro to store
 */
function panoptocourseembed_sanitise_intro($intro, $format = FORMAT_HTML) {
    global $CFG;

    $intro = (string) $intro;

    // A brand new activity is saved before anything is selected, so an empty intro is legitimate.
    if (trim($intro) === '') {
        return '';
    }

    // Exactly one iframe, or this is not markup the selection buttons produced.
    if (preg_match_all('/<iframe\b[^>]*>/i', $intro, $matches) !== 1) {
        return clean_text($intro, $format);
    }

    $iframe = $matches[0][0];

    if (!preg_match('/\ssrc\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/i', $iframe, $srcmatch)) {
        return clean_text($intro, $format);
    }

    $src = html_entity_decode($srcmatch[1] !== '' ? $srcmatch[1] : ($srcmatch[2] ?? ''), ENT_QUOTES, 'UTF-8');

    // Every source this plugin builds - the default folder view in mod_form.php and both branches
    // of the JS selection callbacks - points at view_content.php on this site. view_content.php is
    // where the capability check and the Panopto host allowlist live for the content it launches,
    // so pinning the embed to it keeps those checks in the loop.
    $expected = $CFG->wwwroot . '/mod/panoptocourseembed/view_content.php';
    $trailing = substr($src, strlen($expected));

    if (strpos($src, $expected) !== 0 || ($trailing !== '' && $trailing[0] !== '?')) {
        return clean_text($intro, $format);
    }

    $safesrc = s($src);

    // Responsive embeds carry an aspect ratio, fixed ones carry width and height attributes, and
    // the untouched default folder view carries neither. Re-emit whichever shape was submitted so
    // that sanitising the markup does not silently change how an existing embed renders.
    if (preg_match('/aspect-ratio\s*:\s*(\d{1,5})\s*\/\s*(\d{1,5})/i', $iframe, $ratio)) {
        return '<p><iframe src="' . $safesrc . '" style="width:100%;' .
                ' height:auto;' .
                ' aspect-ratio: ' . (int) $ratio[1] . ' / ' . (int) $ratio[2] . ';"' .
                ' allowfullscreen="true"></iframe><br /></p>';
    }

    if (
        preg_match('/\swidth\s*=\s*"?(\d{1,5})"?/i', $iframe, $width)
            && preg_match('/\sheight\s*=\s*"?(\d{1,5})"?/i', $iframe, $height)
    ) {
        return '<p><iframe src="' . $safesrc . '" width="' . (int) $width[1] . '"' .
                ' height="' . (int) $height[1] . '"' .
                ' allowfullscreen="true"></iframe><br /></p>';
    }

    return '<p><iframe src="' . $safesrc . '"' .
            ' style="width:100%; max-width:100%; height:600px;"></iframe><br /></p>';
}

/**
 * Point the embed in an intro at the course the activity lives in.
 *
 * The intro stores the course the embed was authored in. Copying, importing or restoring the
 * activity carries that id along, and view_content.php then requires login to the original
 * course, which students of the new course are not enrolled in. Rewriting the id when the intro
 * is rendered keeps every copy launching against its own course.
 *
 * @param string $intro the stored intro markup
 * @param int $courseid the course the activity belongs to
 * @return string the intro with its view_content.php source set to that course
 */
function panoptocourseembed_set_intro_course($intro, $courseid) {
    global $CFG;

    $courseid = (int) $courseid;
    $viewurl = preg_quote($CFG->wwwroot . '/mod/panoptocourseembed/view_content.php', '/');

    return preg_replace_callback(
        '/(\ssrc\s*=\s*")(' . $viewurl . ')(\?[^"]*)?"/i',
        function ($matches) use ($courseid) {
            $query = $matches[3] ?? '';
            $courseparam = '/(\?|&amp;|&)course=\d*/';

            if (preg_match($courseparam, $query)) {
                $query = preg_replace($courseparam, '${1}course=' . $courseid, $query, 1);
            } else {
                $query = ($query === '' ? '?' : $query . '&amp;') . 'course=' . $courseid;
            }

            return $matches[1] . $matches[2] . $query . '"';
        },
        (string) $intro
    );
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $panoptocourseembed
 * @return bool|int
 */
function panoptocourseembed_add_instance($panoptocourseembed) {
    global $DB;

    $panoptocourseembed->name = panoptocourseembed_get_name($panoptocourseembed);
    $panoptocourseembed->timemodified = time();

    if (isset($panoptocourseembed->intro)) {
        $panoptocourseembed->intro = panoptocourseembed_sanitise_intro(
            $panoptocourseembed->intro,
            $panoptocourseembed->introformat ?? FORMAT_HTML
        );
    }

    $id = $DB->insert_record("panoptocourseembed", $panoptocourseembed);

    $completiontimeexpected = !empty($panoptocourseembed->completionexpected) ? $panoptocourseembed->completionexpected : null;
    \core_completion\api::update_completion_date_event(
        $panoptocourseembed->coursemodule,
        'panoptocourseembed',
        $id,
        $completiontimeexpected
    );

    return $id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $panoptocourseembed
 * @param object $mform
 * @return bool
 */
function panoptocourseembed_update_instance($panoptocourseembed, $mform) {
    global $DB;

    $panoptocourseembed->name = panoptocourseembed_get_name($panoptocourseembed);
    $panoptocourseembed->timemodified = time();
    $panoptocourseembed->id = $panoptocourseembed->instance;

    if ($mform) {
        $data = $mform->get_data();
        $panoptocourseembed->intro = $data->intro;
    }

    if (isset($panoptocourseembed->intro)) {
        $panoptocourseembed->intro = panoptocourseembed_sanitise_intro(
            $panoptocourseembed->intro,
            $panoptocourseembed->introformat ?? FORMAT_HTML
        );
    }

    $completiontimeexpected = !empty($panoptocourseembed->completionexpected) ? $panoptocourseembed->completionexpected : null;
    \core_completion\api::update_completion_date_event(
        $panoptocourseembed->coursemodule,
        'panoptocourseembed',
        $panoptocourseembed->id,
        $completiontimeexpected
    );

    return $DB->update_record("panoptocourseembed", $panoptocourseembed);
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id
 * @return bool
 */
function panoptocourseembed_delete_instance($id) {
    global $DB;

    if (! $panoptocourseembed = $DB->get_record("panoptocourseembed", ["id" => $id])) {
        return false;
    }

    $result = true;

    $cm = get_coursemodule_from_instance('panoptocourseembed', $id);
    \core_completion\api::update_completion_date_event($cm->id, 'panoptocourseembed', $panoptocourseembed->id, null);

    if (! $DB->delete_records("panoptocourseembed", ["id" => $panoptocourseembed->id])) {
        $result = false;
    }

    return $result;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 * See get_array_of_activities() in course/lib.php
 *
 * @param object $coursemodule
 * @return cached_cm_info|null
 */
function panoptocourseembed_get_coursemodule_info($coursemodule) {
    global $DB;

    if (
        $panoptocourseembed = $DB->get_record(
            'panoptocourseembed',
            ['id' => $coursemodule->instance],
            'id, name, intro, introformat'
        )
    ) {
        if (empty($panoptocourseembed->name)) {
            // Panoptocourseembed name missing, fix it.
            $panoptocourseembed->name = "panoptocourseembed{$panoptocourseembed->id}";
            $DB->set_field('panoptocourseembed', 'name', $panoptocourseembed->name, ['id' => $panoptocourseembed->id]);
        }
        $panoptocourseembed->intro = panoptocourseembed_set_intro_course($panoptocourseembed->intro, $coursemodule->course);

        $info = new cached_cm_info();
        // No filtering hre because this info is cached and filtered later.
        $info->content = format_module_intro('panoptocourseembed', $panoptocourseembed, $coursemodule->id, false);
        $info->name  = $panoptocourseembed->name;
        return $info;
    } else {
        return null;
    }
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 *
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function panoptocourseembed_reset_userdata($data) {

    // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
    // See MDL-9367.
    return [];
}

/**
 * Features that this activity supports.
 *
 * @uses FEATURE_IDNUMBER
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature FEATURE_xx constant for requested feature
 * @return bool|null True if module supports feature, false if not, null if doesn't know
 */
function panoptocourseembed_supports($feature) {
    switch ($feature) {
        case FEATURE_IDNUMBER:
            return true;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_INTRO:
            return false;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return false;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_NO_VIEW_LINK:
            return true;
        default:
            return null;
    }
}

/**
 * Check if the module has any update that affects the current user since a given time.
 *
 * @param  cm_info $cm course module data
 * @param  int $from the time to check updates from
 * @param  array $filter  if we need to check only specific updates
 * @return stdClass an object with the different type of areas indicating if they were updated or not
 * @since Moodle 3.2
 */
function panoptocourseembed_check_updates_since(cm_info $cm, $from, $filter = []) {
    $updates = course_check_module_updates_since($cm, $from, [], $filter);
    return $updates;
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @param int $userid User id to use for all capability checks, etc. Set to 0 for current user (default).
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_panoptocourseembed_core_calendar_provide_event_action(
    calendar_event $event,
    \core_calendar\action_factory $factory,
    int $userid = 0
) {
    $cm = get_fast_modinfo($event->courseid, $userid)->instances['panoptocourseembed'][$event->instance];

    if (!$cm->uservisible) {
        // The module is not visible to the user for any reason.
        return null;
    }

    $completion = new \completion_info($cm->get_course());

    $completiondata = $completion->get_data($cm, false, $userid);

    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    return $factory->create_instance(
        get_string('view'),
        new \moodle_url('/mod/panoptocourseembed/view.php', ['id' => $cm->id]),
        1,
        true
    );
}

/**
 * Ensure the user is logged in, or perform a cross-site repost if cookies were blocked.
 *
 * @param int $courseid Course to require login for after repost succeeds.
 */
function panoptocourseembed_require_login_or_repost(int $courseid): void {
    global $PAGE, $_POST;

    $context = context_course::instance($courseid);
    $PAGE->set_pagelayout('popup');
    $PAGE->set_context($context);

    if (method_exists('panoptoblock_lti_utility', 'require_login_or_repost')) {
        panoptoblock_lti_utility::require_login_or_repost($courseid, $context);
        return;
    }

    if (!empty($_POST['repost'])) {
        unset($_POST['repost']);
    } else if (!isloggedin()) {
        header_remove('Set-Cookie');
        $output = $PAGE->get_renderer('mod_lti');
        $page = new \mod_lti\output\repost_crosssite_page($_SERVER['REQUEST_URI'], $_POST);
        echo $output->header();
        echo $output->render($page);
        echo $output->footer();
        exit;
    }

    require_login($courseid);
}
