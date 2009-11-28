<?php 
/* $Id $ */
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
