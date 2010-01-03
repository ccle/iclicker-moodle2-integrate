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
/* $Id: test_gradebook.php 9 2009-11-28 17:10:13Z azeckoski $ */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}

require_once (dirname(__FILE__).'/../../../config.php');
global $CFG,$USER,$COURSE;
// link in external libraries
require_once ($CFG->dirroot.'/blocks/iclicker/iclicker_service.php');

/**
 * Unit tests for the iclicker services
 * Execute tests at:
 * moodle/admin/report/unittest/index.php?path=blocks%2Ficlicker
 */
class iclicker_services_test extends UnitTestCase {

    var $courseid = 1;
    var $clicker_id = '99996666';

    var $studentid1 = 1;
    var $studentid2 = 2;

   	var $cat_name = 'az_category';
   	var $item_name = 'az_gradeitem';
   	var $grade_score = 91;

    public function setUp() {
        // cleanup any clickers before the test
        $user_id = iclicker_service::require_user();
        $results = iclicker_service::get_registrations_by_user($user_id);
        if ($results) {
            echo "cleanup registrations for user: $user_id  ";
            foreach($results as $reg) {
                if ($reg->clicker_id == $this->clicker_id) {
                    iclicker_service::remove_registration($reg->id);
                    echo "cleanup: $reg->id ";
                }
            }
        }
    }

    public function tearDown() {
    }

    function test_assert() {
        $this->assertEqual("AZ", "AZ");
    }

    function test_require_user() {
        $user_id = iclicker_service::require_user();
        $this->assertTrue($user_id);
    }

    function test_get_users() {
        $user_id = iclicker_service::require_user();
        $this->assertTrue($user_id);
        $results = iclicker_service::get_users(array($user_id));
        var_dump($results);
        $this->assertTrue($results);
        $this->assertTrue(count($results) == 1);
        $this->assertEqual($results[$user_id]['id'], $user_id);
    }
    
    function test_validate_clickerid() {
        $clicker_id = null;
        try {
            iclicker_service::validate_clicker_id($clicker_id);
            $this->fail("should have died");
        } catch (ClickerIdInvalidException $e) {
            $this->assertEqual(ClickerIdInvalidException::F_EMPTY, $e->type);
        }

        $clicker_id = "XXX";
        try {
            iclicker_service::validate_clicker_id($clicker_id);
            $this->fail("should have died");
        } catch (ClickerIdInvalidException $e) {
            $this->assertEqual(ClickerIdInvalidException::F_CHARS, $e->type);
        }

        $clicker_id = "00000000000";
        try {
            iclicker_service::validate_clicker_id($clicker_id);
            $this->fail("should have died");
        } catch (ClickerIdInvalidException $e) {
            $this->assertEqual(ClickerIdInvalidException::F_LENGTH, $e->type);
        }

        $clicker_id = iclicker_service::CLICKERID_SAMPLE;
        try {
            iclicker_service::validate_clicker_id($clicker_id);
            $this->fail("should have died");
        } catch (ClickerIdInvalidException $e) {
            $this->assertEqual(ClickerIdInvalidException::F_SAMPLE, $e->type);
        }

        $clicker_id = "ABCD0123";
        try {
            iclicker_service::validate_clicker_id($clicker_id);
            $this->fail("should have died");
        } catch (ClickerIdInvalidException $e) {
            $this->assertEqual(ClickerIdInvalidException::F_CHECKSUM, $e->type);
        }

        $clicker_id = "112233";
        $result = iclicker_service::validate_clicker_id($clicker_id);
        $this->assertEqual($result, "00112233");

        $clicker_id = "11111111";
        $result = iclicker_service::validate_clicker_id($clicker_id);
        $this->assertEqual($result, $clicker_id);
    }

    function test_registrations() {
        $reg = null;
        $user_id = iclicker_service::require_user();

        // try get registration
        $reg = iclicker_service::get_registration_by_clicker_id($this->clicker_id);
        $this->assertFalse($reg);

        // create registration
        $reg = iclicker_service::create_clicker_registration($this->clicker_id, $user_id);
        $this->assertTrue($reg);
        $this->assertEqual($this->clicker_id, $reg->clicker_id);
        $reg_id = $reg->id;
        $this->assertTrue($reg_id);
        $this->assertFalse($reg->from_national);

        // get registration
        $reg1 = iclicker_service::get_registration_by_clicker_id($this->clicker_id, $user_id);
        $this->assertTrue($reg1);
        $this->assertEqual($this->clicker_id, $reg1->clicker_id);
        $this->assertEqual($reg_id, $reg1->id);

        $reg2 = iclicker_service::get_registration_by_id($reg_id);
        $this->assertTrue($reg2);
        $this->assertEqual($this->clicker_id, $reg2->clicker_id);
        $this->assertEqual($reg_id, $reg2->id);

        // save registration
        $reg->from_national = 1;
        $save_id = iclicker_service::save_registration($reg);
        $this->assertTrue($save_id);
        $this->assertEqual($reg_id, $save_id);

        // check it changed
        $reg3 = iclicker_service::get_registration_by_id($reg_id);
        $this->assertTrue($reg3);
        $this->assertEqual($reg_id, $reg3->id);
        $this->assertTrue($reg3->from_national);
        // too fast $this->assertNotEqual($reg->timemodified, $reg3->timemodified);

        // make registration inactive
        $this->assertTrue($reg->activated);
        $reg4 = iclicker_service::set_registration_active($reg_id, false);
        $this->assertTrue($reg4);
        $this->assertFalse($reg4->activated);
        // check it changed
        $reg5 = iclicker_service::get_registration_by_id($reg_id);
        $this->assertTrue($reg5);
        $this->assertEqual($reg_id, $reg5->id);
        $this->assertEqual($reg4->id, $reg5->id);
        $this->assertFalse($reg5->activated);
        
        // get all registration
        $results = iclicker_service::get_registrations_by_user($user_id);
        $this->assertTrue($results);
        $this->assertEqual(1, count($results));

        $results = iclicker_service::get_registrations_by_user($user_id, true);
        $this->assertNotNull($results);
        $this->assertFalse($results);
        $this->assertEqual(0, count($results));
        
        $results = iclicker_service::get_all_registrations();
        $this->assertTrue($results);
        $this->assertTrue(count($results) >= 1);

        // remove registration
        $result = iclicker_service::remove_registration($reg_id);
        $this->assertTrue($result);

        // try get registration
        $reg = iclicker_service::get_registration_by_id($reg_id);
        $this->assertFalse($reg);
    }

    function test_encode_decode() {
        $xml = <<<XML
<Register>
  <S DisplayName="DisplayName-azeckoski-123456" FirstName="First" LastName="Lastazeckoski-123456" 
    StudentID="eid-azeckoski-123456" Email="azeckoski-123456@email.com" URL="http://sakaiproject.org"; ClickerID="11111111"></S>
</Register>
XML;
        $result = iclicker_service::decode_registration($xml);
        $this->assertNotNull($result);
        $this->assertNotNull($result->clicker_id);
        $this->assertNotNull($result->owner_id);
        $this->assertEqual($result->clicker_id, '11111111');
        $this->assertEqual($result->owner_id, 'eid-azeckoski-123456');
    }

}
?>
