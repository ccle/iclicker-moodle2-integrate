<?php 
/* $Id $ */
/**
 * Library functions for the simplehtml block
 */

function block_simplehtml_images() {
    global $CFG;
    return array(
        '<img src="'.$CFG->wwwroot.'/blocks/simplehtml/img/picture0.gif" alt="'.get_string('red', 'block_simplehtml').'">', 
		'<img src="'.$CFG->wwwroot.'/blocks/simplehtml/img/picture1.gif" alt="'.get_string('blue', 'block_simplehtml').'">', 
		'<img src="'.$CFG->wwwroot.'/blocks/simplehtml/img/picture2.gif" alt="'.get_string('green', 'block_simplehtml').'">'
    );
}

function block_simplehtml_animals() {
    return array(
		get_string('animal0', 'block_simplehtml'),
		get_string('animal1', 'block_simplehtml'),
		get_string('animal2', 'block_simplehtml'),
		get_string('animal3', 'block_simplehtml'),
		get_string('animal4', 'block_simplehtml')
    );
}

?>
