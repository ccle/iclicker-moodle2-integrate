<?php 
/* $Id $ */

require_once ('../../config.php');
global $CFG,$USER,$SITE;

$courseid = required_param('courseid', PARAM_INT);
$id = optional_param('id', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);

if (!$course = get_record('course', 'id', $courseid)) {
    error(get_string('invalidcourse', 'block_iclicker').$courseid);
}

require_login($course);
//require_capability('block/iclicker:managepages', get_context_instance(CONTEXT_COURSE, $courseid));

if (!$iclickerpage = get_record('iclicker', 'id', $id)) {
    error(get_string('nopage', 'block_iclicker', $id));
}

$site = get_site();
print_header(strip_tags($site->fullname), $site->fullname, 
	'<a href="'.$CFG->wwwroot.'/course/view.php?id='.$courseid.'">'.$course->shortname.'</a> ->'.$iclickerpage->pagetitle, '', 
	'<meta name="description" content="'.s(strip_tags($site->summary)).'">', true, '', '');

if (!$confirm) {
    $optionsno = array(
        'id'=>$courseid
    );
    $optionsyes = array(
        'id'=>$id, 
		'courseid'=>$courseid, 
		'confirm'=>1, 
		'sesskey'=>sesskey()
    );
    print_heading(get_string('confirmdelete', 'block_iclicker'));
    notice_yesno(get_string('deletepage', 'block_iclicker', $iclickerpage->pagetitle), 'delete.php', 
		$CFG->wwwroot.'/course/view.php', $optionsyes, $optionsno, 'post', 'get');
} else {
    if (confirm_sesskey()) {
        if (!delete_records('iclicker', 'id', $id)) {
            error('deleterror', 'block_iclicker');
        }
    } else {
        error('sessionerror', 'block_iclicker');
    }
    add_to_log($id, 'block_iclicker', 'delete page', $CFG->wwwroot.'course/view.php?&id='.$courseid, '', $id, $USER->id);
    redirect("$CFG->wwwroot/course/view.php?id=$courseid");
}

print_footer();
?>
