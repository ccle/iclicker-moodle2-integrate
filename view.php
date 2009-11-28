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
 * This displays the page data
 */

require_once ('../../config.php');
global $CFG, $USER, $COURSE;
require_once ($CFG->dirroot.'/lib/filelib.php');
require_once ($CFG->dirroot.'/blocks/iclicker/lib.php');

$courseid = required_param('courseid', PARAM_INT);
$id = required_param('id', PARAM_INT);

if (!$course = get_record('course', 'id', $courseid)) {
    error(get_string('invalidcourse', 'block_iclicker').$courseid);
}

require_login($course);

// ensure the user has appropriate permissions to access this area
require_capability('block/iclicker:viewpages', get_context_instance(CONTEXT_COURSE, $courseid));

// ensure we have a valid iclicker page id and can load the associated page
if(!$iclicker = get_record('iclicker', 'id', $id)){
    error(get_string('nopage','block_iclicker', $id));
}

// print the header and associated data
$site = get_site();
print_header(strip_tags($site->fullname), $site->fullname,
    '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$courseid.'">'.$course->shortname.
    '</a> ->'.$iclicker->pagetitle, '',
    '<meta name="description" content="'. s(strip_tags($site->summary)) .'">', true, '', '');

// display page information
include_once($CFG->dirroot.'/lib/filelib.php');
$output = print_heading($iclicker->pagetitle, 'center', 2, 'main', TRUE);

if ($iclicker->displaydate) {
    $output .= '<div class="iclicker displaydate">'.userdate($iclicker->displaydate).'</div>';
}

$output .= print_box_start('generalbox', '', TRUE);
$output .= clean_text($iclicker->displaytext);
$fileurl = get_file_url($COURSE->id.'/'.$iclicker->filename);
$output .= '<br /><a href="'.$fileurl.'">'.get_string('viewfile', 'block_iclicker').'</a>';
$output .= print_box_end(TRUE);

if ($iclicker->displaypicture) {
    $images = block_iclicker_images();
    $output .= print_box_start('generalbox', '', TRUE);
    $output .= $images[$iclicker->picture];
    $output .= ' '.$iclicker->description;
    $output .= print_box_end(TRUE);        
}

$animals = block_iclicker_animals();
$output .= print_box_start('generalbox', '', TRUE);
$output .= $animals[$iclicker->animal];
$output .= ': <i>'.$iclicker->animal_location.'</i>';
$output .= print_box_end(TRUE);        

print $output;

// print the footer
print_footer();

?>
