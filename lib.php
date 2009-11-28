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
 * Library functions for the iclicker block
 */

function block_iclicker_images() {
    global $CFG;
    return array(
        '<img src="'.$CFG->wwwroot.'/blocks/iclicker/img/picture0.gif" alt="'.get_string('red', 'block_iclicker').'">', 
		'<img src="'.$CFG->wwwroot.'/blocks/iclicker/img/picture1.gif" alt="'.get_string('blue', 'block_iclicker').'">', 
		'<img src="'.$CFG->wwwroot.'/blocks/iclicker/img/picture2.gif" alt="'.get_string('green', 'block_iclicker').'">'
    );
}

function block_iclicker_animals() {
    return array(
		get_string('animal0', 'block_iclicker'),
		get_string('animal1', 'block_iclicker'),
		get_string('animal2', 'block_iclicker'),
		get_string('animal3', 'block_iclicker'),
		get_string('animal4', 'block_iclicker')
    );
}

?>
