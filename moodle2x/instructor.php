<?php
/**
 * Copyright (c) 2012 i>clicker (R) <http://www.iclicker.com/dnn/>
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
global $CFG, $USER, $COURSE, $OUTPUT, $PAGE;
require_once ('iclicker_service.php');
require_once ('controller.php');

$site = get_site();
require_login($site);

// activate the controller
$cntlr = new iclicker_controller();
$cntlr->processInstructor();
extract($cntlr->results);

/* TODO
$navigation = iclicker_service::msg('inst.title');
if ($show_students && $course) {
    $navigation = array(
        array('name' => iclicker_service::msg('inst.title'), 'link' => $instPath),
        array('name' => $course->fullname)
    );
}
*/

// begin rendering
$PAGE->set_title( strip_tags($site->fullname).':'.iclicker_service::msg('app.iclicker').':'.iclicker_service::msg('inst.title') );
$PAGE->set_heading( iclicker_service::msg('app.iclicker').' '.iclicker_service::msg('inst.title') );
$PAGE->navbar->add(iclicker_service::msg('inst.title'));
$PAGE->set_focuscontrol('');
$PAGE->set_cacheable(false);
$PAGE->requires->css(iclicker_service::BLOCK_PATH.'/css/iclicker.css');
$PAGE->set_url(iclicker_service::BLOCK_PATH.'/registration.php');
//$PAGE->requires->js('mod/mymod/styles.css');
echo $OUTPUT->header();

// show messages if there are any to show
require ('user_messages.php');
?>

<div class="main_content">
<?php if (count($courses) == 0) { ?>
    <span class="no_items"><?php echo iclicker_service::msg('inst.no.courses') ?></span>
<?php } else if ($show_students) { ?>
    <div class="title"><?php echo iclicker_service::msg('inst.course') ?>: <?php echo $course->fullname ?></div>
    <div class="description"><?php echo $course->summary ?></div>
    <!-- clicker registration listing -->
    <div><?php echo iclicker_service::msg('inst.students') ?> (<?php echo $students_count ?>):</div>
    <table width="80%" border="1" cellspacing="0" cellpadding="0" class="students_list"
        summary="<?php echo iclicker_service::msg('inst.students.table.summary') ?>">
        <thead>
            <tr class="students_header header_row">
                <th width="40%" scope="col" height="25" valign="middle" bgcolor="#e8e8e8" class="style5">
                    <?php echo iclicker_service::msg('inst.student.name.header') ?>
                </th>
                <th width="30%" scope="col" height="25" valign="middle" bgcolor="#e8e8e8" class="style5">
                    <?php echo iclicker_service::msg('inst.student.email.header') ?>
                </th>
                <th width="30%" scope="col" height="25" valign="middle" bgcolor="#e8e8e8" class="style5">
                    <?php echo iclicker_service::msg('inst.student.status.header') ?>
                </th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($students as $student) { ?>
            <tr class="<?php echo ($student->clicker_registered ? 'registered' : 'unregistered') ?> students_row data_row style1">
                <td align="center" class="user_name"><?php echo $student->name ?></td>
                <td align="center" class="user_email"><?php echo $student->email ?></td>
                <td align="center" class="clicker_status"><?php echo iclicker_service::msg('inst.student.registered.'.($student->clicker_registered ? 'true':'false')) ?></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
<?php } else { ?>
    <div class="title"><?php echo iclicker_service::msg('inst.courses.header') ?> (<?php echo $courses_count ?>):</div>
    <!-- course listing -->
    <table width="90%" border="1" cellspacing="0" cellpadding="0"
        summary="<?php echo iclicker_service::msg('inst.courses.table.summary') ?>">
        <thead>
            <tr class="courses_header header_row">
                <th width="70%" scope="col" height="25" valign="middle" bgcolor="#e8e8e8" class="style5">
                    <?php echo iclicker_service::msg('inst.course') ?>
                </th>
                <th width="30%" scope="col" height="25" valign="middle" bgcolor="#e8e8e8" class="style5"></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($courses as $course) { ?>
            <tr class="courses_row data_row style1">
                <td align="center"><?php echo $course->fullname ?></td>
                <td align="center"><a href="<?php echo $instPath.'?courseId='.$course->id ?>"><?php echo iclicker_service::msg('inst.course.view.students') ?></a></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
<?php } ?>
</div>

<?php echo $OUTPUT->footer(); ?>
