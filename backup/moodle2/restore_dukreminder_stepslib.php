<?php

// https://docs.moodle.org/dev/Restore_2.0_for_developers#Introduction

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
 * @package     block_dukreminder
 * @category    backup
 * @copyright   2018 michaelpollak {@link http://michaelpollak.org}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_dukreminder block
 */

/**
 * Structure step to restore one dukreminder block
 */
class restore_dukreminder_block_structure_step extends restore_structure_step {

    protected function define_structure() {

        $paths = array();
        $paths[] = new restore_path_element('dukreminder', '/block/dukreminder');
        // Simplified that, I think the logs of sent mails are not needed.
        //$paths[] = new restore_path_element('dukreminder_mailssent', '/block/dukreminder/mailssents/mailssent');
        //print_r($paths);
        // Return the paths wrapped into standard activity structure
        return $paths;
    }

    protected function process_dukreminder($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->courseid = $this->get_courseid(); // Courseid needs to be updated.

        // insert the dukreminder record
        // DEBUG: var_dump($data);
        $newitemid = $DB->insert_record('block_dukreminder', $data);
        
    }
    
    /*
    protected function process_dukreminder_mailssent($data) {
        global $DB;
        print_r("this gets triggered obviously.");
        $data = (object)$data;
        $oldid = $data->id;
        
        $data->reminderid = $this->get_new_parentid('dukreminder');
        var_dump($data);
        exit;
        $newitemid = $DB->insert_record('block_dukreminder_mailssent', $data);
        $this->set_mapping('dukreminder_mailssent', $oldid, $newitemid);

    }
    */
    


}
