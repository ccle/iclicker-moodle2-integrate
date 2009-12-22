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
/* $Id: rest.php 9 2009-11-28 17:10:13Z azeckoski $ */

// this includes lib/setup.php and the standard set:
//setup.php : setup which creates the globals
//'/textlib.class.php');   // Functions to handle multibyte strings
//'/weblib.php');          // Functions for producing HTML
//'/dmllib.php');          // Functions to handle DB data (DML) - inserting, updating, and retrieving data from the database
//'/datalib.php');         // Legacy lib with a big-mix of functions. - user, course, etc. data lookup functions
//'/accesslib.php');       // Access control functions - context, roles, and permission related functions
//'/deprecatedlib.php');   // Deprecated functions included for backward compatibility
//'/moodlelib.php');       // general-purpose (login, getparams, getconfig, cache, data/time)
//'/eventslib.php');       // Events functions
//'/grouplib.php');        // Groups functions

//ddlib.php : modifying, creating, or deleting database schema
//blocklib.php : functions to use blocks in a typical course page
//formslib.php : classes for creating forms in Moodle, based on PEAR QuickForms

require_once('../../config.php'); //$CFG->dirroot.'/config.php');
global $USER, $COURSE, $CFG;
//require_once('../../lib/datalib.php'); //$CFG->libdir .'/datalib.php');

require_login();

echo "me=".me().", qualified=".qualified_me();
echo "user: id=".$USER->id.", auth=".$USER->auth.", username=".$USER->username.", lastlogin=".$USER->lastlogin."\n";
echo "course: id=".$COURSE->id.", title=".$COURSE->fullname."\n";
echo "CFG: wwwroot=".$CFG->wwwroot.", httpswwwroot=".$CFG->httpswwwroot.", dirroot=".$CFG->dirroot.", libdir=".$CFG->libdir."\n";

// getting some courses
$query = "SELECT id, fullname, shortname FROM mdl_course";
/* where "
	."startdate > unix_timestamp('20071201 00:00:00') and "
	."startdate < unix_timestamp('20061001 00:00:00')";*/
$rs = get_records_sql($query);
?>
<h3><?php echo get_string('courses') ?></h3>
<table>
	<thead>
		<tr>
			<th><?php echo get_string('id') ?></th>
			<th><?php echo get_string('shortname') ?></th>
			<th><?php echo get_string('fullname') ?></th>
		</tr>
	</thead>
	<tbody>
<?php	if ($rs) {
			foreach ($rs as $course) {
?>
		<tr>
			<td><?php echo $course->id ?></td>
			<td><?php echo $course->shortname ?></td>
			<td><?php echo $course->fullname ?></td>
		</tr>
<?php 		}
		} else {
?>
		<tr>
			<td colspan='3'><?php echo get_string('No courses found') ?></td>
		</tr>
<?php 	} ?>
	</tbody>
</table>
