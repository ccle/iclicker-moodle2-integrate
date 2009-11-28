<?php 
/* $Id $ */

require_once ($CFG->libdir.'/formslib.php');
require_once ($CFG->dirroot.'/blocks/simplehtml/lib.php');

class simplehtml_form extends moodleform {

    function definition() {
		global $CFG, $USER, $COURSE;
        
        $mform = &$this->_form;

        $mform->addElement('header', 'displayinfo', get_string('textfields', 'block_simplehtml'));
        
        // add page title element
        $mform->addElement('text', 'pagetitle', get_string('pagetitle', 'block_simplehtml'));
        $mform->addRule('pagetitle', null, 'required', null, 'client');
        
        // add display text field
        $mform->addElement('htmleditor', 'displaytext', get_string('displayedhtml', 'block_simplehtml'));
        $mform->setType('displaytexttext', PARAM_RAW);
        $mform->addRule('displaytext', null, 'required', null, 'client');
        
        // add filename selection
        $mform->addElement('choosecoursefile', 'filename', get_string('displayfile', 'block_simplehtml'), array(
            'courseid'=>$COURSE->id
        ));
        
        //add picturefields header
        $mform->addElement('header', 'pictureinfo', get_string('picturefields', 'block_simplehtml'));
        
        // add display picture yes/no option
        $mform->addElement('selectyesno', 'displaypicture', get_string('displaypicture', 'block_simplehtml'));
        $mform->setDefault('displaypicture', 1);
        
        // add image selector radio buttons
        $images = block_simplehtml_images();
        $radioarray = array();
        for ($i = 0; $i < count($images); $i++) {
            $radioarray[] = &$mform->createElement('radio', 'picture', '', $images[$i], $i);
        }
        
        $mform->addGroup($radioarray, 'radioar', 
			get_string('pictureselect', 'block_simplehtml'), 
			array(' '), false);

        // add description field
        $attributes = array(
            'size'=>'50', 'maxlength'=>'100'
        );
        $mform->addElement('text', 'description', get_string('picturedesc', 'block_simplehtml'), $attributes);
        $mform->setType('description', PARAM_TEXT);

        // add animal grouping
        $mform->addElement('header', 'animal_group', get_string('animal_group', 'block_simplehtml'), null, false);

		// radio buttons
        $animals = block_simplehtml_animals();
        $animalsRadio = array();
        for ($i = 0; $i < count($animals); $i++) {
            $animalsRadio[] = &MoodleQuickForm::createElement('radio', 'animal', '', $animals[$i], $i);
        }
        $mform->addGroup($animalsRadio, 
			'animalsRadio', 
			get_string('animals', 'block_simplehtml'), 
			array(' '), 
			false
		);
        $mform->setType('animal', PARAM_TEXT);

		// normal text box
        $mform->addElement('text', 'animal_location', get_string('animals_location', 'block_simplehtml'), 'size="80"');
        $mform->setType('animal_location', PARAM_TEXT);
        
        
        // add optional grouping
        $mform->addElement('header', 'optional', get_string('optional', 'form'), null, false);

        // add date_time selector in optional area
        $mform->addElement('date_time_selector', 'displaydate', get_string('displaydate', 'block_simplehtml'), array(
            'optional'=>true
        	)
		);
        $mform->setAdvanced('optional');

		/*
		 * These hidden elements will need to be populated by displaying code because these will be passed in via the URL initially.
		 * Before the display function in view.php (or whatever) add:
		 * $toform['blockid'] = $blockid;
		 * $toform['courseid'] = $courseid;
		 * $simplehtml->set_data($toform);
		 */ 
        $mform->addElement('hidden','blockid');
        $mform->addElement('hidden','courseid');
        $mform->addElement('hidden','id','0');

		$this->add_action_buttons();        
    }
    
}
?>
