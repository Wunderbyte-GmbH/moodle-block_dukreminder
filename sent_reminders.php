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
 * sent_reminders.php
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

// Check and alter SQL if renderid is set.
$reminderid = "%";
if ((optional_param('reminderid', 0, PARAM_INT)) > 0) {
    $reminderid = optional_param('reminderid', 0, PARAM_INT);
}

$pageidentifier = 'tab_sent_reminders';

$PAGE->set_url('/blocks/dukreminder/sent_reminders.php', array('courseid' => $courseid));
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

$table = new html_table();

$table->head = array(
                //html_writer::link($PAGE->url . "&sorting=title", get_string('form_title', 'block_dukreminder')),
                'id',
                'reminderid',
                'userid',
                'timesent');
                
                // Fetch all sent mails.    
                $data = $DB->get_records_sql("SELECT {block_dukreminder_mailssent}.id, reminderid, userid, timesent 
FROM {block_dukreminder_mailssent} 
JOIN {block_dukreminder} ON {block_dukreminder_mailssent}.reminderid={block_dukreminder}.id 
WHERE courseid = ? 
AND reminderid LIKE ?", array($courseid, $reminderid));
                        foreach ($data as $record) {
                            
                        }
                        $table->data = $data;
                        echo html_writer::table($table);
                        
                        /* END CONTENT REGION */
                        
                        echo $OUTPUT->footer();
                        
