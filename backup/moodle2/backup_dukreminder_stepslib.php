<?php

// https://docs.moodle.org/dev/Backup_2.0_for_developers#How_to_backup_one_module



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
 * Defines backup_dukreminder_block class libs
 *
 * @package     block_dukreminder
 * @category    backup
 * @copyright   2018 michaelpollak {@link http://michaelpollak.org}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the backup steps that will be used by the backup_dukreminder_block_task
 */

/**
 * Define the complete dukreminder structure for backup, with file and id annotations
 */
class backup_dukreminder_block_structure_step extends backup_block_structure_step {

    protected function define_structure() {
        global $DB;
        // Define each element separated        
        $dukreminder = new backup_nested_element('dukreminder', array('id'), array(
                        'courseid', 'title', 'subject', 'text', 'dateabsolute',
                        'daterelative', 'mailssent', 'timecreated',
                        'timemodified', 'createdby', 'modifiedby', 'to_status',
                        'to_reporttrainer', 'to_reportsuperior', 'to_reportdirector', 'to_groups',
                        'to_mail', 'sent', 'text_teacher', 'criteria', 'status'));
        
        $mailssents = new backup_nested_element('mailssents');
        $mailssent = new backup_nested_element('mailssent', array('id'), array(
                        'userid', 'timesent'));

        // Build the tree
        $dukreminder->add_child($mailssents);
        $mailssents->add_child($mailssent);
        
        
        // Define sources
        $dukreminder->set_source_table('block_dukreminder', array('courseid' => backup::VAR_COURSEID));
        $mailssent->set_source_table('block_dukreminder_mailssent', array('reminderid' => backup::VAR_PARENTID));

        // Define id annotations
        //$dukreminder->annotate_ids('group', 'to_group');
        $mailssent->annotate_ids('user', 'userid');
        $dukreminder->annotate_ids('user', 'createdby');
        $dukreminder->annotate_ids('user', 'modifiedby');
        $dukreminder->annotate_ids('user', 'to_reporttrainer');
        $dukreminder->annotate_ids('user', 'to_reportsuperior');
        $dukreminder->annotate_ids('user', 'to_reportdirector');
      
        
        // Define file annotations
        // No files in use.
        
        // Return the root element (dukreminder), wrapped into standard block structure
        return $this->prepare_block_structure($dukreminder);
    }
}
