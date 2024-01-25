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
 * Panopto course embed module settings information
 *
 * @package mod_panoptocourseembed
 * @copyright  Panopto 2021
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;


require_once(dirname(__FILE__) . '/classes/admin/trim_configtext.php');

if ($ADMIN->fulltree) {
    $settings->add(
        new admin_setting_configtext_trimmed_courseembed(
            'mod_panoptocourseembed/default_panopto_server',
            get_string('default_panopto_server', 'mod_panoptocourseembed'),
            get_string('default_panopto_server_desc', 'mod_panoptocourseembed'),
            '',
            PARAM_TEXT
        )
    );
    $settings->add(
        new admin_setting_configcheckbox(
            'mod_panoptocourseembed/is_responsive',
            get_string('is_responsive', 'mod_panoptocourseembed'),
            get_string('is_responsive_desc', 'mod_panoptocourseembed'),
            0
        )
    );
}
