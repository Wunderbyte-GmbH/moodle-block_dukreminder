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
 * dukreminder block.
 *
 * @package    block_dukreminder
 * @copyright  gtn gmbh <office@gtn-solutions.com>
 * @author       Florian Jungwirth <fjungwirth@gtn-solutions.com>
 * @ideaandconcept Gerhard Schwed <gerhard.schwed@donau-uni.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dukreminder\event;
defined('MOODLE_INTERNAL') || die();

/**
 * send mail
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  gtn gmbh <office@gtn-solutions.com>
 * @package    block_dukreminder
 */
class send_mail extends \core\event\base {

    /**
     * Init
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'block_dukreminder';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventsendmail', 'block_dukreminder');
    }

    /**
     * Get description
     * @return string
     */
    public function get_description() {
        return "User {$this->relateduserid} was notified";
    }

    /**
     * Get URL related to the action
     *
     * @return \moodle_url
     */
    public function get_url() {
        // Using [] array syntax.
        return new \moodle_url('/blocks/dukreminder/course_reminders.php', ['courseid' => $this->contextinstanceid]);
    }
}