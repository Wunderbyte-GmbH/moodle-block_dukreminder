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
 * Strings for component 'block_dukreminder', language 'de'
 *
 * @package    block_dukreminder
 * @copyright  gtn gmbh <office@gtn-solutions.com>
 * @author	   Florian Jungwirth <fjungwirth@gtn-solutions.com>
 * @ideaandconcept Gerhard Schwed <gerhard.schwed@donau-uni.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['blockstring'] = 'Course completion reminder';
$string['newblock:addinstance']  = 'Add a newblock block';
$string['newblock:myaddinstance']  = 'Add a newblock block to my moodle';
$string['dukreminder:myaddinstance']  = 'Add a newblock block to my moodle';
$string['pluginname']  = 'Course completion reminder';

$string['tab_course_reminders']  = 'Course reminders';
$string['tab_new_reminder']  = 'New reminder';
$string['tab_sent_reminders'] = 'Sent reminders';

$string['form_title']  = 'Reminder name';
$string['form_subject']  = 'Email subject';
$string['form_subject_help']  = 'Reminder e-mail subject';

$string['form_text']  = 'Email text (for participants)';
$string['form_text_help']  = 'Here the report text to the course participants is shown. 
    <br>Choose among the following placeholders:
    <br>###username### will be replaced by the the name of the participant, 
    <br>###usermail### by the the participant´semail address and 
    <br>###coursename### by name of the current course.';
$string['form_placeholder']  = 'Available placeholders';

$string['form_text_teacher']  = 'Email text (for reports)';
$string['form_text_teacher_help']  = 'Here the report text to the teachers is shown. Choose among the following placeholders: ###coursename### will be replaced within the e-mail by the name of the course. ###users### by a list of the contacted course participants and ###usercount### by the number of the contacted participants.';

$string['form_time']  = 'Time of delivery';
$string['form_dateabsolute']  = 'Absolute delivery date';
$string['form_dateabsolute_help']  = 'At the fixed date reminders are sent to those not having fulfilled the selected criteria below, e. g. (participants) not having completed the course by then.';
$string['form_daterelative']  = 'Relative delivery date';
$string['form_daterelative_help']  = 'A certain period after having fulfilled the selected criteria below, reminders are sent to each user individually.';
$string['form_daterelative_completion']  = 'Delivery [time] after last course completion';
$string['form_to_status']  = 'Course completion status';

$string['form_to_reporttrainer']  = 'Report to teachers';
$string['form_to_reporttrainer_help']  = 'This option defines whether the report is to be sent to the teachers of the course or not.';

$string['form_to_reportsuperior']  = 'Report to superiors';
$string['form_to_reportsuperior_help']  = 'This option defines whether the report is to be sent to the superior/s of the user or not.
        <br>To use this option enter the appropriate e-mail address in the custom profile field <i>manager</i>';

$string['form_to_reportdirector']  = 'Report to directors';
$string['form_to_reportdirector_help']  = 'This option defines whether the report is to be sent to the user´ director or not.
        <br>To use this option enter the appropriate e-mail address in the custom profile field <i>director</i>';
$string['form_to_groups']  = 'Groups';
$string['form_to_mail']  = 'Report to other e-mail recipients';
$string['form_to_mail_help']  = 'Add additional recipients of the trainer report by separating them with a semi-colon.';
$string['form_mailssent']  = 'Sent reminders';

$string['form_delete']  = 'Delete?';
$string['form_criteria']  = 'Criteria';
$string['form_criteria_help']  = '<b>Absolute time</b> - reminders are sent to those who haven´t completed a course or have´t fulfilled the selected criteria
        <br><b>Relative time</b> - reminders are sent after a fixed period to those having fulfilled the selected criteria.
        <br>
        <br>Note: The following improper combinations lead to error messages:
        <br>- absolute time + course enrolment
        <br>- relative time + all
        <br><br>
        <u>Examples:</u>
        <ol>
            <li>absolute time + course completion - At the fixed time reminders are sent to those NOT having completed the course. </li>
            <li>absolute time + all - At the fixed time all course participants are notified, no matter if they have completed the course or not. </li>
            <li>relative time + course completion - A fixed period after having completed the course each participant is notified individually. Participants not having completed the course will not be notified. </li>
            <li>relative time + course enrolment - Each participant is notified a fixed period after having been enrolled in the course unless they have completed the course by then. </li>
        </ol>
        <br>';

$string['form_grade_item'] = 'passed grade';
$string['form_grade_item_help'] = 'Filters for passed grade of choosen activity.';

$string['form_header_general']  = 'General';
$string['form_header_time']  = 'Time';
$string['form_header_criteria']  = 'Criteria';
$string['form_header_groups']  = 'Groups';
$string['form_header_report']  = 'Report options';

$string['form_to_status_all']  = 'All';
$string['form_to_status_completed']  = 'With course completion';
$string['form_to_status_notcompleted']  = 'Without course completion';

$string['daterelative_error']  = 'No negative value permitted.';
$string['criteria_error']  = 'No combination of absolute date and the course enrolment criteria allowed.';
$string['criteria_error2']  = 'No combination of relative time and the all criteria allowed.';
$string['to_mail_error']  = 'Invalid e-mail address! Separate the addresses by a semi-colon!';

$string['email_teacher_notification']  = 'The following {$a->amount} participants in course <b>{$a->course}</b> have been notified:</p>';

$string['criteria_all']  = 'All';
$string['criteria_completion']  = 'Course completion';
$string['criteria_enrolment']  = 'Course enrolment';
$string['criteria_activity_grade']  = 'Activity passed (grade)';
$string['criteria_activity_completed'] = 'Activity completed';

$string['activities'] = 'Completed Activity';
$string['grade_items'] = 'Passed Activity';
