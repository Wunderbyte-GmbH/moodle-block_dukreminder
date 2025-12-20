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
 * Event observer for block_dukreminder.
 *
 * @package    block_dukreminder
 * @copyright  2025 David Bogner Wunderbyte GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dukreminder;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer class.
 */
class observer {

    /**
     * Delete all reminder entries associated with a course when that course is deleted.
     *
     * @param \core\event\course_deleted $event
     * @return void
     */
    public static function course_deleted(\core\event\course_deleted $event) {
        global $DB;
        // The objectid of the course_deleted event is the ID of the course.
        $courseid = $event->objectid;

        if (!$courseid) {
            return;
        }

        // Delete records from block_dukreminder where courseid matches.
        $DB->delete_records('block_dukreminder', ['courseid' => $courseid]);
    }
}