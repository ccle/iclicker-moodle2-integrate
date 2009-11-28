<?php
/**
 * Copyright (c) 2009 i>clicker (R) <http://www.iclicker.com/dnn/>
 *
 * This file is part of i>clicker Moodle integrate.
 *
 * i>clicker Moodle integrate is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * i>clicker Moodle integrate is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with i>clicker Moodle integrate.  If not, see <http://www.gnu.org/licenses/>.
 */
/* $Id$ */

/**
 * Handles rendering the form for creating new pages and the submission of the form as well
 * NOTE: table is named iclicker
 */
 
require_once ('../../config.php');
global $CFG, $USER, $COURSE;
require_once ('iclicker_form.php');

$courseid = required_param('courseid', PARAM_INT);
$blockid = required_param('blockid', PARAM_INT);
$id = optional_param('id', 0, PARAM_INT);

if (!$course = get_record('course', 'id', $courseid)) {
    error(get_string('invalidcourse', 'block_iclicker').$courseid);
}

require_login($course);
//require_capability('block/iclicker:managepages', get_context_instance(CONTEXT_COURSE, $courseid));

$iclicker = new iclicker_form();
if ($iclicker->is_cancelled()) {
    // cancelled forms redirect to course main page
    redirect("$CFG->wwwroot/course/view.php?id=$id");
} else if ($fromform = $iclicker->get_data()) {
	//print_object($fromform); // for testing
    // we need to add code to appropriately act on and store the submitted data
    if (isset($fromform->id) && $fromform->id != 0) {
        if (! update_record('iclicker', $fromform)) {
        	print_object($fromform);
            error(get_string('updateerror', 'block_iclicker'));
        }
        add_to_log($blockid, 'block_iclicker', 'update page', $CFG->wwwroot.'/blocks/iclicker/page.php?blockid='.$blockid.'&courseid'.$courseid, '', $blockid, $USER->id);
    } else {
        if (! insert_record('iclicker', $fromform)) {
        	print_object($fromform);
            error(get_string('inserterror', 'block_iclicker'));
        }
        add_to_log($id, 'block_iclicker', 'insert page', $CFG->wwwroot.'/blocks/iclicker/page.php?courseid='.$courseid.'&blockid='.$blockid, '', 0, $USER->id);
    }
	// done with the handling so go to the view
    redirect("$CFG->wwwroot/course/view.php?id=$courseid");
} else {
    //form didn't validate or this is the first display
    if ($id != 0) {
        if (! $toform = get_record('iclicker', 'id', $id)) {
            error(get_string('nopage', 'block_iclicker', $id));
        }
		print_object($toform);
    } else {
        $toform = new stdClass;
    }
    $toform->blockid = $blockid;
    $toform->courseid = $courseid;
    
    $site = get_site();
    print_header(strip_tags($site->fullname), $site->fullname, '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$courseid.'">'.$course->shortname.'</a> ->'.get_string('formtitle', 'block_iclicker'), '', '<meta name="description" content="'.s(strip_tags($site->summary)).'">', true, '', '');
    
    $iclicker->set_data($toform);
    
    $iclicker->display();
    print_footer();
}

?>
