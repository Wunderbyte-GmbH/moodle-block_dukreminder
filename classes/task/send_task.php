<?php
namespace block_dukreminder\task;

class send_task extends \core\task\scheduled_task
{
    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name()
    {
        return get_string('send_task', 'block_dukreminder');
    }

    /**
     * Execute the task.
     */
    public function execute()
    {
        require_once(dirname(__FILE__) . "/../../inc.php");

        global $DB, $OUTPUT, $PAGE, $USER;

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
                };

                $event = \block_dukreminder\event\send_mail::create(array(
                    'objectid' => $creator->id,
                    'context' => $coursecontext,
                    'other' => 'student was notified',
                    'relateduserid' => $user->id
                ));
                $event->trigger();
                mtrace("a reminder mail was sent to student $user->id for $entry->subject");

                // Check for user manager and save information for later notifications.
                if ($entry->to_reportsuperior) {
                    $usermanager = block_dukreminder_get_manager($user);
                    if ($usermanager) {
                        if (!isset($managers[$usermanager->id])) {
                            $managers[$usermanager->id]->$usermanager;
                        };
                        $managers[$usermanager->id]->users[] = $user;
                    }
                }
            }

            $mailtext = block_dukreminder_get_mail_text($course->fullname, $users, $entry->text_teacher);

            if ($entry->to_reporttrainer && $mailssent > 0) {
            // Get course teachers and send mails, and additional mails.
                $teachers = block_dukreminder_get_course_teachers($coursecontext);
                foreach ($teachers as $teacher) {
                    email_to_user($teacher, $creator, $entry->subject, $mailtext); // Changed by G. Schwed (DUK).

                    $event = \block_dukreminder\event\send_mail::create(array(
                        'objectid' => $creator->id,
                        'context' => $coursecontext,
                        'other' => 'teacher was notified',
                        'relateduserid' => $teacher->id
                    ));
                    $event->trigger();
                    mtrace("a report mail was sent to teacher $teacher->id");
                }
            }
            // Sonstige Empfänger.
            if ($entry->to_mail && $mailssent > 0) {
                $addresses = explode(';', $entry->to_mail);
                $dummyuser = $DB->get_record('user', array('id' => BLOCK_DUKREMINDER_EMAIL_DUMMY));

                foreach ($addresses as $address) {
                    $dummyuser->email = $address;
                    email_to_user($teacher, $creator, $entry->subject, $mailtext); // Changed by G. Schwed (DUK).

                    $event = \block_dukreminder\event\send_mail::create(array(
                        'objectid' => $creator->id,
                        'context' => $coursecontext,
                        'other' => 'additional user was notified',
                        'relateduserid' => $dummyuser->id
                    ));
                    $event->trigger();
                    mtrace("a report mail was sent to $address");
                }
            }

            // Managers.
            if ($entry->to_reportsuperior && $mailssent > 0) {
                foreach ($managers as $manager) {
                    $mailtext = block_dukreminder_get_mail_text($course->fullname, $manager->users);
                    email_to_user($manager, $creator, get_string('pluginname', 'block_dukreminder'), $mailtext);

                    $event = \block_dukreminder\event\send_mail::create(array(
                        'objectid' => $creator->id,
                        'context' => $coursecontext,
                        'other' => 'manager was notified',
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