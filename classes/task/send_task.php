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

namespace block_dukreminder\task;

use core\task\scheduled_task;
use context_course;
use block_dukreminder\event\mail_sent;

/**
 * Scheduled task to send pending reminders.
 * @package    block_dukreminder
 * @copyright  gtn gmbh <office@gtn-solutions.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_task extends scheduled_task {
    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('send_task', 'block_dukreminder');
    }

    /**
     * Execute the task.
     *
     * @return bool
     */
    public function execute(): bool {
        // Robuster Pfad zum Einbinden der Helper-Funktionen.
        require_once(__DIR__ . "/../../inc.php");

        global $DB;

        $entries = block_dukreminder_get_pending_reminders();

        foreach ($entries as $entry) {
            $mailssent = 0;
            $creator = $DB->get_record('user', ['id' => $entry->createdby]);
            $course = $DB->get_record('course', ['id' => $entry->courseid]);
            $coursecontext = context_course::instance($course->id);

            $users = block_dukreminder_filter_users($entry);
            $managers = [];

            // Go through users and send mails AND save the user managers.
            foreach ($users as $user) {
                // Wenn E-Mails bereits gesendet wurden, überspringen Sie diesen Benutzer.
                if ($DB->record_exists('block_dukreminder_mailssent', ['reminderid' => $entry->id, 'userid' => $user->id])) {
                    mtrace("... email already sent to user $user->id => skipped\n");
                    continue;
                }

                $user->mailformat = FORMAT_HTML;

                if(!empty($user->email)) {
                    $mailtext = block_dukreminder_replace_placeholders($entry->text, $course->fullname, fullname($user), $user->email);
                    email_to_user($user, $creator, $entry->subject, strip_tags($mailtext), $mailtext);
                    $mailssent++;
                }

                if ($entry->daterelative > 0) {
                    // Fügt timesent hinzu, um Datenbankfehler zu vermeiden, falls dieses Feld existiert.
                    $DB->insert_record('block_dukreminder_mailssent', ['userid' => $user->id, 'reminderid' => $entry->id, 'timesent' => time()]);
                }

                $event = mail_sent::create([
                        'objectid' => $creator->id,
                        'context' => $coursecontext,
                        'other' => ['message' => 'student was notified'],
                        'relateduserid' => $user->id
                ]);
                $event->trigger();
                mtrace("a reminder mail was sent to student $user->id for $entry->subject");

                // Check for user manager and save information for later notifications.
                if ($entry->to_reportsuperior) {
                    $usermanager = block_dukreminder_get_manager($user);
                    if ($usermanager) {
                        if (!isset($managers[$usermanager->id])) {
                            $managers[$usermanager->id] = $usermanager;
                        }
                        if (!isset($managers[$usermanager->id]->users)) {
                            $managers[$usermanager->id]->users = [];
                        }
                        $managers[$usermanager->id]->users[] = $user;
                    }
                }
            }

            $mailtext = block_dukreminder_get_mail_text($course->fullname, $users, $entry->text_teacher);
            $subject = "Reminder report: " . $entry->subject;

            if ($entry->to_reporttrainer && $mailssent > 0) {
                $teachers = block_dukreminder_get_course_teachers($coursecontext);
                foreach ($teachers as $teacher) {
                    email_to_user($teacher, $creator, $subject, strip_tags($mailtext), $mailtext);

                    $event = mail_sent::create([
                            'objectid' => $creator->id,
                            'context' => $coursecontext,
                            'other' => ['message' => 'teacher was notified'],
                            'relateduserid' => $teacher->id
                    ]);
                    $event->trigger();
                    mtrace("a report mail was sent to teacher $teacher->id");
                }
            }

            // Additional recipients.
            if ($entry->to_mail && $mailssent > 0) {
                $addresses = explode(';', $entry->to_mail);
                $dummyuser = \core_user::get_noreply_user();

                foreach ($addresses as $address) {
                    $address = trim($address);
                    if (!empty($address)) {
                        $dummyuser->email = $address;
                        email_to_user($dummyuser, $creator, $subject, strip_tags($mailtext), $mailtext);

                        $event = mail_sent::create([
                                'objectid' => $creator->id,
                                'context' => $coursecontext,
                                'other' => ['message' => 'additional user was notified', 'email' => $address],
                                'relateduserid' => $dummyuser->id
                        ]);
                        $event->trigger();
                        mtrace("a report mail was sent to $address");
                    }
                }
            }

            // Managers.
            if ($entry->to_reportsuperior && $mailssent > 0) {
                foreach ($managers as $manager) {
                    $mailtext = block_dukreminder_get_mail_text($course->fullname, $manager->users, $entry->text_teacher);
                    email_to_user($manager, $creator, $subject, strip_tags($mailtext), $mailtext);

                    $event = mail_sent::create([
                            'objectid' => $creator->id,
                            'context' => $coursecontext,
                            'other' => ['message' => 'manager was notified'],
                            'relateduserid' => $manager->id
                    ]);
                    $event->trigger();
                    mtrace("a report mail was sent to manager $manager->id");
                }
            }

            // Set sentmails.
            $entry->mailssent += $mailssent;
            // Set sent.
            $entry->sent = 1;

            $DB->update_record('block_dukreminder', $entry);
        }
        return true;
    }
}