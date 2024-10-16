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
 * Collection of useful functions and constants
 *
 * @package    block_dukreminder
 * @copyright  gtn gmbh <office@gtn-solutions.com>
 * @author       Florian Jungwirth <fjungwirth@gtn-solutions.com>
 * @ideaandconcept Gerhard Schwed <gerhard.schwed@donau-uni.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__)."/inc.php");
require_once($CFG->libdir.'/datalib.php');

global $DB, $OUTPUT, $PAGE, $USER;

$courseid = required_param('courseid', PARAM_INT);
$reminderid = optional_param('reminderid', 0, PARAM_INT);

$course=get_course($courseid);

require_login($course);

$context = context_course::instance($courseid);
require_capability('block/dukreminder:use', $context);

$pageidentifier = 'tab_new_reminder';

$PAGE->set_url('/blocks/dukreminder/new_reminder.php', array('courseid' => $courseid));
$PAGE->set_heading(get_string('pluginname', 'block_dukreminder'));
$PAGE->set_title(get_string($pageidentifier, 'block_dukreminder'));
block_dukreminder_init_js_css();
$PAGE->requires->js('/blocks/dukreminder/lib/form.js', true);

// Build breadcrumbs navigation.
$coursenode = $PAGE->navigation->find($courseid, navigation_node::TYPE_COURSE);
$blocknode = $coursenode->add(get_string('pluginname', 'block_dukreminder'));
$pagenode = $blocknode->add(get_string($pageidentifier, 'block_dukreminder'), $PAGE->url);
$pagenode->make_active();

/* CONTENT REGION */

// Include form.php .
require_once('lib/reminder_form.php');

if ($reminderid > 0) {
    $toform = $DB->get_record('block_dukreminder', array('id' => $reminderid));
    $toform->text = array("text" => $toform->text, "format" => 1);
    $toform->text_teacher = array("text" => $toform->text_teacher, "format" => 1);
    $toform->disable = ($toform->dateabsolute > 0 && $toform->dateabsolute < time()) ? 1 : 0;
}
// Instantiate form.
$mform = new reminder_form($PAGE->url,
        array("disable" => (isset($toform) && ($toform->dateabsolute > 0 && $toform->dateabsolute < time()) ? 1 : 0)));

// Form processing and displaying is done here.
if ($mform->is_cancelled()) {
    // Handle form cancel operation, if cancel button is present on form.
    $df = 1;
    
    // Go back to reminder overview
    $url = new moodle_url('/blocks/dukreminder/course_reminders.php', array('courseid' => $PAGE->course->id));
    redirect($url);
} else if ($fromform = $mform->get_data()) {
    // In this case you process validated data. $mform->get_data() returns data posted in form.
    if ($fromform->id == 0) {
        // ... new entry.
        $fromform->courseid = $courseid;
        $fromform->timecreated = time();
        $fromform->createdby = $USER->id;
        $fromform->text = $fromform->text['text'];
        $fromform->text_teacher = $fromform->text_teacher['text'];
        $fromform->to_status = 0;

        if ($fromform->daterelative > 0) {
            $fromform->dateabsolute = 0;
        }
        if (isset($fromform->to_groups)) {
            $fromform->to_groups = implode(";", $fromform->to_groups);
        };

        $DB->insert_record('block_dukreminder', $fromform);
    } else {
        $fromform->timemodified = time();
        $fromform->modifiedby = $USER->id;
        $fromform->text = $fromform->text['text'];
        $fromform->text_teacher = $fromform->text_teacher['text'];
        if (isset($fromform->to_groups)) {
            $fromform->to_groups = implode(";", $fromform->to_groups);
        }
        if ($fromform->daterelative > 0) {
            $fromform->dateabsolute = 0;
        }
        $DB->update_record('block_dukreminder', $fromform);
    }
    redirect(new moodle_url("/blocks/dukreminder/course_reminders.php", array("courseid" => $courseid)));
} else {
    // Build tab navigation & print header.
    echo $OUTPUT->header();
    echo $OUTPUT->tabtree(block_dukreminder_build_navigation_tabs($courseid), $pageidentifier);

    // This branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed.
    // or on the first display of the form.

    // Set default data (if any).
    if ($reminderid > 0) {
        $mform->set_data($toform);
    }
    // Displays the form.
    $mform->display();
}

/* END CONTENT REGION */

echo $OUTPUT->footer();
