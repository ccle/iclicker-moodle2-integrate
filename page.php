<?php 
/* $Id $ */
/**
 * Handles rendering the form for creating new pages and the submission of the form as well
 * NOTE: table is named simplehtml
 */
 
require_once ('../../config.php');
global $CFG, $USER, $COURSE;
require_once ('simplehtml_form.php');

$courseid = required_param('courseid', PARAM_INT);
$blockid = required_param('blockid', PARAM_INT);
$id = optional_param('id', 0, PARAM_INT);

if (!$course = get_record('course', 'id', $courseid)) {
    error(get_string('invalidcourse', 'block_simplehtml').$courseid);
}

require_login($course);
//require_capability('block/simplehtml:managepages', get_context_instance(CONTEXT_COURSE, $courseid));

$simplehtml = new simplehtml_form();
if ($simplehtml->is_cancelled()) {
    // cancelled forms redirect to course main page
    redirect("$CFG->wwwroot/course/view.php?id=$id");
} else if ($fromform = $simplehtml->get_data()) {
	//print_object($fromform); // for testing
    // we need to add code to appropriately act on and store the submitted data
    if (isset($fromform->id) && $fromform->id != 0) {
        if (! update_record('simplehtml', $fromform)) {
        	print_object($fromform);
            error(get_string('updateerror', 'block_simplehtml'));
        }
        add_to_log($blockid, 'block_simplehtml', 'update page', $CFG->wwwroot.'/blocks/simplehtml/page.php?blockid='.$blockid.'&courseid'.$courseid, '', $blockid, $USER->id);
    } else {
        if (! insert_record('simplehtml', $fromform)) {
        	print_object($fromform);
            error(get_string('inserterror', 'block_simplehtml'));
        }
        add_to_log($id, 'block_simplehtml', 'insert page', $CFG->wwwroot.'/blocks/simplehtml/page.php?courseid='.$courseid.'&blockid='.$blockid, '', 0, $USER->id);
    }
	// done with the handling so go to the view
    redirect("$CFG->wwwroot/course/view.php?id=$courseid");
} else {
    //form didn't validate or this is the first display
    if ($id != 0) {
        if (! $toform = get_record('simplehtml', 'id', $id)) {
            error(get_string('nopage', 'block_simplehtml', $id));
        }
		print_object($toform);
    } else {
        $toform = new stdClass;
    }
    $toform->blockid = $blockid;
    $toform->courseid = $courseid;
    
    $site = get_site();
    print_header(strip_tags($site->fullname), $site->fullname, '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$courseid.'">'.$course->shortname.'</a> ->'.get_string('formtitle', 'block_simplehtml'), '', '<meta name="description" content="'.s(strip_tags($site->summary)).'">', true, '', '');
    
    $simplehtml->set_data($toform);
    
    $simplehtml->display();
    print_footer();
}

?>
