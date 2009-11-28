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

require_once ($CFG->libdir.'/formslib.php');
require_once ($CFG->dirroot.'/blocks/iclicker/lib.php');

class iclicker_form extends moodleform {

    function definition() {
		global $CFG, $USER, $COURSE;
        
        $mform = &$this->_form;

        $mform->addElement('header', 'displayinfo', get_string('textfields', 'block_iclicker'));
        
        // add page title element
        $mform->addElement('text', 'pagetitle', get_string('pagetitle', 'block_iclicker'));
        $mform->addRule('pagetitle', null, 'required', null, 'client');
        
        // add display text field
        $mform->addElement('htmleditor', 'displaytext', get_string('displayedhtml', 'block_iclicker'));
        $mform->setType('displaytexttext', PARAM_RAW);
        $mform->addRule('displaytext', null, 'required', null, 'client');
        
        // add filename selection
        $mform->addElement('choosecoursefile', 'filename', get_string('displayfile', 'block_iclicker'), array(
            'courseid'=>$COURSE->id
        ));
        
        //add picturefields header
        $mform->addElement('header', 'pictureinfo', get_string('picturefields', 'block_iclicker'));
        
        // add display picture yes/no option
        $mform->addElement('selectyesno', 'displaypicture', get_string('displaypicture', 'block_iclicker'));
        $mform->setDefault('displaypicture', 1);
        
        // add image selector radio buttons
        $images = block_iclicker_images();
        $radioarray = array();
        for ($i = 0; $i < count($images); $i++) {
            $radioarray[] = &$mform->createElement('radio', 'picture', '', $images[$i], $i);
        }
        
        $mform->addGroup($radioarray, 'radioar', 
			get_string('pictureselect', 'block_iclicker'), 
			array(' '), false);

        // add description field
        $attributes = array(
            'size'=>'50', 'maxlength'=>'100'
        );
        $mform->addElement('text', 'description', get_string('picturedesc', 'block_iclicker'), $attributes);
        $mform->setType('description', PARAM_TEXT);

        // add animal grouping
        $mform->addElement('header', 'animal_group', get_string('animal_group', 'block_iclicker'), null, false);

		// radio buttons
        $animals = block_iclicker_animals();
        $animalsRadio = array();
        for ($i = 0; $i < count($animals); $i++) {
            $animalsRadio[] = &MoodleQuickForm::createElement('radio', 'animal', '', $animals[$i], $i);
        }
        $mform->addGroup($animalsRadio, 
			'animalsRadio', 
			get_string('animals', 'block_iclicker'), 
			array(' '), 
			false
		);
        $mform->setType('animal', PARAM_TEXT);

		// normal text box
        $mform->addElement('text', 'animal_location', get_string('animals_location', 'block_iclicker'), 'size="80"');
        $mform->setType('animal_location', PARAM_TEXT);
        
        
        // add optional grouping
        $mform->addElement('header', 'optional', get_string('optional', 'form'), null, false);

        // add date_time selector in optional area
        $mform->addElement('date_time_selector', 'displaydate', get_string('displaydate', 'block_iclicker'), array(
            'optional'=>true
        	)
		);
        $mform->setAdvanced('optional');

		/*
		 * These hidden elements will need to be populated by displaying code because these will be passed in via the URL initially.
		 * Before the display function in view.php (or whatever) add:
		 * $toform['blockid'] = $blockid;
		 * $toform['courseid'] = $courseid;
		 * $iclicker->set_data($toform);
		 */ 
        $mform->addElement('hidden','blockid');
        $mform->addElement('hidden','courseid');
        $mform->addElement('hidden','id','0');

		$this->add_action_buttons();        
    }
    
}
?>
