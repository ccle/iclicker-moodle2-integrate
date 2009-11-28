<?php 
/* $Id $ */
/**
 * This displays the page data
 */

require_once ('../../config.php');
global $CFG, $USER, $COURSE;
require_once ($CFG->dirroot.'/lib/filelib.php');
require_once ($CFG->dirroot.'/blocks/simplehtml/lib.php');

$courseid = required_param('courseid', PARAM_INT);
$id = required_param('id', PARAM_INT);

if (!$course = get_record('course', 'id', $courseid)) {
    error(get_string('invalidcourse', 'block_simplehtml').$courseid);
}

require_login($course);

// ensure the user has appropriate permissions to access this area
require_capability('block/simplehtml:viewpages', get_context_instance(CONTEXT_COURSE, $courseid));

// ensure we have a valid simplehtml page id and can load the associated page
if(!$simplehtml = get_record('simplehtml', 'id', $id)){
    error(get_string('nopage','block_simplehtml', $id));
}

// print the header and associated data
$site = get_site();
print_header(strip_tags($site->fullname), $site->fullname,
    '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$courseid.'">'.$course->shortname.
    '</a> ->'.$simplehtml->pagetitle, '',
    '<meta name="description" content="'. s(strip_tags($site->summary)) .'">', true, '', '');

// display page information
include_once($CFG->dirroot.'/lib/filelib.php');
$output = print_heading($simplehtml->pagetitle, 'center', 2, 'main', TRUE);

if ($simplehtml->displaydate) {
    $output .= '<div class="simplehtml displaydate">'.userdate($simplehtml->displaydate).'</div>';
}

$output .= print_box_start('generalbox', '', TRUE);
$output .= clean_text($simplehtml->displaytext);
$fileurl = get_file_url($COURSE->id.'/'.$simplehtml->filename);
$output .= '<br /><a href="'.$fileurl.'">'.get_string('viewfile', 'block_simplehtml').'</a>';
$output .= print_box_end(TRUE);

if ($simplehtml->displaypicture) {
    $images = block_simplehtml_images();
    $output .= print_box_start('generalbox', '', TRUE);
    $output .= $images[$simplehtml->picture];
    $output .= ' '.$simplehtml->description;
    $output .= print_box_end(TRUE);        
}

$animals = block_simplehtml_animals();
$output .= print_box_start('generalbox', '', TRUE);
$output .= $animals[$simplehtml->animal];
$output .= ': <i>'.$simplehtml->animal_location.'</i>';
$output .= print_box_end(TRUE);        

print $output;

// print the footer
print_footer();

?>
