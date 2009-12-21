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
 * Handles rendering the form for creating new pages and the submission of the form as well
 * NOTE: table is named iclicker
 */
 
require_once ('../../config.php');
global $CFG, $USER, $COURSE;
require_once ('iclicker_service.php');

class iclicker_controller {

    const TYPE_HTML = 'html';
    const TYPE_XML = 'xml';
    const TYPE_TEXT = 'txt';

    public $method = 'GET';
    public $body = NULL;
    public $headers = NULL;
    public $response = NULL;
    public $results = array();

    var $TIME_START = 0;

    public function __construct($getBody = false) {
        $this->TIME_START = microtime(true);
        $this->headers = array();
        // set some headers
        $this->headers['Content-Encoding'] = 'UTF8';
        //header('Content-type: text/plain');
        //header('Cache-Control: no-cache, must-revalidate');
        $response_data = array(
            'code' => 200,
            'type' => self::TYPE_HTML,
            'message' => ''
        );
        if ($getBody) {
            $this->body = @file_get_contents('php://input');
        }
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->response = $response_data;
    }

    // MESSAGING

    var $messages = array();

    const KEY_INFO          = "INFO";
    const KEY_ERROR         = "ERROR";
    const KEY_BELOW         = "BELOW";

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
            if (! isset($this->messages[$key])) {
                $this->messages[$key] = array();
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
    public function addMessage($key, $messageKey, $args=NULL) {
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
            if (! isset($messages)) {
                $messages = array();
            }
        } else {
            $messages = array();
        }
        return $messages;
    }

    /**
     * Send the response
     *
     * @param string $message [optional] the message to send, defaults to "Invalid request"
     * @param int    $code    [optional] the status code, defaults to 400
     * @param array  $headers [optional] any headers to include when sending the response
     */
    public function sendResponse($message = "Invalid request parameters", $code = 400, $headers = array ()) {
        header("HTTP/1.0 $code ".str_replace("\n", "", $message));
        if ($code >= 400) {
            // force plain text encoding when errors occur
            header('Content-type: text/plain');
        }
        if ( isset ($headers) and ! empty($headers)) {
            foreach ($headers as $key => &$value) {
                header($key.': '.$value, false);
            }
            unset ($value);
        }
        if (! empty($message)) {
            if ($code >= 400) {
                // include the helpful message
                $message .= "\n POST requests are required\n The request must be formed in one of 3 ways:\n ".
                " 1) Including the fields of the post as http form params (required: post_title, post_content)\n ".
                " 2) Including a field 'data' which contains xml with the post data, \n".
                "     Example: <post><post_title>TITLE</post_title><post_content>CONTENT</post_content></post>\n ".
                " 3) Including the data for multiple posts in the body of the request (similar xml format to above but with <posts>...</posts>), \n".
                "     Example: <posts><post><post_title>TITLE1</post_title><post_content>CONTENT</post_content></post><post><post_title>TITLE2</post_title><post_content>CONTENT</post_content></post></posts> \n";
            }
            $time = microtime(true)-$this->TIME_START;
            $message .= "\nTotal processing time: ".round($time, 4)." seconds at ".date("H:i:s")."\n";
            die ($code." ".$message);
        }
    }

    public function processRegistration() {
        // process calls to the registration view
        $this->results["new_reg"] = false;
        $this->results["clicker_id_val"] = "";
        if ( "POST" == $this->method ) {
            if ( optional_param('register', NULL) != NULL ) {
                // we are registering a clicker
                $clicker_id = optional_param('clickerId', NULL, PARAM_RAW);
                if ( $clicker_id == NULL ) {
                    $this->addMessage(self::KEY_ERROR,
                            "reg.registered.clickerId.empty");
                } else {
                    $this->results["clicker_id_val"] = $clicker_id;
                    // save a new clicker registration
                    try {
                        iclicker_service::create_clicker_registration($clicker_id);
                        $this->addMessage(self::KEY_INFO,
                                "reg.registered.success", $clicker_id);
                        $this->addMessage(self::KEY_BELOW,
                                "reg.registered.below.success");
                        $this->results["new_reg"] = true;
                    } catch (ClickerRegisteredException $e) {
                        $this->addMessage(self::KEY_ERROR,
                                "reg.registered.clickerId.duplicate", $clicker_id);
                        $this->addMessage(self::KEY_BELOW,
                                "reg.registered.below.duplicate", $clicker_id);
                    } catch (ClickerIdInvalidException $e) {
                        if (ClickerIdInvalidException::F_EMPTY == $e->type) {
                            $this->addMessage(self::KEY_ERROR,
                                    "reg.registered.clickerId.empty");
                        } else {
                            $this->addMessage(self::KEY_ERROR,
                                    "reg.registered.clickerId.invalid", $clicker_id);
                        }
                    }
                }
            } else if ( optional_param('activate', NULL) != NULL ) {
                // First arrived at this page
                $activate = optional_param('activate', 'false', PARAM_RAW);
                $activate = ($activate == 'true' ? true : false);
                $reg_id = optional_param('registrationId', NULL, PARAM_INT);
                if ( $reg_id == NULL) {
                    $this->addMessage(self::KEY_ERROR,
                            "reg.activate.registrationId.empty", null);
                } else {
                    // save a new clicker registration
                    $cr = iclicker_service::set_registration_active($reg_id, $activate);
                    if ($cr) {
                        $this->addMessage(self::KEY_INFO,
                                "reg.activate.success.".($cr->activated ? 'true' : 'false'), 
                                $cr->clicker_id);
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

/*
    public function processInstructor(PageContext HttpServletRequest request) {
        // admin/instructor check
        if (! this.isAdmin() && ! this.isInstructor()) {
            throw new SecurityException("Current user is not an instructor and cannot access the instructor view");
        }
        String courseId = request.getParameter("courseId");
        $this->results["courseId", courseId );
        if (courseId != null) {
            $this->results["courseTitle", this.getLogic().getCourseTitle(courseId) );
        }
        List<Course> courses = logic.getCoursesForInstructorWithStudents(courseId);
        $this->results["courses", courses );
        $this->results["coursesCount", courses.size());
        $this->results["showStudents", false );
        if (courseId != null && courses.size() == 1) {
            Course course = courses.get(0);
            $this->results["showStudents", true );
            $this->results["course", course );
            $this->results["students", course.students );
            $this->results["studentsCount", course.students.size() );
        }
    }

/*
public void processAdmin(PageContext HttpServletRequest request) {
    // admin check
    if (! this.isAdmin()) {
        throw new SecurityException("Current user is not an admin and cannot access the admin view");
    }

    int pageNum = 1;
    int perPageNum = 20; // does not change
    if ( (request.getParameter("page") != null) ) {
        try {
            pageNum = Integer.parseInt( request.getParameter("page") );
            if (pageNum < 1) { pageNum = 1; }
        } catch (NumberFormatException e) {
            // nothing to do
            System.err.println("WARN: invalid page number: " . request.getParameter("page") . ":" . e);
        }
    }
    $this->results["page", pageNum);
    $this->results["perPage", perPageNum);
    String sort = "clickerId";
    if ( (request.getParameter("sort") != null) ) {
        sort = request.getParameter("sort");
    }
    $this->results["sort", sort);

    if ( "POST".equalsIgnoreCase(request.getMethod()) ) {
        if ( (request.getParameter("activate") != null) ) {
            // First arrived at this page
            boolean activate = Boolean.parseBoolean( request.getParameter("activate") );
            if ( (request.getParameter("registrationId") == null) ) {
                $this->addMessage(self::KEY_ERROR,
                        "reg.activate.registrationId.empty", null);
            } else {
                try {
                    Long registrationId = Long.parseLong( request.getParameter("registrationId") );
                    // save a new clicker registration
                    ClickerRegistration cr = this.getLogic().setRegistrationActive(registrationId, activate);
                    if (cr != null) {
                        $this->addMessage(self::KEY_INFO,
                                "admin.activate.success.".cr.isActivated(), cr.getClickerId(),
                                this.getLogic().getUserDisplayName(cr.getOwnerId()) );
                    }
                } catch (NumberFormatException e) {
                    $this->addMessage(self::KEY_ERROR,
                            "reg.activate.registrationId.nonnumeric", 
                            request.getParameter("registrationId") );
                }
            }
        } else if ( (request.getParameter("remove") != null) ) {
            if ( (request.getParameter("registrationId") == null) ) {
                $this->addMessage(self::KEY_ERROR,
                        "reg.activate.registrationId.empty", null);
            } else {
                try {
                    Long registrationId = Long.parseLong( request.getParameter("registrationId") );
                    ClickerRegistration cr = this.getLogic().getItemById(registrationId);
                    if (cr != null) {
                        this.getLogic().removeItem(cr);
                        $this->addMessage(self::KEY_INFO,
                                "admin.delete.success", cr.getClickerId(), registrationId, 
                                this.getLogic().getUserDisplayName(cr.getOwnerId()) );
                    }
                } catch (NumberFormatException e) {
                    $this->addMessage(self::KEY_ERROR,
                            "reg.activate.registrationId.nonnumeric", 
                            request.getParameter("registrationId") );
                }
            }
        } else if ( (request.getParameter("runner") != null) ) {
            // initiate the runner process
            String runnerType;
            if ( (request.getParameter("addAll") != null) ) {
                runnerType = BigRunner.RUNNER_TYPE_ADD;
            } else if ( (request.getParameter("removeAll") != null) ) {
                runnerType = BigRunner.RUNNER_TYPE_REMOVE;
            } else if ( (request.getParameter("syncAll") != null) ) {
                runnerType = BigRunner.RUNNER_TYPE_SYNC;
            } else {
                throw new IllegalArgumentException("Invalid request type: missing valid parameter");
            }
            try {
                logic.startRunnerOperation(runnerType);
                String msgKey = "admin.process.message." . runnerType;
                $this->addMessage(self::KEY_INFO, msgKey, null );
            } catch (ClickerLockException e) {
                $this->addMessage(self::KEY_ERROR, "admin.process.message.locked", runnerType );
            } catch (IllegalStateException e) {
                $this->addMessage(self::KEY_ERROR, "admin.process.message.locked", runnerType );
            }
        } else {
            // invalid POST
            System.err.println("WARN: Invalid POST: does not contain runner, remove, or activate, nothing to do");
        }
    }

    // put config data into page
    $this->results["useNationalWebservices", logic.useNationalWebservices);
    $this->results["domainURL", logic.domainURL);
    $this->results["workspacePageTitle", logic.workspacePageTitle);
    $this->results["disableSyncWithNational", logic.disableSyncWithNational);
    $this->results["webservicesNationalSyncHour", logic.webservicesNationalSyncHour);

    // put error data into page
    $this->results["recentFailures", logic.getFailures());

    // put runner status in page
    makeRunnerStatus(true);

    // handling the calcs for paging
    int first = (pageNum - 1) * perPageNum;
    int totalCount = this.getLogic().countAllItems();
    int pageCount = (totalCount + perPageNum - 1) / perPageNum;
    $this->results["totalCount", totalCount);
    $this->results["pageCount", pageCount);
    $this->results["registrations", this.getLogic().getAllItems(first, perPageNum, sort, null, true));

    String pagerHTML = "";
    if (totalCount > 0) {
        StringBuilder sb = new StringBuilder();
        Date d = new Date();
        for (int i = 0; i < pageCount; i++) {
            int currentPage = i + 1;
            int currentStart = currentPage + (i * perPageNum);
            int currentEnd = currentStart + perPageNum - 1;
            if (currentEnd > totalCount) {
                currentEnd = totalCount;
            }
            String marker = "[" . currentStart . ".." . currentEnd . "]";
            if (currentPage == pageNum) {
                // make it bold and not a link
                sb.append("<span class=\"paging_current paging_item\">".marker."</span>\n");
            } else {
                // make it a link
                sb.append("<a class=\"paging_link paging_item\" href=\"".pageContext.findAttribute("adminPath")."&page=".currentPage."&sort=".sort."&nc=".(d.getTime().currentPage)."\">".marker."</a>\n");
            }
        }
        pagerHTML = sb.toString();
        $this->results["pagerHTML", pagerHTML);
    }
}

public void makeRunnerStatus(PageContext boolean clearOnComplete) {
    // check for running process and include the info in the page
    BigRunner runner = logic.getRunnerStatus();
    $this->results["runnerExists", runner != null );
    if (runner != null) {
        $this->results["runnerType", runner.getType());
        $this->results["runnerPercent", runner.getPercentCompleted());
        $this->results["runnerComplete", runner.isComplete());
        $this->results["runnerError", runner.isError());
        if (runner.isComplete() && clearOnComplete) {
            // clear the runner since it is completed
            logic.clearRunner();
        }
    } else {
        $this->results["runnerType", "none");
        $this->results["runnerPercent", 100);
        $this->results["runnerComplete", true);
        $this->results["runnerError", false);
    }
}

public String getValidView(String viewParam) {
    String view = VIEW_REGISTRATION;
    if (viewParam != null && !"".equals(viewParam)) {
        if (ArrayUtils.contains(VIEWS, viewParam)) {
            if (userAllowedForView(viewParam)) {
                view = viewParam;
            }
        }
    }
    return view;
}

protected boolean userAllowedForView(String view) {
    boolean allowed = false;
    if (view != null && !"".equals(view)) {
        if (ArrayUtils.contains(VIEWS, view)) {
            String userId = externalLogic.getCurrentUserId();
            if (VIEW_ADMIN.equals(view)) {
                if (externalLogic.isUserAdmin(userId)) {
                    allowed = true;
                }
            } else if (VIEW_INSTRUCTOR.equals(view)) {
                if (externalLogic.isUserAdmin(userId) || externalLogic.isUserInstructor(userId)) {
                    allowed = true;
                }
            } else {
                // everyone allowed on registration view
                allowed = true;
            }
        }
    }
    return allowed;
}
*/

}
?>