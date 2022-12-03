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
 * course_reminders.php
 *
 * @package    block_dukreminder
 * @copyright  gtn gmbh <office@gtn-solutions.com>
 * @author       Florian Jungwirth <fjungwirth@gtn-solutions.com>
 * @ideaandconcept Gerhard Schwed <gerhard.schwed@donau-uni.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__)."/inc.php");
global $DB, $OUTPUT, $PAGE, $cg;
require_once($CFG->libdir . "/tablelib.php");

$courseid = required_param('courseid', PARAM_INT);
$sorting = optional_param('sorting', 'id', PARAM_TEXT);
$sorttype = optional_param('type', 'asc', PARAM_TEXT);

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourse', 'block_simplehtml', $courseid);
}

require_login($course);

$context = context_course::instance($courseid);
require_capability('block/dukreminder:use', $context);

// DELETE.
if (($deleteid = optional_param('delete', 0, PARAM_INT)) > 0) {
    $deleterecord = $DB->get_record('block_dukreminder', array('id' => $deleteid));
    if ($deleterecord->courseid == $courseid) {
        $DB->delete_records('block_dukreminder', array('id' => $deleteid));
    };
}

// Extend dukreminder with copy and mute functionality.
if (($toggleid = optional_param('toggleid', 0, PARAM_INT)) > 0) {
    $DB->update_record('block_dukreminder', array('id' => $toggleid, 'status'=> 1-optional_param('currentstate', 0, PARAM_INT)), $bulk=false);
}
if (($copyid = optional_param('copyid', 0, PARAM_INT)) > 0) {
    $record = $DB->get_record('block_dukreminder', array('id' => $copyid), $fields='*', $strictness=MUST_EXIST); // Read all information from old record.    
    $record->id = null;     // Modify record to have new id.
    $DB->insert_record('block_dukreminder', $record, false, false); // Write to database.
}

$pageidentifier = 'tab_course_reminders';

$PAGE->set_url('/blocks/dukreminder/course_reminders.php', array('courseid' => $courseid));
$PAGE->set_heading(get_string('pluginname', 'block_dukreminder'));
$PAGE->set_title(get_string($pageidentifier, 'block_dukreminder'));

// Build breadcrumbs navigation.
$coursenode = $PAGE->navigation->find($courseid, navigation_node::TYPE_COURSE);
$blocknode = $coursenode->add(get_string('pluginname', 'block_dukreminder'));
$pagenode = $blocknode->add(get_string($pageidentifier, 'block_dukreminder'), $PAGE->url);
$pagenode->make_active();

// Build tab navigation & print header.
echo $OUTPUT->header();
echo $OUTPUT->tabtree(block_dukreminder_build_navigation_tabs($courseid), $pageidentifier);






/* CONTENT REGION */


if (($sentdetails = optional_param('sentdetails', 0, PARAM_INT)) > 0) {
    //$record = $DB->get_record('block_dukreminder', array('id' => $copyid), $fields='*', $strictness=MUST_EXIST); // Read all information from old record.
    //$record->id = null;     // Modify record to have new id.
    //$DB->insert_record('block_dukreminder', $record, false, false); // Write to database.
    
    // If specialdisplayid is current id, insert details table here.
    $table = new html_table();
    $table->head = array('Lastname', 'Firstname', 'ID Number');
    $table->data[] = array('Lastname' => 'asa', 'Firstname'=>'asfasf', 'ID Number' => '123');
    $table->data[] = array('Lastname' => 'asa', 'Firstname'=>'asfasf', 'ID Number' => '123');
    echo html_writer::table($table);
}





$table = new html_table();

$table->head = array(html_writer::link($PAGE->url . "&sorting=title", get_string('form_title', 'block_dukreminder')),
        html_writer::link($PAGE->url . "&sorting=subject", get_string('form_subject', 'block_dukreminder')),
        html_writer::link($PAGE->url . "&sorting=dateabsolute&type=desc", get_string('form_time', 'block_dukreminder')),
        //html_writer::link($PAGE->url . "&sorting=to_status&type=desc", get_string('form_to_status', 'block_dukreminder')), // to be deleted, G. Schwed
        html_writer::link($PAGE->url . "&sorting=criteria&type=desc", get_string('form_criteria', 'block_dukreminder')),
        html_writer::link($PAGE->url . "&sorting=criteria&type=desc", get_string('form_to_groups', 'block_dukreminder')),
        html_writer::link($PAGE->url . "&sorting=mailssent&type=desc", get_string('form_mailssent', 'block_dukreminder')),
        /*html_writer::div('Options')*/ '');

// Fetch all reminders.
$data = $DB->get_records('block_dukreminder',
    array('courseid' => $courseid),
    $sorting . ' ' . $sorttype,
    'id, title, subject, dateabsolute, daterelative, criteria, to_groups, mailssent, status');
foreach ($data as $record) {
    // Calculate date and time.
    // calculate weeks, days, hours for relative time
    $daterelative = "";
    $seconds = $record->daterelative;
    $weeks = floor($seconds / 86400 / 7);
    if ($weeks > 0) $daterelative = $weeks . "w ";

    $seconds = $seconds - $weeks * 86400 * 7;
    $days = floor($seconds / 86400);
    if ($days > 0) $daterelative .= $days . "d ";

    $seconds = $seconds - $days * 86400;
    $hours = floor($seconds / 3600);
    if ($hours > 0) $daterelative .= $hours . "h ";

    $seconds = $seconds - $hours * 3600;
    $minutes = floor($seconds / 60);
    if ($minutes > 0) $daterelative .= $minutes . "m ";

    $daterelative .= "nach (Abschluss von) ...";
    $record->dateabsolute = ($record->dateabsolute > 0) ? date('d.m.Y', $record->dateabsolute) : $daterelative;
    unset($record->daterelative); // to prevent another column in the table for daterelative

    // Groups
    if ($record->to_groups <> "") {
        $groupids = explode(";", $record->to_groups);
        $groups = array();
        foreach ($groupids as $groupid) {
            $groups[$groupid] = groups_get_group_name($groupid);
        }
            $record->to_groups = implode("<br>", $groups);
    } else {
            $record->to_groups = 'Alle';
    }

    // Criteria
    $record->criteria = block_dukreminder_get_criteria($record->criteria);

    // Add link to mailssent.
    $record->mailssent = 
        html_writer::link(
            new moodle_url('/blocks/dukreminder/sent_reminders.php',
                    array('courseid' => $COURSE->id, 'reminderid' => $record->id)), $record->mailssent);
    
    // Check which icon for status we want to display.
    if ($record->status) { $statusicon="/blocks/dukreminder/pix/show.png"; }
    else { $statusicon="/blocks/dukreminder/pix/hide.png"; } // Variable pix_icon should be used.

    // Display icons and links for Edit and delete.
    $record->actions =
 
        // Toggle if this reminder is active or not.
        html_writer::link(
            new moodle_url('/blocks/dukreminder/course_reminders.php',
                    array('courseid' => $COURSE->id, 'toggleid' => $record->id, 'currentstate' => $record->status)),
            html_writer::empty_tag('img', array('src' => new moodle_url("$statusicon"))))

        // Copy this element.
        .html_writer::link(
                    new moodle_url('/blocks/dukreminder/course_reminders.php',
                            array('courseid' => $COURSE->id, 'copyid' => $record->id)),
            html_writer::empty_tag('img', array('src' => new moodle_url('/blocks/dukreminder/pix/copy.png'))))

        // Generate a new reminder.
        .html_writer::link(
            new moodle_url('/blocks/dukreminder/new_reminder.php', 
                array('courseid' => $COURSE->id, 'reminderid' => $record->id)),
            html_writer::empty_tag('img', array('src' => new moodle_url('/blocks/dukreminder/pix/edit.png'))))
        
        // Delete existing reminder.
        .html_writer::link(
            new moodle_url('/blocks/dukreminder/course_reminders.php',
                array('courseid' => $COURSE->id, 'sorting' => $sorting, 'delete' => $record->id)),
            html_writer::empty_tag('img', array('src' => new moodle_url('/blocks/dukreminder/pix/del.png'))),
                array("onclick" => "return confirm('".get_string('form_delete', 'block_dukreminder')."')"));

    // Don't display id, it is only used for the delete link.
    unset($record->id);
    unset($record->status); // Hide status from table.
 
}
$table->data = $data;
echo html_writer::table($table);

/* END CONTENT REGION */

echo $OUTPUT->footer();
