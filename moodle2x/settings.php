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
 /* $Id: settings.php 1169 2011-12-23 17:17:31Z aaronz $ */

// control the config settings for this plugin
require_once ('bfwpub_service.php');
$block_name = bfwpub_service::BLOCK_NAME;
$settings->add(
    new admin_setting_configtext('block_bfwpub_shared_key',
        get_string('config_shared_key', $block_name),
        get_string('config_shared_key_desc', $block_name),
        '', //50,200
        PARAM_TEXT,
        50
    )
);
?>
