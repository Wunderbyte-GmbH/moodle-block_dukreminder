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
define('BLOCK_DUKREMINDER_COMPLETION_STATUS_ALL', 0);
define('BLOCK_DUKREMINDER_COMPLETION_STATUS_COMPLETED', 1);
define('BLOCK_DUKREMINDER_COMPLETION_STATUS_NOTCOMPLETED', 2);

define('BLOCK_DUKREMINDER_PLACEHOLDER_COURSENAME', '###coursename###');
define('BLOCK_DUKREMINDER_PLACEHOLDER_USERNAME', '###username###');
define('BLOCK_DUKREMINDER_PLACEHOLDER_USERMAIL', '###usermail###');
define('BLOCK_DUKREMINDER_PLACEHOLDER_USERCOUNT', '###usercount###');
define('BLOCK_DUKREMINDER_PLACEHOLDER_USERS', '###users###');

define('BLOCK_DUKREMINDER_CRITERIA_COMPLETION', 250000);
define('BLOCK_DUKREMINDER_CRITERIA_ENROLMENT', 250001);
define('BLOCK_DUKREMINDER_CRITERIA_ALL', 250002);

// SHOULD BE CHANGED.
define('BLOCK_DUKREMINDER_EMAIL_DUMMY', 2);

/**
 * Build navigation tabs
 * @param integer $courseid
 */
function block_dukreminder_build_navigation_tabs($courseid) {

    $rows[] = new tabobject('tab_course_reminders',
        new moodle_url('/blocks/dukreminder/course_reminders.php',
        array("courseid" => $courseid)),
        get_string('tab_course_reminders', 'block_dukreminder'));
    $rows[] = new tabobject('tab_new_reminder',
        new moodle_url('/blocks/dukreminder/new_reminder.php',
        array("courseid" => $courseid)),
        get_string('tab_new_reminder', 'block_dukreminder'));
    return $rows;
}

/**
 * Init Js and CSS
 * @return void
 */
function block_dukreminder_init_js_css() {

}
/**
 * This function gets all the pending reminder entries. An entry is pending
 * if dateabsolute is set and it is not sent yet (sent = 0)
 * OR
 * if daterelative is set
 *
 * @return array $entries
 */
function block_dukreminder_get_pending_reminders() {
    global $DB;
    $now = time();
    $entries = $DB->get_records_select('block_dukreminder',
    "status = 0 AND ((sent = 0 AND dateabsolute > 0 AND dateabsolute < $now) OR (dateabsolute = 0 AND daterelative > 0))");

    // Check for non existing (deleted) courses and unset the related reminders.
    foreach ($entries as $entry) {
        $course = $DB->record_exists('course', ['id' => $entry->courseid]);
        if (!$course) {
            mtrace("... course $entry->courseid does not exist (perhaps deleted) => skipped<br/>");
            unset($entries[$entry->id]);
            // Delete record from DB so it is not fetched forever.
            $DB->delete_records('block_dukreminder', ['id' => $entry->id]);
        }
    }
    mtrace("... " . count($entries) . " pending reminder(s) found<br/>");
    return $entries;
}

/**
 * Replace placeholders
 * @param string $text
 * @param string $coursename
 * @param string $username
 * @param string $usermail
 * @param string $users
 * @param string $usercount
 * @return string
 */
function block_dukreminder_replace_placeholders(string $text, string $coursename = '', string $username = '',
            string $usermail = '', string $users = '', string $usercount = ''): string {

    $text = str_replace(BLOCK_DUKREMINDER_PLACEHOLDER_COURSENAME, $coursename, $text);
    $text = str_replace(BLOCK_DUKREMINDER_PLACEHOLDER_USERMAIL, $usermail, $text);
    $text = str_replace(BLOCK_DUKREMINDER_PLACEHOLDER_USERNAME, $username, $text);
    $text = str_replace(BLOCK_DUKREMINDER_PLACEHOLDER_USERCOUNT, $usercount, $text);
    $text = str_replace(BLOCK_DUKREMINDER_PLACEHOLDER_USERS, $users, $text);

    return $text;
}

/**
 * This function filters the users to recieve a reminder according to the
 * criterias recorded in the database.
 * The criterias are:
 *  - deadline: amount of sec after course enrolment
 *  - groups: user groups specified in the course
 *  - completion status: if users have already completed/not completed the course
 *
 * @param stdClass $entry database entry of block_dukreminder table
 * @return array $users users to recieve a reminder
 */
function block_dukreminder_filter_users($entry) {
    global $DB, $CFG;

    require_once($CFG->dirroot . '/lib/completionlib.php');

    // Fetch all potential users (students).
    $users = get_role_users(5, context_course::instance($entry->courseid));
    if (!$users) {
        return [];
    }

    // -------------------------------------------------------------------------
    // DETECT CRITERIA TYPE
    // -------------------------------------------------------------------------

    $is_criteria_all        = ($entry->criteria == BLOCK_DUKREMINDER_CRITERIA_ALL);
    $is_criteria_enrolment  = ($entry->criteria == BLOCK_DUKREMINDER_CRITERIA_ENROLMENT);
    $is_criteria_completion = ($entry->criteria == BLOCK_DUKREMINDER_CRITERIA_COMPLETION);

    $is_activity_criteria   = (!$is_criteria_all &&
            !$is_criteria_enrolment &&
            !$is_criteria_completion);

    // If activity criteria → load DB record safely
    $activitycriteria = null;
    if ($is_activity_criteria) {
        $activitycriteria = $DB->get_record(
                'course_completion_criteria',
                ['id' => $entry->criteria],
                '*',
                IGNORE_MISSING
        );

        if (!$activitycriteria) {
            // Invalid criterion → prevent fatal errors
            return [];
        }
    }

    // -------------------------------------------------------------------------
    // ABSOLUTE DATE REMINDERS
    // -------------------------------------------------------------------------
    if ($entry->dateabsolute > 0) {

        // COURSE COMPLETION CRITERIA
        if ($is_criteria_completion) {
            foreach ($users as $user) {
                $completed = $DB->get_field('course_completions', 'timecompleted',
                        ['course' => $entry->courseid, 'userid' => $user->id]);

                if ($completed) {
                    unset($users[$user->id]);
                }
            }
        }

        // ACTIVITY COMPLETION CRITERIA
        else if ($is_activity_criteria) {

            $course = $DB->get_record('course', ['id' => $entry->courseid], '*', MUST_EXIST);
            $completion = new completion_info($course);

            // Build activity criterion
            $criterion = completion_criteria::factory((array)$activitycriteria);

            foreach ($users as $user) {
                $usercompletion = $completion->get_user_completion($user->id, $criterion);
                if ($usercompletion->is_complete()) {
                    unset($users[$user->id]);
                }
            }
        }
    }

    // -------------------------------------------------------------------------
    // RELATIVE DATE REMINDERS
    // -------------------------------------------------------------------------
    else if ($entry->daterelative > 0) {

        // Load sent mail records
        $mailssent = $DB->get_records('block_dukreminder_mailssent',
                ['reminderid' => $entry->id],
                '',
                'userid'
        );

        // RELATIVE ENROLMENT DATE
        if ($is_criteria_enrolment) {

            $enrolids = implode(',',
                    $DB->get_fieldset_select('enrol', 'id', "courseid = {$entry->courseid}")
            );

            foreach ($users as $user) {

                if (isset($mailssent[$user->id])) {
                    unset($users[$user->id]);
                    continue;
                }

                $timestart = $DB->get_field_select('user_enrolments', 'timestart',
                        "userid = {$user->id} AND enrolid IN ($enrolids)");

                if (!$timestart || ($timestart + $entry->daterelative > time())) {
                    unset($users[$user->id]);
                }
            }
        }

        // RELATIVE COURSE COMPLETION DATE
        else if ($is_criteria_completion) {

            foreach ($users as $user) {

                if (isset($mailssent[$user->id])) {
                    unset($users[$user->id]);
                    continue;
                }

                $completiontime = $DB->get_field(
                        'course_completions',
                        'timecompleted',
                        ['userid' => $user->id, 'course' => $entry->courseid]
                );

                if (!$completiontime || ($completiontime + $entry->daterelative > time())) {
                    unset($users[$user->id]);
                }
            }
        }

        // RELATIVE ACTIVITY COMPLETION DATE
        else if ($is_activity_criteria) {

            $course = $DB->get_record('course', ['id' => $entry->courseid], '*', MUST_EXIST);
            $completion = new completion_info($course);
            $criterion = completion_criteria::factory((array)$activitycriteria);

            foreach ($users as $user) {

                if (isset($mailssent[$user->id])) {
                    unset($users[$user->id]);
                    continue;
                }

                $usercompletion = $completion->get_user_completion($user->id, $criterion);

                if (!$usercompletion->timecompleted ||
                        ($usercompletion->timecompleted + $entry->daterelative > time())
                ) {
                    unset($users[$user->id]);
                }
            }
        }
    }

    // -------------------------------------------------------------------------
    // GROUP FILTER (invert logic: remove users IN selected groups)
    // -------------------------------------------------------------------------
    if (!empty($entry->to_groups)) {

        $groupids = explode(';', $entry->to_groups);

        foreach ($users as $user) {
            foreach ($groupids as $gid) {
                if (groups_is_member($gid, $user->id)) {
                    unset($users[$user->id]);
                    break;
                }
            }
        }
    }

    return $users;
}

/**
 * Get manager
 * @param object $user
 * @return boolean
 */
function block_dukreminder_get_manager($user) {
    global $DB;
    // Bestimme Vorgesetzten (= Manager) zum User
    if (isset($user->address)) { // Vorgesetzte stehen in Moodle im Adressfeld des Users
        $manager = addslashes(substr($user->address, 0, 50)) . "%"; // addslashes wegen ' in manchen Usernamen
        // Suche userid des Vorgesetzten in mdl_user.
        $select = "idnumber LIKE '$manager'";
        $managerid = $DB->get_field_select('user', 'id', $select);

        // Hole Details des Vorgesetzten aus mdl_user.
        return $DB->get_record('user', array('id' => $managerid));
    }
    return false;
}

/**
 * Replace placeholders
 * @param string $course
 * @param array $users
 * @param boolean $textteacher
 * @return string
 */
function block_dukreminder_get_mail_text($course, $users, $textteacher = null) {

    $userlisting = '';
    foreach ($users as $user) {
        $userlisting .= "\n" . fullname($user);
    }

    // If text_teacher is not set, use lang string (for old reminders).
    if (!$textteacher) {
        $textparams = new stdClass();
        $textparams->amount = count($users);
        $textparams->course = $course;

        $mailtext = get_string('email_teacher_notification', 'block_dukreminder', $textparams);
        $mailtext .= $userlisting;
    } else {
        // If text_teacher is set, use it and replace placeholders.
        $mailtext = block_dukreminder_replace_placeholders($textteacher, $course, '', '', $userlisting, count($users));
        $mailtext = strip_tags($mailtext);
    }

    return $mailtext;
}

/**
 * Get course teachers
 * @param string $coursecontext
 * @return array
 */
function block_dukreminder_get_course_teachers($coursecontext) {
    return array_merge(get_role_users(4, $coursecontext),
        get_role_users(3, $coursecontext),
        get_role_users(2, $coursecontext),
        get_role_users(1, $coursecontext));
}

/**
 * Get criteria
 * @param string $entry
 * @return string
 */
function block_dukreminder_get_criteria($entry) {
    global $DB;

    if ($entry == BLOCK_DUKREMINDER_CRITERIA_COMPLETION) {
        return get_string('criteria_completion', 'block_dukreminder');
    };
    if ($entry == BLOCK_DUKREMINDER_CRITERIA_ENROLMENT) {
        return get_string('criteria_enrolment', 'block_dukreminder');
    };
    if ($entry == BLOCK_DUKREMINDER_CRITERIA_ALL) {
        return get_string('criteria_all', 'block_dukreminder');
    }

    $completioncriteriaentry = $DB->get_record('course_completion_criteria', array('id' => $entry));
    $mod = get_coursemodule_from_id($completioncriteriaentry->module, $completioncriteriaentry->moduleinstance);

    return $mod->name;
}