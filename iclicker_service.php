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

require_once (dirname(__FILE__).'/../../config.php');
global $CFG,$USER,$COURSE;
// link in external libraries
require_once ($CFG->libdir.'/gradelib.php');
require_once ($CFG->libdir.'/dmllib.php');
require_once ($CFG->libdir.'/accesslib.php');

/**
 * For XML error handling
 */
function HandleXmlError($errno, $errstr, $errfile, $errline) {
    if ($errno==E_WARNING && (substr_count($errstr,"DOMDocument::loadXML()")>0)) {
        throw new DOMException($errstr);
    } else {
        return false;
    }
}

/**
 * Defines an exception which can occur when validating clicker ids
 * Valid types are:
 * empty - the clickerId is null or empty string
 * length - the clickerId length is not 8 chars (too long), shorter clickerIds are padded out to 8
 * chars - the clickerId contains invalid characters
 * checksum - the clickerId did not validate using the checksum method
 * sample - the clickerId matches the sample one and cannot be used
 */
class ClickerIdInvalidException extends Exception {
    const F_EMPTY = 'EMPTY';
    const F_LENGTH = 'LENGTH';
    const F_CHARS = 'CHARS';
    const F_CHECKSUM = 'CHECKSUM';
    const F_SAMPLE = 'SAMPLE';
    public $type = "UNKNOWN";
    public $clicker_id = NULL;
    /**
     * @param string $message the error message
     * @param string $type [optional] Valid types are:
     * empty - the clickerId is null or empty string
     * length - the clickerId length is not 8 chars (too long), shorter clickerIds are padded out to 8
     * chars - the clickerId contains invalid characters
     * checksum - the clickerId did not validate using the checksum method
     * sample - the clickerId matches the sample one and cannot be used
     * @param string $clicker_id [optional] the clicker id
     */
    function __construct($message, $type = NULL, $clicker_id = NULL) {
        parent::__construct($message);
        $this->type = $type;
        $this->clicker_id = $clicker_id;
    }
    public function errorMessage() {
        $errorMsg = 'Error on line '.$this->getLine().' in '.$this->getFile().': '.$this->getMessage().' : type='.$this->type.' : clicker_id='.$this->clicker_id;
        return $errorMsg;
    }
}

class ClickerRegisteredException extends Exception {
    public $owner_id;
    public $clicker_id;
    public $registered_owner_id;
    function __construct($message, $owner_id, $clicker_id, $registered_owner_id) {
        parent::__construct($message);
        $this->owner_id = $owner_id;
        $this->clicker_id = $clicker_id;
        $this->registered_owner_id = $registered_owner_id;
    }
    public function errorMessage() {
        $errorMsg = 'Error on line '.$this->getLine().' in '.$this->getFile().': '.$this->getMessage().' : cannot register to '.$this->owner_id.', clicker already registered to owner='.$this->registered_owner_id.' : clicker_id='.$this->clicker_id;
        return $errorMsg;
    }
}

/**
 * This marks an exception as being related to an authn or authz failure
 */
class SecurityException extends Exception {
}

/**
 * This holds all the service logic for the iclicker integrate plugin
 */
class iclicker_service {

    // CONSTANTS
    const BLOCK_NAME = 'block_iclicker';
    const BLOCK_PATH = '/blocks/iclicker';
    const REG_TABLENAME = 'iclicker_registration';
    const REG_ORDER = 'timecreated desc';
    const DEFAULT_SYNC_HOUR = 3;
    const DEFAULT_SERVER_URL = "http://moodle.org/"; // "http://epicurus.learningmate.com/";
    const NATIONAL_WS_URL = "https://webservices.iclicker.com/iclicker_gbsync_registrations/service.asmx";
    /*
     * iclicker_gbsync_reg / #8d7608e1e7f4@
     * 'Basic ' + base64(username + ":" + password)
     */
    const NATIONAL_WS_BASIC_AUTH_HEADER = "Basic aWNsaWNrZXJfZ2JzeW5jX3JlZzojOGQ3NjA4ZTFlN2Y0QA==";
    // errors constants
    const SCORE_UPDATE_ERRORS = 'ScoreUpdateErrors';
    const POINTS_POSSIBLE_UPDATE_ERRORS = 'PointsPossibleUpdateErrors';
    const USER_DOES_NOT_EXIST_ERROR = 'UserDoesNotExistError';
    const GENERAL_ERRORS = 'GeneralErrors';
    const SCORE_KEY = '${SCORE}';
    
    // CLASS VARIABLES
    
    // CONFIG
    public static $server_id = "UNKNOWN_SERVER_ID";
    public static $server_URL = self::DEFAULT_SERVER_URL;
    public static $domain_URL = self::DEFAULT_SERVER_URL;
    public static $use_national_webservices = false;
    public static $webservices_URL = self::NATIONAL_WS_URL;
    public static $webservices_use_basic_auth = true;
    public static $webservices_basic_auth_header = self::NATIONAL_WS_BASIC_AUTH_HEADER;
    public static $disable_sync_with_national = false;
    public static $webservices_national_sync_hour = self::DEFAULT_SYNC_HOUR;
    public static $test_mode = false;
    
    var $notify_emails_string = NULL;
    var $notify_emails = array(
    );
    
    // STATIC METHODS
    
    /**
     * @return the path for this block
     */
    public static function block_path($added = NULL) {
        global $CFG;
        if (isset($added)) {
            $added = '/'.$added;
        } else {
            $added = '';
        }
        return $CFG->dirroot.self::BLOCK_PATH.$added;
    }
    
    /**
     * @return the url for this block
     */
    public static function block_url($added = NULL) {
        global $CFG;
        if (isset($added)) {
            $added = '/'.$added;
        } else {
            $added = '';
        }
        return $CFG->wwwroot.self::BLOCK_PATH.$added;
    }
    
    /**
     * i18n message handling
     *
     * @param string $key i18 msg key
     * @param object $vars [optional] optional replacement variables
     * @return the translated string
     */
    public static function msg($key, $vars = NULL) {
        return get_string($key, self::BLOCK_NAME, $vars);
    }
    
    public static function df($time) {
        return strftime('%Y/%m/%d', $time); //userdate($time, '%Y/%m/%d');
    }
    
    public static function sendEmail() {
        // FIXME LOW not implemented
        //email_to_user($user, $from, $subject, $messagetext, $messagehtml='', $attachment='', $attachname='', $usetrueaddress=true, $replyto='', $replytoname='', $wordwrapwidth=79);
    }
    
    // USERS

    const USER_FIELDS = 'id,username,firstname,lastname,email';

    /**
     * Authenticate a user by username and password
     * @param string $username
     * @param string $password
     * @return true if the authentication is successful
     * @throw SecurityException if auth invalid
     */
    public static function authenticate_user($username, $password) {
        global $USER;
        if (!isset($USER->id)) {
            $u = authenticate_user_login($username, $password);
            if ($u === false) {
                throw new SecurityException('Could not authenticate username ('.$username.')');
            }
            complete_user_login($u);
        }
        return true;
    }
    
    /**
     * Ensure user is logged in and return the current user id
     * @return the current user id
     * @throws SecurityException if there is no current user
     * @static
     */
    public static function require_user() {
        global $USER;
        if (!isset($USER->id)) {
            throw new SecurityException('User must be logged in');
        }
        return $USER->id;
    }
    
    /**
     * Gets the current user_id, return FALSE if none can be found
     * @return the current user id OR null/false if no user
     */
    public static function get_current_user_id() {
        $current_user = null;
        try {
            $current_user = self::require_user();
        } catch (SecurityException $e) {
            $current_user = false;
        }
        return $current_user;
    }

    /**
     * Gets a user by their username
     * @param string $username the username (i.e. login name)
     * @return the user object OR false if none can be found
     */
    public static function get_user_by_username($username) {
        $user = false;
        if ($username) {
            $user = get_record('user', 'username', $username, '', '', '', '', self::USER_FIELDS);
            // TESTING handling
            if (self::$test_mode && !$user) {
                $user = new stdClass();
                $user->id = 100;
                $user->username = $username;
                $user->firstname = 'Student';
                $user->lastname = 'One';
                $user->email = 'one@fail.com';
            }
        }
        return $user;
    }

    /**
     * Get user records for a set of user ids
     * @param array $user_ids an array of user ids OR a single user_id
     * @return a map of user_id -> user data OR single user object for single user_id OR empty array if no matches
     */
    public static function get_users($user_ids) {
        $results = array(
        );
        if (isset($user_ids)) {
            if (is_array($user_ids)) {
                $users = false;
                if (! empty($user_ids)) {
                    $ids = implode(',', $user_ids);
                    $users = get_records_list('user', 'id', $ids, 'id', self::USER_FIELDS);
                }
                if ($users) {
                    foreach ($users as $user) {
                        self::makeUserDisplayName($user);
                        $results[$user->id] = $user;
                    }
                }
            } else {
                // single user id
                $user = get_record('user', 'id', $user_ids, '', '', '', '', self::USER_FIELDS);
                // TESTING handling
                if (self::$test_mode && !$user) {
                    $user = new stdClass();
                    $user->id = $user_ids;
                    $user->username = 'student01';
                    $user->firstname = 'Student';
                    $user->lastname = 'One';
                    $user->email = 'one@fail.com';
                }
                if ($user) {
                    self::makeUserDisplayName($user);
                    $results = $user;
                }
            }
        }
        return $results;
    }
    
    /**
     * Get a display name for a given user id
     * @param int $user_id id for a user
     * @return the display name
     */
    public static function get_user_displayname($user_id) {
        $name = "UNKNOWN-".$user_id;
        $users = self::get_users($user_id);
        if ($users && array_key_exists($user_id, $users)) {
            $name = self::makeUserDisplayName($users[$user_id]);
        }
        return $name;
    }

    private static function makeUserDisplayName(&$user) {
        $display_name = fullname($user);
        $user->name = $display_name;
        $user->display_name = $display_name;
        return $display_name;
    }

    /**
     * @param int $user_id [optional] the user id
     * @return true if this user is an admin OR false if not
     * @static
     */
    public static function is_admin($user_id = NULL) {
        if (!isset($user_id)) {
            try {
                $user_id = self::require_user();
            }
            catch (SecurityException $e) {
                return false;
            }
        }
        $result = is_siteadmin($user_id);
        return $result;
    }

    /**
     * Check if a user is an instructor in moodle
     * 
     * @param int $user_id [optional] the user id to check (default to current user)
     * @return true if an instructor or false otherwise
     * @static
     */
    public static function is_instructor($user_id = NULL) {
        global $USER;
        if (!isset($user_id)) {
            try {
                $user_id = self::require_user();
            }
            catch (SecurityException $e) {
                return false;
            }
        }
        // sadly this is the only way to do this check: http://moodle.org/mod/forum/discuss.php?d=140383
        $accessinfo = $USER->access;
        if ($user_id === $USER->id && isset($USER->access)) {
            $accessinfo = $USER->access;
        } else {
            $accessinfo = get_user_access_sitewide($user_id);
        }
        $results = get_user_courses_bycap($user_id, 'moodle/course:update', $accessinfo, false,
            'c.sortorder', array(), 1);
        $result = count($results) > 0;
        return $result;
    }
    
    const CLICKERID_SAMPLE = '11A4C277';
    /**
     * Cleans up and validates a given clicker_id
     * @param clicker_id a remote clicker ID
     * @return the cleaned up and valid clicker ID
     * @throws ClickerIdInvalidException if the id is invalid for some reason,
     * the exception will indicate the type of validation failure
     * @static
     */
    public static function validate_clicker_id($clicker_id) {
        if (!isset($clicker_id) || strlen($clicker_id) == 0) {
            throw new ClickerIdInvalidException("empty or NULL clicker_id", ClickerIdInvalidException::F_EMPTY, $clicker_id);
        }
        if (strlen($clicker_id) > 8) {
            throw new ClickerIdInvalidException("clicker_id is an invalid length", ClickerIdInvalidException::F_LENGTH, $clicker_id);
        }
        $clicker_id = strtoupper(trim($clicker_id));
        if (!preg_match('/^[0-9A-F]+$/', $clicker_id)) {
            throw new ClickerIdInvalidException("clicker_id can only contains A-F and 0-9", ClickerIdInvalidException::F_CHARS, $clicker_id);
        }
        while (strlen($clicker_id) < 8) {
            $clicker_id = "0".$clicker_id;
        }
        if (self::CLICKERID_SAMPLE == $clicker_id) {
            throw new ClickerIdInvalidException("clicker_id cannot match the sample ID", ClickerIdInvalidException::F_SAMPLE, $clicker_id);
        }
        $idArray = array(
        );
        $idArray[0] = substr($clicker_id, 0, 2);
        $idArray[1] = substr($clicker_id, 2, 2);
        $idArray[2] = substr($clicker_id, 4, 2);
        $idArray[3] = substr($clicker_id, 6, 2);
        $checksum = 0;
        foreach ($idArray as $piece) {
            $hex = hexdec($piece);
            $checksum = $checksum ^ $hex;
        }
        if ($checksum != 0) {
            throw new ClickerIdInvalidException("clicker_id checksum (" + $checksum + ") validation failed", ClickerIdInvalidException::F_CHECKSUM, $clicker_id);
        }
        return $clicker_id;
    }
    
    // CLICKER REGISTRATIONS DATA
    
    /**
     * @param int $id the registration ID
     * @return the registration object OR false if none found
     * @static
     */
    public static function get_registration_by_id($reg_id) {
        if (!isset($reg_id)) {
            throw new InvalidArgumentException("reg_id must be set");
        }
        $result = get_record(self::REG_TABLENAME, 'id', $reg_id);
        //$sql = "id = ".addslashes($reg_id);
        //$result = get_record_select(self::REG_TABLENAME, $sql);
        return $result;
    }
    
    /**
     * @param string $clicker_id the clicker id
     * @param int $user_id [optional] the user who registered the clicker (id)
     * @return the registration object OR false if none found
     * @static
     */
    public static function get_registration_by_clicker_id($clicker_id, $user_id = NULL) {
        if (!$clicker_id) {
            throw new InvalidArgumentException("clicker_id must be set");
        }
        $current_user_id = self::require_user();
        if (!isset($user_id)) {
            $user_id = $current_user_id;
        }
        try {
            $clicker_id = self::validate_clicker_id($clicker_id);
        }
        catch (ClickerIdInvalidException $e) {
            return false;
        }
        // NOTE: also returns disabled registrations
        $result = get_record(self::REG_TABLENAME, 'clicker_id', $clicker_id, 'owner_id', $user_id);
        //$sql = "clicker_id = '".addslashes($clicker_id)."' and owner_id = '".addslashes($user_id)."'";
        //$result = get_record_select(self::REG_TABLENAME, $sql);
        if ($result) {
            if (!self::can_read_registration($result, $current_user_id)) {
                throw new SecurityException("User ($current_user_id) not allowed to access registration ($result->id)");
            }
        }
        return $result;
    }
    
    public static function can_read_registration($clicker_registration, $user_id) {
        if (!isset($clicker_registration)) {
            throw new InvalidArgumentException("clicker_registration must be set");
        }
        if (!isset($user_id)) {
            throw new InvalidArgumentException("user_id must be set");
        }
        $result = false;
        if ($clicker_registration->owner_id == $user_id) {
            $result = true;
        }
        // FIXME make this do a real check
        $result = true;
        return $result;
    }
    
    public static function can_write_registration($clicker_registration, $user_id) {
        if (!isset($clicker_registration)) {
            throw new InvalidArgumentException("clicker_registration must be set");
        }
        if (!isset($user_id)) {
            throw new InvalidArgumentException("user_id must be set");
        }
        $result = false;
        if ($clicker_registration->owner_id == $user_id) {
            $result = true;
        }
        // FIXME make this do a real check
        $result = true;
        return $result;
    }
    
    /**
     * @param int $user_id [optional] the user id OR current user id
     * @param boolean $activated if null or not set then return all,
     * if true then return active only, if false then return inactive only
     * @return the list of registrations for this user or empty array if none
     */
    public static function get_registrations_by_user($user_id = NULL, $activated = NULL) {
        $current_user_id = self::require_user();
        if (!isset($user_id)) {
            $user_id = $current_user_id;
        }
        $sql = "owner_id = '".addslashes($user_id)."'";
        if (isset($activated)) {
            $sql .= ' and activated = '.($activated ? 1 : 0);
        }
        $results = get_records_select(self::REG_TABLENAME, $sql, self::REG_ORDER);
        if (!$results) {
            $results = array(
            );
        }
        return $results;
    }
    
    /**
     * ADMIN ONLY
     * This is a method to get all the clickers for the clicker admin view
     * @param int $start [optional] start value for paging
     * @param int $max [optional] max value for paging
     * @param string $order [optional] the order by string
     * @param string $search [optional] search string for clickers
     * @return array of clicker registrations
     */
    public static function get_all_registrations($start = 0, $max = 0, $order = 'clicker_id', $search = '') {
        if (!self::is_admin()) {
            throw new SecurityException("Only admins can use this function");
        }
        if ($max <= 0) {
            $max = 10;
        }
        $query = '';
        if ($search) {
            // build a search query
            $query = 'clicker_id '.sql_ilike().' '.addslashes($search).'%';
        }
        $results = get_records_select(self::REG_TABLENAME, $query, $order, '*', $start, $max);
        if (!$results) {
            $results = array(
            );
        } else {
            // insert user display names
            $user_ids = array();
            foreach ($results as $reg) {
                $user_ids[] = $reg->owner_id;
            }
            $user_ids = array_unique($user_ids);
            $users = self::get_users($user_ids);
            foreach ($results as $reg) {
                $name = 'UNKNOWN-'.$reg->owner_id;
                if (array_key_exists($reg->owner_id, $users)) {
                    $name = $users[$reg->owner_id]->name;
                }
                $reg->user_display_name = $name;
            }
        }
        return $results;
    }
    
    /**
     * @return the count of the total number of registered clickers
     */
    public static function count_all_registrations() {
        return count_records(self::REG_TABLENAME);
    }
    
    /**
     * ADMIN ONLY
     * Removes the registration from the database
     *
     * @param int $reg_id id of the clicker registration
     * @return true if removed OR false if not found or not removed
     */
    public static function remove_registration($reg_id) {
        if (!self::is_admin()) {
            throw new SecurityException("Only admins can use this function");
        }
        if (isset($reg_id)) {
            if (delete_records(self::REG_TABLENAME, 'id', $reg_id)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Create a registration
     *
     * @param string $clicker_id the clickerID (e.g. 11111111)
     * @param string $owner_id [optional] the user_id OR current user if not set
     * @param boolean $local_only [optional] create this clicker in the local system only if true, otherwise sync to national system as well
     * @return the clicker_registration object
     */
    public static function create_clicker_registration($clicker_id, $owner_id = NULL, $local_only = false) {
        $clicker_id = self::validate_clicker_id($clicker_id);
        $current_user_id = self::require_user();
        $user_id = $owner_id;
        if (!isset($owner_id)) {
            $user_id = $current_user_id;
        }
        $registration = self::get_registration_by_clicker_id($clicker_id, $user_id);
        // NOTE: we probably want to check the national system here to see if this is already registered
        if ($registration) {
            throw new ClickerRegisteredException($user_id, $registration->clicker_id, $registration->owner_id);
        } else {
            $clicker_registration = new stdClass ;
            $clicker_registration->clicker_id = $clicker_id;
            $clicker_registration->owner_id = $user_id;
            $reg_id = self::save_registration($clicker_registration);
            $registration = self::get_registration_by_id($reg_id);
            if ($local_only) {
                // FIXME syncClickerRegistrationWithNational(registration);
            }
        }
        return $registration;
    }
    
    /**
     * Make a registration active or inactive
     *
     * @param int $reg_id id of the clicker registration
     * @param boolean $activated true to enable, false to disable
     * @return the clicker_registration object
     */
    public static function set_registration_active($reg_id, $activated) {
        if (!isset($reg_id)) {
            throw new InvalidArgumentException("reg_id must be set");
        }
        if (!isset($activated)) {
            throw new InvalidArgumentException("active must be set");
        }
        $current_user_id = self::require_user();
        $registration = self::get_registration_by_id($reg_id);
        if (!$registration) {
            throw new InvalidArgumentException("Could not find registration with id ($reg_id)");
        }
        $registration->activated = $activated ? 1 : 0;
        self::save_registration($registration);
        return $registration;
    }
    
    /**
     * Saves the clicker registration data (create or update)
     * @param object $clicker_registration the registration data as an object
     * @return the id of the saved registration
     */
    public static function save_registration(&$clicker_registration) {
        if (!$clicker_registration || !isset($clicker_registration->clicker_id)) {
            throw new InvalidArgumentException("clicker_registration cannot be empty and clicker_id must be set");
        }
        $clicker_registration->clicker_id = self::validate_clicker_id($clicker_registration->clicker_id);
        $current_user_id = self::require_user();
        // set the owner to current if not set
        if (!isset($clicker_registration->owner_id)) {
            $clicker_registration->owner_id = $current_user_id;
        } else {
            // check for valid user id
            // @todo
        }
        $clicker_registration->timemodified = time();
        $reg_id = -1;
        if (!isset($clicker_registration->id)) {
            // new item to save (no perms check)
            $clicker_registration->timecreated = time();
            if (!$reg_id = insert_record(self::REG_TABLENAME, $clicker_registration, true)) {
                print_object($clicker_registration);
                error(self::msg('inserterror'));
            }
        } else {
            // updating existing item
            if (self::can_write_registration($clicker_registration, $current_user_id)) {
                if (!update_record(self::REG_TABLENAME, $clicker_registration)) {
                    print_object($clicker_registration);
                    error(self::msg('updateerror'));
                }
                $reg_id = $clicker_registration->id;
            } else {
                throw new SecurityException("Current user cannot update item ($clicker_registration->id) because they do not have permission");
            }
        }
        return $reg_id;
    }
    
    // COURSES METHODS

    /**
     * Get all the students for a course with their clicker registrations
     * @param int $course_id the course to get the students for
     * @param boolean $include_regs [optional]
     * @return the list of user objects for the students in the course
     */
    public static function get_students_for_course_with_regs($course_id, $include_regs=true) {
        // get_users_by_capability - accesslib - moodle/grade:view
        // search_users - datalib
        $context = get_context_instance(CONTEXT_COURSE, $course_id);
        $results = get_users_by_capability($context, 'moodle/grade:view', 'u.id, u.username, u.firstname, u.lastname, u.email', 'u.lastname', '', '', '', '', FALSE);
        if (isset($results) && !empty($results)) {
            // get the registrations related to these students
            $user_regs = array();
            if ($include_regs) {
                $query = 'activated = 1';
                if (count($results) > 500) {
                    // just return them all since the in query would be super slow anyway
                } else {
                    $query .= ' AND owner_id in (';
                    $first = true;
                    foreach ($results as $student) {
                        if ($first) {
                            $first = false;
                        } else {
                            $query .= ',';
                        }
                        $query .= $student->id;
                    }
                    $query .= ')';
                }
                $regs = get_records_select(self::REG_TABLENAME, $query);
                if ($regs) {
                    // now put these into a map
                    foreach ($regs as $reg) {
                        if (! array_key_exists($reg->owner_id, $user_regs)) {
                            $user_regs[$reg->owner_id] = array();
                        }
                        $user_regs[$reg->owner_id][] = $reg;
                    }
                }
            }
            foreach ($results as $user) {
                // setup display name
                self::makeUserDisplayName($user);
                if ($include_regs) {
                    // add in registrations
                    $user->clicker_registered = false;
                    $user->clickers = array();
                    if (array_key_exists($user->id, $user_regs)) {
                        $user->clicker_registered = true;
                        $user->clickers = $user_regs[$user->id];
                    }
                }
            }
        } else {
            // NO matches
            $results = array();
        }
        return $results;
    }

    /**
     * Get the listing of all courses for an instructor
     * @param int $user_id [optional] the unique user id for an instructor (default to current user)
     * @return the list of courses (maybe be emtpy array)
     */    
    public static function get_courses_for_instructor($user_id = NULL) {
        global $USER;
        // make this only get courses for this instructor
        // get_user_courses_bycap? - accesslib
        // http://docs.moodle.org/en/Category:Capabilities - moodle/course:update
        //$results = get_records('course', 'category', 1, 'id'); // get_records_sql("SELECT * FROM mdl_course where category = 1");
        if (! isset($user_id)) {
            $user_id = self::get_current_user_id();
        }
        $accessinfo = $USER->access;
        if ($user_id === $USER->id && isset($USER->access)) {
            $accessinfo = $USER->access;
        } else {
            $accessinfo = get_user_access_sitewide($user_id);
        }
        $results = get_user_courses_bycap($user_id, 'moodle/course:update', $accessinfo, false,
            'c.sortorder', array('fullname','summary'), 50);
        if (!$results) {
            $results = array();
        }
        return $results;
    }

    /**
     * Retrieve a single course by unique id
     * @param int $course_id the course
     * @return the course object or FALSE
     */
    public static function get_course($course_id) {
        $course = get_record('course', 'id', $course_id);
        if (!$course) {
            $course = FALSE;
        }
        return $course;
    }
    
    public static function get_course_grade_item($course_id, $grade_item_id) {
        // FIXME
        return array(
        );
    }
    
    public static function save_grade_item($grade_item) {
        // FIXME
        return array(
        );
    }
    
    // DATA ENCODING METHODS
    
    public static function encode_registration($clicker_registration) {
        if (! $clicker_registration) {
            throw new InvalidArgumentException("clicker_registration must be set");
        }
        $user_id = $clicker_registration->owner_id;
        $user = self::get_users($user_id);
        if (! $user) {
            throw new InvalidArgumentException("Invalid user id ($user_id) for clicker reg ($clicker_registration->clicker_id)");
        }
        $encoded = '<Register>'.PHP_EOL;
        $encoded .= '  <S DisplayName="';
        $encoded .= self::encode_for_xml($user->name);
        $encoded .= '" FirstName="';
        $encoded .= self::encode_for_xml($user->firstname);
        $encoded .= '" LastName="';
        $encoded .= self::encode_for_xml($user->lastname);
        $encoded .= '" StudentID="';
        $encoded .= self::encode_for_xml(strtoupper($user->username));
        $encoded .= '" Email="';
        $encoded .= self::encode_for_xml($user->email);
        $encoded .= '" URL="';
        $encoded .= self::encode_for_xml(self::$domain_URL);
        $encoded .= '" ClickerID="';
        $encoded .= strtoupper($clicker_registration->clicker_id);
        $encoded .= '" Enabled="';
        $encoded .= $clicker_registration->activated ? 'True' : 'False';
        $encoded .= '"></S>'.PHP_EOL;
        // close out
        $encoded .= '</Register>'.PHP_EOL;
        return $encoded;
    }
    
    public static function encode_registration_result($registrations, $status, $message) {
        if (! $registrations) {
            throw new InvalidArgumentException("registrations must be set");
        }
        if (! isset($status)) {
            throw new InvalidArgumentException("status must be set");
        }
        /* SAMPLE
1) When clicker is already registered to some one else - the same
message should be returned that is displayed in the plug-in in xml
format
<RetStatus Status="False" Message=""/>

2) When clicker is already registered to the same user - the same
message should be returned that is displayed in the plug-in in xml
format.
<RetStatus Status="False" Message=""/>

3) When studentid is not found in the CMS
<RetStatus Status="False" Message="Student not found in the CMS"/>

4) Successful registration - 
<RetStatus Status="True" Message="..."/>
         */
        $encoded = '<RetStatus Status="'.($status ? 'True' : 'False').'" Message="'.self::encode_for_xml($message).'" />';
        return $encoded;
    }
    
    public static function encode_courses($instructor_id) {
        if (! isset($instructor_id)) {
            throw new InvalidArgumentException("instructor_id must be set");
        }
        $instructor = self::get_users($instructor_id);
        if (! $instructor) {
            throw new InvalidArgumentException("Invalid instructor user id ($instructor_id)");
        }
        $courses = self::get_courses_for_instructor($instructor_id);
        if (! $courses) {
            throw new SecurityException("No courses found, only instructors can access instructor courses listings");
        }
        $encoded = '<coursemembership username="';
        $encoded .= self::encode_for_xml($instructor->username);
        $encoded .= '">'.PHP_EOL;
        // loop through courses
        foreach ($courses as $course) {
            $encoded .= '  <course id="'.$course->id.'" name="';
            $encoded .= self::encode_for_xml($course->fullname);
            $encoded .= '" usertype="I" />'.PHP_EOL;
        }
        // close out
        $encoded .= '</coursemembership>'.PHP_EOL;
        return $encoded;
    }
    
    public static function encode_enrollments($course_id) {
        if (! isset($course_id)) {
            throw new InvalidArgumentException("course_id must be set");
        }
        $course = self::get_course($course_id);
        if (! $course) {
            throw new InvalidArgumentException("No course found with course_id ($course_id)");
        }
        $students = self::get_students_for_course_with_regs($course_id);
        $encoded = '<courseenrollment courseid="'.$course->id.'">'.PHP_EOL;
        // the students may be an empty set
        if ($students) {
            // loop through students
            foreach ($students as $student) {
                // get the clicker data out first if there is any
                $cids_dates = self::make_clicker_ids_and_dates($student->clickers);
                // now make the actual user data line
                $encoded .= '  <user id="'.$student->id.'" usertype="S" firstname="';
                $encoded .= self::encode_for_xml($student->firstname ? $student->firstname : '');
                $encoded .= '" lastname="';
                $encoded .= self::encode_for_xml($student->lastname ? $student->lastname : '');
                $encoded .= '" emailid="';
                $encoded .= self::encode_for_xml($student->email ? $student->email : '');
                $encoded .= '" uniqueid="';
                $encoded .= self::encode_for_xml($student->username);
                $encoded .= '" clickerid="';
                $encoded .= self::encode_for_xml( $cids_dates['clickerid'] );
                $encoded .= '" whenadded="';
                $encoded .= self::encode_for_xml( $cids_dates['whenadded'] );
                $encoded .= '" />'.PHP_EOL;
            }
        }
        // close out
        $encoded .= '</courseenrollment>'.PHP_EOL;
        return $encoded;
    }

    private static function make_clicker_ids_and_dates($clicker_regs) {
        $clicker_ids = '';
        $clicker_added_dates = '';
        if ($clicker_regs && !empty($clicker_regs)) {
            $count = 0;
            foreach ($clicker_regs as $reg) {
                if ($count > 0) {
                    $clicker_ids .= ',';
                    $clicker_added_dates .= ',';
                }
                $clicker_ids .= $reg->clicker_id;
                $clicker_added_dates .= date('M/d/Y', $reg->timecreated);
                $count++;
            }
        }
        return array('clickerid' => $clicker_ids, 'whenadded' => $clicker_added_dates);
    }

    public static function encode_grade_item_results($course_id, $result_items) {
        if (! isset($course_id)) {
            throw new InvalidArgumentException("course_id must be set");
        }
        $course = self::get_course($course_id);
        if (! $course) {
            throw new InvalidArgumentException("No course found with course_id ($course_id)");
        }
        // check for any errors
        $has_errors = false;
        foreach ($result_items as $item) {
            if (isset($item->score_errors) && !empty($item->score_errors)) {
                $has_errors = true;
                break;
            }
        }
        /* SAMPLE
<errors courseid="BFW61">
  <Userdoesnotexisterrors>
    <user id="student03" />
  </Userdoesnotexisterrors>
  <Scoreupdateerrors>
    <user id="student02">
      <lineitem name="Decsample" pointspossible="0" type="Text" score="9" />
    </user>
  </Scoreupdateerrors>
  <PointsPossibleupdateerrors>
    <user id="6367a431-557c-4869-88a7-229c2398f6ec">
      <lineitem name="CMSIntTEST01" pointspossible="50" type="iclicker polling scores" score="70" />
    </user>
  </PointsPossibleupdateerrors>
  <Scoreupdateerrors>
    <user id="iclicker_student01">
      <lineitem name="Mac-integrate-2" pointspossible="31" type="092509Mac" score="13"/>
    </user>
  </Scoreupdateerrors>
  <Generalerrors>
    <user id="student02" error="CODE">
      <lineitem name="itemName" pointspossible="35" score="XX" error="CODE" />
    </user>
  </Generalerrors>
</errors>
         */
        $output = null;
        if ($has_errors) {
            $lineitems = self::make_lineitems($result_items);
            $invalid_user_ids = array();

            $encoded = '<errors courseId="'.$course_id.'">'.PHP_EOL;
            // loop through items and errors and generate errors xml blocks
            $error_items = array();
            foreach ($result_items as $item) {
                if (isset($item->score_errors) && !empty($item->score_errors)) {
                    foreach ($item->scores as $score) {
                        if ($score->error) {
                            $lineitem = $lineitems[$item->id];
                            if (self::USER_DOES_NOT_EXIST_ERROR == $score->error) {
                                $key = self::USER_DOES_NOT_EXIST_ERROR;
                                if (! array_key_exists($score->user_id, $invalid_user_ids)) {
                                    // only if the invalid user is not already listed in the errors
                                    $error_items[$key] .= '    <user id="'.$score->user_id.'" />'.PHP_EOL;
                                    $invalid_user_ids[$score->user_id] = $score->user_id;
                                }
                            } else if (self::POINTS_POSSIBLE_UPDATE_ERRORS == $score->error) {
                                $key = self::POINTS_POSSIBLE_UPDATE_ERRORS;
                                $li = str_replace(self::SCORE_KEY, $score->grade, $lineitem);
                                $error_items[$key] .= '    <user id="'.$score->user_id.'">'.PHP_EOL.'      '.$li.PHP_EOL.'    </user>'.PHP_EOL;
                            } else if (self::SCORE_UPDATE_ERRORS == $score->error) {
                                $key = self::SCORE_UPDATE_ERRORS;
                                $li = str_replace(self::SCORE_KEY, $score->grade, $lineitem);
                                $error_items[$key] .= '    <user id="'.$score->user_id.'">'.PHP_EOL.'      '.$li.PHP_EOL.'    </user>'.PHP_EOL;
                            } else {
                                // general error
                                $key = self::GENERAL_ERRORS;
                                $li = str_replace(self::SCORE_KEY, $score->grade, $lineitem);
                                $error_items[$key] .= '    <user id="'.$score->user_id.'" error="'.$score->error.'">'.PHP_EOL.
                                                      '      <error type="'.$score->error.'" />'.PHP_EOL.
                                                      '      '.$li.PHP_EOL.
                                                      '    </user>'.PHP_EOL;
                            }
                        }
                    }
                }
            }
            // loop through error items and dump to the output
            if (array_key_exists(self::USER_DOES_NOT_EXIST_ERROR, $error_items)) {
                $encoded .= '  <Userdoesnotexisterrors>'.PHP_EOL.$error_items[self::USER_DOES_NOT_EXIST_ERROR].'  </Userdoesnotexisterrors>'.PHP_EOL;
            }
            if (array_key_exists(self::POINTS_POSSIBLE_UPDATE_ERRORS, $error_items)) {
                $encoded .= '  <PointsPossibleupdateerrors>'.PHP_EOL.$error_items[self::POINTS_POSSIBLE_UPDATE_ERRORS].'  </PointsPossibleupdateerrors>'.PHP_EOL;
            }
            if (array_key_exists(self::SCORE_UPDATE_ERRORS, $error_items)) {
                $encoded .= '  <Scoreupdateerrors>'.PHP_EOL.$error_items[self::SCORE_UPDATE_ERRORS].'  </Scoreupdateerrors>'.PHP_EOL;
            }
            if (array_key_exists(self::GENERAL_ERRORS, $error_items)) {
                $encoded .= '  <Generalerrors>'.PHP_EOL.$error_items[self::GENERAL_ERRORS].'  </Generalerrors>'.PHP_EOL;
            }
            // close out
            $encoded .= '</errors>'.PHP_EOL;
            $output = $encoded;
        }
        return $output;
    }

    private static function make_lineitems($items) {
        $lineitems = array();
        foreach ($items as $item) {
            $li = '<lineitem name="'.self::escape_for_xml($item->itemname).'" pointspossible="'.$item->grademax.'" type="'.$item->itemtype.'" score="'.self::SCORE_KEY.'"/>';
            $lineitems[$item->id] = $li;
        }
        return $lineitems;
    }

    private static function parse_xml_to_doc($xml) {
        if (!$xml) {
            throw new InvalidArgumentException("xml must be set");
        }
        // read the xml (try to anyway)
        set_error_handler('HandleXmlError');
        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;
        if (! $doc->loadXML($xml, LIBXML_NOWARNING) ) {
            throw new Exception("XML read and parse failure: $xml");
        }
        $doc->normalizeDocument();
        restore_error_handler();
        return $doc;
    }

    public static function decode_registration($xml) {
        /*
<Register>
  <S DisplayName="DisplayName-azeckoski-123456" FirstName="First" LastName="Lastazeckoski-123456" 
    StudentID="eid-azeckoski-123456" Email="azeckoski-123456@email.com" URL="http://sakaiproject.org"; ClickerID="11111111"></S>
</Register>
         */
        $doc = self::parse_xml_to_doc($xml);
        $clicker_reg = new stdClass();
        try {
            $users = $doc->getElementsByTagName("S");
            if ($users->length <= 0) {
                throw new InvalidArgumentException("Invalid XML, no S element");
            }
            $user_node = $users->item(0);
            if ($user_node->nodeType == XML_ELEMENT_NODE) {
                $clicker_id = $user_node->getAttribute("ClickerID");
                if (! $clicker_id) {
                    throw new InvalidArgumentException("Invalid XML for registration, no id in the ClickerID element (Cannot process)");
                }
                $username = $user_node->getAttribute("StudentID"); // this is the username
                if (! $username) {
                    throw new InvalidArgumentException("Invalid XML for registration, no id in the StudentID element (Cannot process)");
                }
                $clicker_reg->clicker_id = $clicker_id;
                $clicker_reg->user_username = $username;
                $user = self::get_user_by_username($username);
                if (! $user) {
                    throw new InvalidArgumentException("Invalid username for student ($username), could not find user (Cannot process)");
                }
                $clicker_reg->owner_id = $user->id;
                $clicker_reg->user_display_name = $user_node->getAttribute("DisplayName");
            } else {
                throw new InvalidArgumentException("Invalid user node in XML: $user_node");
            }
        } catch (Exception $e) {
            throw new Exception("XML DOM parsing failure: $e :: $xml");
        }
        return $clicker_reg;
    }
    
    public static function decode_grade_item($xml) {
        /*
<coursegradebook courseid="BFW61">
  <user id="lm_student01" usertype="S">
    <lineitem name="06/02/2009" pointspossible="50" type="iclicker polling scores" score="0"/>
  </user>
  <user id="lm_student02" usertype="S">
    <lineitem name="06/02/2009" pointspossible="50" type="iclicker polling scores" score="0"/>
  </user>
</coursegradebook>
         */
        $doc = self::parse_xml_to_doc($xml);
        $grade_item = new stdClass();
        try {
            // get the course id from the root attribute
            $course_id = $doc->documentElement->getAttribute("courseid");
            if (! $course_id) {
                throw new InvalidArgumentException("Invalid XML, no courseid in the root xml element");
            }
            $users = $doc->getElementsByTagName("user");
            if ($users->length <= 0) {
                throw new InvalidArgumentException("Invalid XML, no user elements found");
            }
            $grade_item->course_id = $course_id;
            foreach ($users as $user_node) {
                if ($user_node->nodeType == XML_ELEMENT_NODE) {
                    $user_type = $user_node->getAttribute("usertype");
                    if (! strcasecmp('s', $user_type)) {
                        continue; // skip this one
                    }
                    // valid user to process
                    $user_id = $user_node->getAttribute("id"); // this is the userId
                    if (! $user_id) {
                        //log.warn("Invalid XML for user, no id in the user element (skipping this entry): " + user);
                        continue;
                    }
                    $lineitems = $user_node->getElementsByTagName("lineitem");
                    foreach ($lineitems as $lineitem) {
                        $li_name = $lineitem->getAttribute("name");
                        if (! $li_name) {
                            throw new InvalidArgumentException("Invalid XML, no name in the lineitem xml element: $lineitem");
                        }
                        if (! isset($grade_item->points_possible)) {
                            // only read the points possible from the first item
                            $li_type = $lineitem->getAttribute("type");
                            $li_pp = 100.0;
                            $lipptext = $lineitem->getAttribute("pointspossible");
                            if (isset($lipptext) && $lipptext != '') {
                                if (! is_numeric($lipptext)) {
                                    //log.warn("Invalid points possible ("+liPPText+"), using default of "+liPointsPossible+": " + lineitem + ": " + e);
                                } else {
                                    $li_pp = floatval($lipptext);
                                }
                            }
                            $grade_item->name = $li_name;
                            $grade_item->points_possible = $li_pp;
                            $grade_item->type = $li_type;
                            $grade_item->scores = array();
                        }
                        $li_score = $lineitem->getAttribute("score");
                        if (! isset($li_score) || '' == $li_score) {
                            //log.warn("Invalid score ("+liScore+"), skipping this entry: " + lineitem);
                            continue;
                        }
                        // add in the score
                        $score = new stdClass();
                        $score->item_name = $grade_item->name;
                        $score->user_id = $user_id;
                        $score->score = $li_score;
                        $grade_item->scores[] = $score;
                    }
                } else {
                    throw new InvalidArgumentException("Invalid user node in XML: $user_node");
                }
            }
        } catch (Exception $e) {
            throw new Exception("XML DOM parsing failure: $e :: $xml");
        }
        return $grade_item;
    }
    
    public static function decode_ws_xml($xml) {
        /*
<StudentRoster>
    <S StudentID="student01" FirstName="student01" LastName="student01" URL="https://www.iclicker.com/" CourseName="">
        <Registration ClickerId="12CE32EE" WhenAdded="2009-01-27" Enabled="True" />
    </S>
</StudentRoster>
         */
        $doc = self::parse_xml_to_doc($xml);
        $regs = array();
        try {
            $users = $doc->getElementsByTagName("S");
            if ($users->length > 0) {
                foreach ($users as $user_node) {
                    if ($user_node->nodeType == XML_ELEMENT_NODE) {
                        $student_id = $user_node->getAttribute("StudentID"); // this is the user eid
                        if (! isset($student_id) || '' == $student_id) {
                            throw new InvalidArgumentException("Invalid XML for registration, no id in the StudentID element (Cannot process)");
                        }
                        $student_id = strtolower($student_id); // username
                        $user = self::get_user_by_username($student_id);
                        if (! $user) {
                            //log.warn("Cannot identify user (id="+studentId+") in the national webservices feed, skipping this user");
                            continue;
                        }
                        $user_id = $user->id;
                        $reg_nodes = $user_node->getElementsByTagName("Registration");
                        if ($reg_nodes->length > 0) {
                            foreach ($reg_nodes as $reg_node) {
                                if ($reg_node->nodeType == XML_ELEMENT_NODE) {
                                    $clicker_id = $reg_node->getAttribute("ClickerId");
                                    if (! $clicker_id) {
                                        //log.warn("Missing clickerId in webservices registration XML line, skipping this registration for user: $user_id");
                                        continue;
                                    }
                                    $when_added = $reg_node->getAttribute("WhenAdded"); // "yyyy-MM-dd"
                                    $date_created = time();
                                    if (isset($when_added)) {
                                        $time = strtotime($when_added);
                                        if ($time) {
                                            $date_created = $time;
                                        }
                                    }
                                    $enabled = $reg_node->getAttribute("Enabled");
                                    $activated = true;
                                    if (isset($enabled)) {
                                        $activated = (boolean) $enabled;
                                    }
                                    $clicker_reg = new stdClass();
                                    $clicker_reg->clicker_id = $clicker_id;
                                    $clicker_reg->owner_id = $user_id;
                                    $clicker_reg->user_username = $student_id;
                                    $clicker_reg->timecreated = $date_created;
                                    $clicker_reg->date_created = $date_created;
                                    $clicker_reg->activated = $activated;
                                    $regs[] = $clicker_reg;
                                } else {
                                    // only skipping invalid ones
                                    //log.warn("Invalid registration node in XML (skipping this one): $reg_node");
                                }
                            }
                        }
                    } else {
                        // only skipping invalid ones
                        //throw new InvalidArgumentException("Invalid user node in XML: $user_node");
                    }
                }
            }
        } catch (Exception $e) {
            throw new Exception("XML DOM parsing failure: $e :: $xml");
        }
        return $regs;
    }

    
    // NATIONAL WEBSERVICES
    
    public static function ws_sync_clicker($clicker_registration) {
        // FIXME
        return array(
        );
    }
    
    public static function ws_get_students() {
        // FIXME
        return array(
        );
    }
    
    public static function ws_get_student($user_name) {
        // FIXME
        return array(
        );
    }
    
    public static function ws_save_clicker($user_name) {
        // FIXME
        return array(
        );
    }

    // XML support functions

    /**
     * encodes a string for inclusion in an xml document
     * @param string $value the value to encode
     * @return the value with xml chars encoded and replaced
     */
    private static function encode_for_xml($value) {
        if ($value) {
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
        return '';
    }
    
}
?>
