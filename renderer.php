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
 * This file contains the renderers for the Panopto Course Embed activity within Moodle
 *
 * @package mod_panoptocourseembed
 * @copyright  Panopto 2021
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * This class renders the course embed pages.
 */
class mod_panoptocourseembed_renderer extends plugin_renderer_base {
    /**
     * This function returns HTML markup to render the content selection buttons.
     *
     * @param string $previewintro Preview intro.
     * @return string Returns HTML markup.
     */
    public function get_content_selection_buttons($previewintro = '') {
        $html = '';

        $attr = array(
            'type' => 'hidden',
            'name' => 'sesskey',
            'value' => sesskey()
        );
        $html .= html_writer::empty_tag('input', $attr);

        $html .= html_writer::start_tag('div', ['id' => 'panopto-intro-preview', 'class' => 'intro-preview-container']);
        $html .= $previewintro;
        $html .= html_writer::end_tag('div');

        $html .= html_writer::start_tag('center', ['class' => 'm-t-2 m-b-1']);

        $attr = array(
            'class' => 'btn btn-primary',
            'type' => 'button',
            'id' => 'id_select_video',
            'name' => 'select_video',
            'value' => get_string('selectvideo', 'panoptocourseembed')
        );

        $html .= html_writer::empty_tag('input', $attr);

        $attr = array(
            'class' => 'panopto-btn-divider'
        );
        $html .= html_writer::start_tag('div', $attr);
        $html .= html_writer::end_tag('div');

        $attr = array(
            'class' => 'btn btn-primary',
            'type' => 'button',
            'name' => 'folder_view',
            'id' => 'id_folder_view',
            'value' => get_string('folderview', 'panoptocourseembed'));

        $html .= html_writer::empty_tag('input', $attr);

        $html .= html_writer::end_tag('center');

        return $html;
    }
}
