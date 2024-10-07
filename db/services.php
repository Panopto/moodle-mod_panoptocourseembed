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
 * Panopto course embed external functions and service definitions.
 *
 * @package mod_panoptocourseembed
 * @category external
 * @copyright  Panopto 2021
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$functions = [
    'mod_panoptocourseembed_get_panoptocourseembeds_by_courses' => [
        'classname'     => 'mod_panoptocourseembed\external\external',
        'methodname'    => 'get_panoptocourseembeds_by_courses',
        'description'   =>
            'Returns a list of panoptocourseembeds in a provided list of courses, if no list is provided all panoptocourseembeds that the user can view will be returned.',
        'type'          => 'read',
        'capabilities'  => 'mod/panoptocourseembed:view',
        'services'      => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
];
