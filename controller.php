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
/* $Id: controller.php 9 2009-11-28 17:10:13Z azeckoski $ */

/**
 * Handles controller functions related to the views
 */
 
require_once ('../../config.php');
global $CFG,$USER,$COURSE;
require_once ('iclicker_service.php');

class iclicker_controller {

    const TYPE_HTML = 'html';
    const TYPE_XML = 'xml';
    const TYPE_TEXT = 'txt';
    
    public $method = 'GET';
    public $body = NULL;
    public $headers = NULL;
    public $response = NULL;
    public $results = array(
    );
    
    var $TIME_START = 0;
    
    public function __construct($getBody = false) {
        $this->TIME_START = microtime(true);
        $this->headers = array(
        );
        // set some headers
        $this->headers['Content-Encoding'] = 'UTF8';
        //header('Content-type: text/plain');
        //header('Cache-Control: no-cache, must-revalidate');
        $response_data = array(
            'code'=>200, 'type'=>self::TYPE_HTML, 'message'=>''
        );
        if ($getBody) {
            $this->body = @file_get_contents('php://input');
        }
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->response = $response_data;
    }
    
    /**
     * Send the response
     *
     * @param string $message [optional] the message to send, defaults to "Invalid request"
     * @param int    $code    [optional] the status code, defaults to 400
     * @param array  $headers [optional] any headers to include when sending the response
     */
    public function sendResponse($message = "Invalid request parameters", $code = 400, $headers = array(
    )) {
        header("HTTP/1.0 $code ".str_replace("\n", "", $message));
        if ($code >= 400) {
            // force plain text encoding when errors occur
            header('Content-type: text/plain');
        }
        if (isset($headers) and ! empty($headers)) {
            foreach ($headers as $key=> & $value) {
                header($key.': '.$value, false);
            }
            unset($value);
        }
        if (! empty($message)) {
            if ($code >= 400) {
                // include the helpful message
                $message .= "\n POST requests are required\n The request must be formed in one of 3 ways:\n "." 1) Including the fields of the post as http form params (required: post_title, post_content)\n "." 2) Including a field 'data' which contains xml with the post data, \n"."     Example: <post><post_title>TITLE</post_title><post_content>CONTENT</post_content></post>\n "." 3) Including the data for multiple posts in the body of the request (similar xml format to above but with <posts>...</posts>), \n"."     Example: <posts><post><post_title>TITLE1</post_title><post_content>CONTENT</post_content></post><post><post_title>TITLE2</post_title><post_content>CONTENT</post_content></post></posts> \n";
            }
            $time = microtime(true) - $this->TIME_START;
            $message .= "\nTotal processing time: ".round($time, 4)." seconds at ".date("H:i:s")."\n";
            die($code." ".$message);
        }
    }

    
    // XHTML view processors
    
    public function processRegistration() {
        // process calls to the registration view
        $this->results['new_reg'] = false;
        $this->results['clicker_id_val'] = "";
        if ("POST" == $this->method) {
            if (optional_param('register', NULL) != NULL) {
                // we are registering a clicker
                $clicker_id = optional_param('clickerId', NULL, PARAM_RAW);
                if ($clicker_id == NULL) {
                    $this->addMessage(self::KEY_ERROR, "reg.registered.clickerId.empty");
                } else {
                    $this->results['clicker_id_val'] = $clicker_id;
                    // save a new clicker registration
                    try {
                        iclicker_service::create_clicker_registration($clicker_id);
                        $this->addMessage(self::KEY_INFO, "reg.registered.success", $clicker_id);
                        $this->addMessage(self::KEY_BELOW, "reg.registered.below.success");
                        $this->results['new_reg'] = true;
                    }
                    catch (ClickerRegisteredException $e) {
                        $this->addMessage(self::KEY_ERROR, "reg.registered.clickerId.duplicate", $clicker_id);
                        $this->addMessage(self::KEY_BELOW, "reg.registered.below.duplicate", $clicker_id);
                    }
                    catch (ClickerIdInvalidException $e) {
                        if (ClickerIdInvalidException::F_EMPTY == $e->type) {
                            $this->addMessage(self::KEY_ERROR, "reg.registered.clickerId.empty");
                        } else {
                            $this->addMessage(self::KEY_ERROR, "reg.registered.clickerId.invalid", $clicker_id);
                        }
                    }
                }
            } else if (optional_param('activate', NULL) != NULL) {
                // First arrived at this page
                $activate = optional_param('activate', 'false', PARAM_RAW);
                $activate = ($activate == 'true' ? true : false);
                $reg_id = optional_param('registrationId', NULL, PARAM_INT);
                if ($reg_id == NULL) {
                    $this->addMessage(self::KEY_ERROR, "reg.activate.registrationId.empty", null);
                } else {
                    // save a new clicker registration
                    $cr = iclicker_service::set_registration_active($reg_id, $activate);
                    if ($cr) {
                        $this->addMessage(self::KEY_INFO, "reg.activate.success.".($cr->activated ? 'true' : 'false'), $cr->clicker_id);
                    }
                }
            } else {
                // invalid POST
                echo("WARN: Invalid POST: does not contain register or activate, nothing to do");
            }
        }
        
        $this->results['regs'] = iclicker_service::get_registrations_by_user();
        $this->results['is_instructor'] = iclicker_service::is_instructor();
        // added to allow special messages below the forms
        $this->results['below_messages'] = $this->getMessages(self::KEY_BELOW);
    }
    
    public function processInstructor() {
        $this->results['instPath'] = iclicker_service::block_url('instructor.php');
        // admin/instructor check
        if (!iclicker_service::is_admin() && !iclicker_service::is_instructor()) {
            throw new SecurityException("Current user is not an instructor and cannot access the instructor view");
        }
        $course_id = optional_param('courseId', false, PARAM_INT);
        $this->results['courseId'] = $course_id;
        if ($course_id) {
            $course = iclicker_service::get_course($course_id);
            $this->results['courseTitle'] = $course->title;
        }
        $courses = iclicker_service::get_courses_for_instructor($course_id);
        $this->results['courses'] = $courses;
        $this->results['coursesCount'] = count($courses);
        $this->results['showStudents'] = false;
        if ($course_id && count($courses) == 1) {
            $course = $courses[0];
            $this->results['showStudents'] = true;
            $this->results['course'] = $course;
            $this->results['students'] = $course->students;
            $this->results['studentsCount'] = count($course->students);
        }
    }
    
    public function processAdmin() {
        $adminPath = iclicker_service::block_url('admin.php');
        $this->results['adminPath'] = $adminPath;
        // admin check
        if (!iclicker_service::is_admin()) {
            throw new SecurityException("Current user is not an admin and cannot access the admin view");
        }
        
        // get sorting params
        $pageNum = 1;
        $perPageNum = 20; // does not change
        if (optional_param('page', NULL) != NULL) {
            $pageNum = required_param('page', PARAM_INT);
            if ($pageNum < 1) {
                $pageNum = 1;
            }
        }
        $this->results['page'] = $pageNum;
        $this->results['perPage'] = $perPageNum;
        $sort = 'clicker_id';
        if (optional_param('sort', NULL) != NULL) {
            $sort = required_param('sort', PARAM_ALPHAEXT);
        }
        $this->results['sort'] = $sort;
        
        if ("POST" == $this->method) {
            if (optional_param('activate', NULL) != NULL) {
                // First arrived at this page
                $activate = required_param('activate', PARAM_BOOL); // @todo use 0/1 on the view
                if (optional_param('registrationId', NULL) == NULL) {
                    $this->addMessage(self::KEY_ERROR, "reg.activate.registrationId.empty", null);
                } else {
                    $reg_id = required_param('registrationId', PARAM_INT);
                    // save a new clicker registration
                    $cr = iclicker_service::set_registration_active($reg_id, $activate);
                    if ($cr) {
                        $args = new stdClass ;
                        $args->cid = $cr->clicker_id;
                        $args->user = iclicker_service::get_user_displayname($cr->owner_id);
                        $this->addMessage(self::KEY_INFO, "admin.activate.success.".($cr->activated ? 'true' : 'false'), $args);
                    }
                }
            } else if (optional_param('remove', NULL) != NULL) {
                if (optional_param('registrationId', NULL) == NULL) {
                    $this->addMessage(self::KEY_ERROR, "reg.activate.registrationId.empty", null);
                } else {
                    $reg_id = required_param('registrationId', PARAM_INT);
                    $cr = iclicker_service::get_registration_by_id($reg_id);
                    if ($cr) {
                        iclicker_service::remove_registration($reg_id);
                        $args = new stdClass ;
                        $args->cid = $cr->clicker_id;
                        $args->rid = $cr->registration_id;
                        $args->user = iclicker_service::get_user_displayname($cr->owner_id);
                        $this->addMessage(self::KEY_INFO, "admin.delete.success", $args);
                    }
                }
            } else if (optional_param('runner', NULL) != NULL) {
                // initiate the runner process
                throw new Exception("NOT IMPLEMENTED"); // @todo
            } else {
                // invalid POST
                error('WARN: Invalid POST: does not contain runner, remove, or activate, nothing to do');
            }
        }
        
        // put config data into page
        $this->results['useNationalWebservices'] = iclicker_service::$use_national_webservices;
        $this->results['domainURL'] = iclicker_service::$domain_URL;
        $this->results['disableSyncWithNational'] = iclicker_service::$disable_sync_with_national;
        $this->results['webservicesNationalSyncHour'] = iclicker_service::$webservices_national_sync_hour;
        
        // put error data into page
        $this->results['recent_failures'] = array(
        ); // FIXME - not real yet
        
        // @todo put runner status in page
        $this->results['runner_exists'] = false;
        $this->results['runner_type'] = NULL;
        $this->results['runner_percent'] = 0;
        
        // handling the calcs for paging
        $first = ($pageNum - 1) * $perPageNum;
        $totalCount = iclicker_service::count_all_registrations();
        $pageCount = floor(($totalCount + $perPageNum - 1) / $perPageNum);
        $this->results['total_count'] = $totalCount;
        $this->results['page_count'] = $pageCount;
        $this->results['registrations'] = iclicker_service::get_all_registrations($first, $perPageNum, $sort, NULL);
        
        $pagerHTML = "";
        if ($totalCount > 0) {
            $timestamp = microtime();
            for ($i = 0; $i < $pageCount; $i++) {
                $currentPage = $i + 1;
                $currentStart = $currentPage + ($i * $perPageNum);
                $currentEnd = $currentStart + $perPageNum - 1;
                if ($currentEnd > $totalCount) {
                    $currentEnd = $totalCount;
                }
                $marker = '['.$currentStart.'..'.$currentEnd.']';
                if ($currentPage == $pageNum) {
                    // make it bold and not a link
                    $pagerHTML .= '<span class="paging_current paging_item">'.$marker.'</span>'."\n";
                } else {
                    // make it a link
                    $pagerHTML .= '<a class="paging_link paging_item" href="'.$adminPath.'&page='.$currentPage.'&sort='.$sort.'&nc='.($timestamp.$currentPage).'">'.$marker.'</a>'."\n";
                }
            }
            $this->results['pagerHTML'] = $pagerHTML;
        }
    }

    
    // MESSAGING
    
    var $messages = array(
    );
    
    const KEY_INFO = "INFO";
    const KEY_ERROR = "ERROR";
    const KEY_BELOW = "BELOW";
    
    /**
     * Adds a message
     *
     * @param string $key the KEY const
     * @param string $message the message to add
     */
    public function addMessageStr($key, $message) {
        if ($key == null) {
            throw new Exception("key (".$key.") must not be null");
        }
        if ($message) {
            if (!isset($this->messages[$key])) {
                $this->messages[$key] = array(
                );
            }
            $this->messages[$key][] = $message;
        }
    }
    
    /**
     * Add an i18n message based on a key
     *
     * @param string $key the KEY const
     * @param string $messageKey the i18n message key
     * @param object $args [optional] args to include
     */
    public function addMessage($key, $messageKey, $args = NULL) {
        if ($key == null) {
            throw new Exception("key (".$key.") must not be null");
        }
        if ($messageKey) {
            $message = iclicker_service::msg($messageKey, $args);
            $this->addMessageStr($key, $message);
        }
    }
    
    /**
     * Get the messages that are currently waiting in this request
     *
     * @param string $key the KEY const
     * @return the list of messages to display
     */
    public function getMessages($key) {
        if ($key == null) {
            throw new Exception("key (".$key.") must not be null");
        }
        $messages = NULL;
        if (isset($this->messages[$key])) {
            $messages = $this->messages[$key];
            if (!isset($messages)) {
                $messages = array(
                );
            }
        } else {
            $messages = array(
            );
        }
        return $messages;
    }
    
}
?>
