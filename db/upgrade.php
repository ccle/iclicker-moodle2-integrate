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
function xmldb_block_iclicker_upgrade($oldversion = 0) {
    global $CFG,$THEME,$db;
    $result = true;
    /* 
     * And upgrade begins here. For each one, you'll need one
     * block of code similar to the next one.
     */
    if ($result && $oldversion < 2009112700) {
        $table = new XMLDBTable('iclicker');
		// Add stuff here if needed
    }

    return $result;
}
?>
