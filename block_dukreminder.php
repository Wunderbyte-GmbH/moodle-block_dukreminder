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
 * dukreminder block caps.
 *
 * @package    block_dukreminder
 * @copyright  gtn gmbh <office@gtn-solutions.com>
 * @author       Florian Jungwirth <fjungwirth@gtn-solutions.com>
 * @ideaandconcept Gerhard Schwed <gerhard.schwed@donau-uni.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Dukreminder block
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  gtn gmbh <office@gtn-solutions.com>
 */
class block_dukreminder extends block_list {

    /**
     * Init
     * @return nothing
    */
     public function init() {
        $this->title = get_string('pluginname', 'block_dukreminder');
    }

    /**
     * Get content
     * @return string
     */
    public function get_content() {
        global $CFG, $OUTPUT, $COURSE;

        if (!has_capability('block/dukreminder:use', context_course::instance($COURSE->id))) {
            return '';
        }

        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        $this->content->items[] = html_writer::link(new moodle_url('/blocks/dukreminder/course_reminders.php',
                                            array('courseid' => $COURSE->id)),
                                            get_string('tab_course_reminders', 'block_dukreminder'),
                                            array('title' => get_string('tab_course_reminders', 'block_dukreminder')));
        $this->content->icons[] = $OUTPUT->pix_icon("t/copy", "edit");

        $this->content->items[] = html_writer::link(new moodle_url('/blocks/dukreminder/new_reminder.php',
                                            array('courseid' => $COURSE->id)),
                                            get_string('tab_new_reminder', 'block_dukreminder'),
                                            array('title' => get_string('tab_new_reminder', 'block_dukreminder')));
        $this->content->icons[] = $OUTPUT->pix_icon("t/editstring", "edit");

        return $this->content;
    }

    /**
     * Allow multiple
     * @return boolean
     */
    public function instance_allow_multiple() {
        return true;
    }

    /**
     * Delete everything related to this instance if you have been using persistent storage other than the configdata field.
     * @return boolean
     */
    public function instance_delete() {
        global $DB, $COURSE;

        return $DB->delete_records('block_dukreminder', array('courseid' => $COURSE->id));
    }
}