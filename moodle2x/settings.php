<?php
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
