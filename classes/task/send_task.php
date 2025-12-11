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

class send_task extends \core\task\scheduled_task {
    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('send_task', 'block_dukreminder');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        require_once(dirname(__FILE__) . "/../../inc.php");

        global $DB;

        $entries = block_dukreminder_get_pending_reminders();

        foreach ($entries as $entry) {
            $mailssent = 0;
            $creator = $DB->get_record('user', array('id' => $entry->createdby));
            $course = $DB->get_record('course', array('id' => $entry->courseid));
            $coursecontext = \context_course::instance($course->id);

            $users = block_dukreminder_filter_users($entry);
            $managers = array();

            // Go through users and send mails AND save the user managers.
            foreach ($users as $user) {
                $user->mailformat = FORMAT_HTML;

                $mailtext = block_dukreminder_replace_placeholders($entry->text, $course->fullname, fullname($user), $user->email);
                email_to_user($user, $creator, $entry->subject, strip_tags($mailtext), $mailtext);
                $mailssent++;

                if ($entry->daterelative > 0) {
                    $DB->insert_record('block_dukreminder_mailssent', array('userid' => $user->id, 'reminderid' => $entry->id));
                }

                $event = \block_dukreminder\event\send_mail::create(array(
                        'objectid' => $creator->id,
                        'context' => $coursecontext,
                        'other' => array('message' => 'student was notified'),
                        'relateduserid' => $user->id
                ));
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
                            $managers[$usermanager->id]->users = array();
                        }
                        $managers[$usermanager->id]->users[] = $user;
                    }
                }
            }

            $mailtext = block_dukreminder_get_mail_text($course->fullname, $users, $entry->text_teacher);

            if ($entry->to_reporttrainer && $mailssent > 0) {
                // Get course teachers and send mails.
                $teachers = block_dukreminder_get_course_teachers($coursecontext);
                foreach ($teachers as $teacher) {
                    email_to_user($teacher, $creator, $entry->subject, strip_tags($mailtext), $mailtext);

                    $event = \block_dukreminder\event\send_mail::create(array(
                            'objectid' => $creator->id,
                            'context' => $coursecontext,
                            'other' => array('message' => 'teacher was notified'),
                            'relateduserid' => $teacher->id
                    ));
                    $event->trigger();
                    mtrace("a report mail was sent to teacher $teacher->id");
                }
            }

            // Additional recipients.
            if ($entry->to_mail && $mailssent > 0) {
                $addresses = explode(';', $entry->to_mail);
                $dummyuser = $DB->get_record('user', array('id' => BLOCK_DUKREMINDER_EMAIL_DUMMY));

                foreach ($addresses as $address) {
                    $address = trim($address);
                    if (!empty($address)) {
                        $dummyuser->email = $address;
                        email_to_user($dummyuser, $creator, $entry->subject, strip_tags($mailtext), $mailtext);

                        $event = \block_dukreminder\event\send_mail::create(array(
                                'objectid' => $creator->id,
                                'context' => $coursecontext,
                                'other' => array('message' => 'additional user was notified', 'email' => $address),
                                'relateduserid' => $dummyuser->id
                        ));
                        $event->trigger();
                        mtrace("a report mail was sent to $address");
                    }
                }
            }

            // Managers.
            if ($entry->to_reportsuperior && $mailssent > 0) {
                foreach ($managers as $manager) {
                    $mailtext = block_dukreminder_get_mail_text($course->fullname, $manager->users, $entry->text_teacher);
                    email_to_user($manager, $creator, get_string('pluginname', 'block_dukreminder'), strip_tags($mailtext),
                            $mailtext);

                    $event = \block_dukreminder\event\send_mail::create(array(
                            'objectid' => $creator->id,
                            'context' => $coursecontext,
                            'other' => array('message' => 'manager was notified'),
                            'relateduserid' => $manager->id
                    ));
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