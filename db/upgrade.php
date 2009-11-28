<?php
/* $Id $ */
/*
 * This file keeps track of upgrades to this block
 *
 * Sometimes, changes between versions involve
 * alterations to database structures and other
 * major things that may break installations.
 *
 * The upgrade function in this file will attempt
 * to perform all the necessary actions to upgrade
 * your older installation to the current version.
 *
 * If there's something it cannot do itself, it
 * will tell you what you need to do.
 *
 * The commands in here will all be database-neutral,
 * using the functions defined in lib/ddllib.php
 */
function xmldb_block_simplehtml_upgrade($oldversion = 0) {
    global $CFG,$THEME,$db;
    $result = true;
    /* 
     * And upgrade begins here. For each one, you'll need one
     * block of code similar to the next one.
     */
    if ($result && $oldversion < 2009112600) {
        // Define index index_blockid (not unique) to be added to block_simplehtml
        $table = new XMLDBTable('simplehtml');
        $index = new XMLDBIndex('index_blockid');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array(
            'blockid'
        	)
		);
        // Launch add index index_blockid
        $result = $result && add_index($table, $index);
    }

    if ($result && $oldversion < 2009112700) {
        $table = new XMLDBTable('simplehtml');

	    /// Define field animal to be added to simplehtml
        $field = new XMLDBField('animal');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'displaydate');

	    /// Launch add field animal
        $result = $result && add_field($table, $field);        

        $field = new XMLDBField('animal_location');
        $field->setAttributes(XMLDB_TYPE_TEXT, 'small', null, null, null, null, null, null, 'animal');
        
        /// Launch add field animal_location
        $result = $result && add_field($table, $field);
    }

    return $result;
}
?>
