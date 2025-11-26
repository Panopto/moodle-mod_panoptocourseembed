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
 * Handles content item return.
 *
 * @package    mod_panoptocourseembed
 * @copyright  2021 Panopto
 * @author     Panopto
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/blocks/panopto/lib/lti/panoptoblock_lti_utility.php');
require_once($CFG->dirroot . '/blocks/panopto/lib/panopto_data.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/mod/lti/lib.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/mod/lti/locallib.php');

$courseid = required_param('course', PARAM_INT);
$id       = required_param('id', PARAM_INT);
$jwt      = optional_param('JWT', '', PARAM_RAW);

require_login($courseid);

$context = context_course::instance($courseid);

$items = '';

if (!empty($jwt)) {
    $params = lti_convert_from_jwt($id, $jwt);
    $consumerkey = $params['oauth_consumer_key'] ?? '';
    $messagetype = $params['lti_message_type'] ?? '';
    $version = $params['lti_version'] ?? '';
    $items = $params['content_items'] ?? '';
    $errormsg = $params['lti_errormsg'] ?? '';
    $msg = $params['lti_msg'] ?? '';
} else {
    $consumerkey = required_param('oauth_consumer_key', PARAM_RAW);
    $messagetype = required_param('lti_message_type', PARAM_TEXT);
    $version = required_param('lti_version', PARAM_TEXT);
    $items = optional_param('content_items', '', PARAM_RAW_TRIMMED);
    $errormsg = optional_param('lti_errormsg', '', PARAM_TEXT);
    $msg = optional_param('lti_msg', '', PARAM_TEXT);
}

$contentitems = json_decode($items);

$errors = [];

// Affirm that the content item is a JSON object.
if (!is_object($contentitems) && !is_array($contentitems)) {
    $errors[] = 'invalidjson';
}

// Get and validate frame sizes.
$framewidth = 720;
$fwidth = $contentitems->{'@graph'}[0]->placementAdvice->displayWidth;
if (!empty($fwidth)) {
    $framewidth = is_numeric($fwidth) ? $fwidth : $framewidth;
}

$frameheight = 480;
$fheight = $contentitems->{'@graph'}[0]->placementAdvice->displayHeight;
if (!empty($fheight)) {
    $frameheight = is_numeric($fheight) ? $fheight : $frameheight;
}

$title = "";
$itemtitle = $contentitems->{'@graph'}[0]->title;
if (!empty($itemtitle)) {
    $invalidcharacters = ["$", "%", "#", "<", ">"];
    $cleantitle = str_replace($invalidcharacters, "", $itemtitle);
    $title = is_string($cleantitle) ? $cleantitle : $title;
}

$url = "";
$contenturl = $contentitems->{'@graph'}[0]->url;
if (!empty($contenturl)) {
    $panoptodata = new \panopto_data($courseid);
    $baseurl = parse_url($contenturl, PHP_URL_HOST);
    if (strcmp($panoptodata->servername, $baseurl) === 0) {
        $url = $contenturl;
    }
}

$customdata = $contentitems->{'@graph'}[0]->custom;

// In this version of Moodle LTI contentitem request we do not want the interactive viewer.
unset($customdata->use_panopto_interactive_view);

$ltiviewerurl = new moodle_url("/mod/panoptocourseembed/view_content.php");
?>

<?php
/**
 * This script handles the content item return process.
 *
 * It checks for errors and either triggers an error callback or dispatches
 * a custom event with details about the selected session.
 *
 * @package    mod_panoptocourseembed
 * @copyright  2024 Panopto
 * @author     Panopto
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
?>
<script type="text/javascript">
    /**
     * Trigger the handleError callback with the errors.
     */
    <?php
    /**
     * This is the if statement checking for errors.
     *
     * @package    mod_panoptocourseembed
     * @copyright  2024 Panopto
     * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     */
    if (count($errors) > 0) : ?>
        parent.document.CALLBACKS.handleError(<?php
        /**
         * JSON encode the errors array for the handleError callback.
         *
         * @package    mod_panoptocourseembed
         * @copyright  2024 Panopto
         * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
         */
        echo json_encode($errors); ?>);
        <?php
        /**
         * Else statement for handling successful event dispatch.
         *
         * @package    mod_panoptocourseembed
         * @copyright  2024 Panopto
         * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
         */
    else : ?>
        /**
         * Create and dispatch a custom event 'sessionSelected' with session details.
         * This event should close the Panopto popup and pass the new content URL to the existing iframe.
         */
        const detailObject = {
            title: "<?php
            /**
             * Title for the LTI viewer, generated without additional parameters.
             *
             * @package    mod_panoptocourseembed
             * @copyright  2024 Panopto
             * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
             */
            echo $title ?>",

            ltiViewerUrl: "<?php
            /**
             * URL for the LTI viewer, generated without additional parameters.
             *
             * @package    mod_panoptocourseembed
             * @copyright  2024 Panopto
             * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
             */
            echo $ltiviewerurl->out(false) ?>",

            contentUrl: "<?php
            /**
             * Content URL for embedding in the iframe.
             *
             * @package    mod_panoptocourseembed
             * @copyright  2024 Panopto
             * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
             */
            echo $url ?>",

            customData: "<?php
            /**
             * Custom data encoded in JSON format, URL-encoded for safe transport.
             *
             * @package    mod_panoptocourseembed
             * @copyright  2024 Panopto
             * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
             */
            echo urlencode(json_encode($customdata)) ?>",

            width: <?php
            /**
             * Frame width, dynamically retrieved from PHP.
             *
             * @package    mod_panoptocourseembed
             * @copyright  2024 Panopto
             * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
             */
            echo $framewidth ?>,

            height: <?php
            /**
             * Frame height, dynamically retrieved from PHP.
             *
             * @package    mod_panoptocourseembed
             * @copyright  2024 Panopto
             * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
             */
            echo $frameheight ?>
        };

        const sessionSelectedEvent = new CustomEvent('sessionSelected', {
            detail: detailObject,
            bubbles: false,
            cancelable: false
        });

        parent.document.body.dispatchEvent(sessionSelectedEvent);
        <?php
        /**
         * End of if-else statement.
         *
         * @package    mod_panoptocourseembed
         * @copyright  2024 Panopto
         * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
         */
    endif; ?>
</script>
