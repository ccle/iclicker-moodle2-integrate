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
/* $Id: upgrade.php 107 2012-04-06 01:48:53Z azeckoski@gmail.com $ */

// This file keeps track of upgrades to this block
function xmldb_block_iclicker_upgrade($oldversion = 0) {
    global $CFG,$THEME,$DB;
    $dbman = $DB->get_manager(); /// loads ddl manager and xmldb classes
    /*
     * And upgrade begins here. For each one, you'll need one
     * block of code similar to the next one.
     */
    if ($oldversion < 2009112700) {
        $table = new xmldb_table('iclicker');
        // Add stuff here if needed
        //upgrade_mod_savepoint(true, 2009112700, 'iclicker');
    }
}
?>
