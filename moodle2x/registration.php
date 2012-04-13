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
/* $Id$ */

/**
 * Handles rendering the form for creating new pages and the submission of the form as well
 */

require_once ('../../config.php');
global $CFG, $USER, $COURSE, $OUTPUT, $PAGE;
require_once ('iclicker_service.php');
require_once ('controller.php');

$site = get_site();
require_login($site);

// activate the controller
$cntlr = new iclicker_controller();
$cntlr->processRegistration();
extract($cntlr->results);

// begin rendering
$PAGE->set_title( strip_tags($site->fullname).':'.iclicker_service::msg('app.iclicker').':'.iclicker_service::msg('reg.title') );
$PAGE->set_heading( iclicker_service::msg('app.iclicker').' '.iclicker_service::msg('reg.title') );
$PAGE->navbar->add(iclicker_service::msg('reg.title'));
$PAGE->set_focuscontrol('');
$PAGE->set_cacheable(false);
$PAGE->requires->css(iclicker_service::BLOCK_PATH.'/css/iclicker.css');
$PAGE->set_url(iclicker_service::BLOCK_PATH.'/registration.php');
//$PAGE->requires->js('mod/mymod/styles.css');
echo $OUTPUT->header();

// show messages if there are any to show
require ('user_messages.php');
?>

<div class="main_content">
    <div style="float:left; width: 45%;">
        <!-- column one -->
        <!-- clicker registration listing -->
        <table width="100%" border="1" cellspacing="0" cellpadding="0"
            summary="<?php echo iclicker_service::msg('reg.registration.table.summary') ?>">
            <thead>
                <tr class="registration_row header_row">
                    <th width="30%" scope="col" height="25" valign="middle" bgcolor="#e8e8e8" class="style5">
                        <?php echo iclicker_service::msg('reg.remote.id.header') ?>
                    </th>
                    <th width="30%" scope="col" height="25" valign="middle" bgcolor="#e8e8e8" class="style5">
                        <?php echo iclicker_service::msg('reg.registered.date.header') ?>
                    </th>
                    <th width="40%" scope="col" height="25" valign="middle" bgcolor="#e8e8e8" class="style5">
                        <?php echo iclicker_service::msg('admin.controls.header') ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($regs as $reg) { ?>
                <tr class="registration_row data_row style1 <?php echo ($reg->activated ? '' : 'disabled') ?>">
                    <td class="clicker_id" align="center"><?php echo $reg->clicker_id ?></td>
                    <td class="date" align="center"><?php echo iclicker_service::df($reg->timecreated) ?></td>
                    <td class="controls" align="center">
                        <form method="post">
                            <input type="hidden" name="registrationId" value="<?php echo $reg->id ?>" />
                            <?php if ($reg->activated) { ?>
                            <input type="button" class="small" value="<?php echo iclicker_service::msg('app.activate') ?>" disabled="disabled" />
                            <input type="submit" class="small" value="<?php echo iclicker_service::msg('app.disable') ?>" alt="<?php echo iclicker_service::msg('reg.disable.submit.alt') ?>" />
                            <input type="hidden" name="activate" value="false" />
                            <?php } else { ?>
                            <input type="submit" class="small" value="<?php echo iclicker_service::msg('app.activate') ?>" alt="<?php echo iclicker_service::msg('reg.reactivate.submit.alt') ?>" />
                            <input type="button" class="small" value="<?php echo iclicker_service::msg('app.disable') ?>" disabled="disabled" />
                            <input type="hidden" name="activate" value="true" />
                            <?php } ?>
                        </form>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
        <!-- registration form area -->
        <div class="registration_entry_holder" style="background-color: #A2CDED; height: 35px; margin-top: 1em;">
            <div class="registration_entry style5">
                <?php echo iclicker_service::msg('reg.register.clickers') ?>:
                &nbsp;
                <form method="post" id="registerForm">
                    <input type="hidden" name="register" value="true" />
                    <input name="clickerId" type="text" size="10" maxlength="8" value="<?php echo $clicker_id_val ?>" />
                    <input type="submit" class="registerButton" value="<?php echo iclicker_service::msg('app.register') ?>"
                        alt="<?php echo iclicker_service::msg('reg.register.submit.alt') ?>" />
                </form>
            </div>
        </div>
        <?php if ($below_messages) { ?>
        <!-- registration below messages area -->
        <div class="registration_below_messages_holder style5" style="margin-top: 1em;">
            <div class="registration_below_messages">
                <?php foreach ($below_messages as $message) { ?>
                <p class="registration_below_message"><?php echo $message ?></p>
                <?php } ?>
            </div>
        </div>
        <?php } ?>
    </div>
    <!-- column two -->
    <!-- instructions and image -->
    <div style="margin-left: 45%; width: 40%;">
        <div style="padding-left:10px;">
            <div class="instructions style2" style="margin-bottom: 1em;">
                <?php
                    if ($new_reg) {
                        echo iclicker_service::msg('reg.registered.instructions');
                    } else {
                        echo iclicker_service::msg('reg.registration.instructions');
                    }
                ?>
            </div>
            <img src="<?php echo iclicker_service::block_url('img/Iclicker.jpg') ?>" border="0" alt="<?php echo iclicker_service::msg('reg.iclicker.image.alt') ?>" />
        </div>
    </div>
</div>

<?php echo $OUTPUT->footer(); ?>
