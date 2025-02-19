<?php
/*
This software is released under the BSD-3-Clause License

Copyright 2025 Daydream Interactive Limited

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.

2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and or other materials provided with the distribution.

3. Neither the name of the copyright holder nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

/*
This is the main entry point for the Simple DAM API
*/

// Uncomment the lines below to enable troubleshooting - should only be done in dev environments
//error_reporting(E_ALL);
//ini_set('display_errors', '1');

// Include the API settings file
require(__DIR__ . '/../../includes/classes/api.settings.php');

// Include the base controller file
require_once INCLUDE_PATH . "/classes/api.base.class.php";

// Include the utility class
// This is static, so doesn't need instantiating, call using:
// Utils::debug("Log message here");
require_once INCLUDE_PATH . "/classes/api.utility.class.php";

// Include the controllers
require_once INCLUDE_PATH . "/classes/api.asset.controller.php";
require_once INCLUDE_PATH . "/classes/api.user.controller.php";
require_once INCLUDE_PATH . "/classes/api.userrole.controller.php";
require_once INCLUDE_PATH . "/classes/api.event.controller.php";
require_once INCLUDE_PATH . "/classes/api.eventtype.controller.php";

// Include the models
require_once INCLUDE_PATH . "/classes/api.database.model.php";
require_once INCLUDE_PATH . "/classes/api.asset.model.php";
require_once INCLUDE_PATH . "/classes/api.user.model.php";
require_once INCLUDE_PATH . "/classes/api.userrole.model.php";
require_once INCLUDE_PATH . "/classes/api.event.model.php";
require_once INCLUDE_PATH . "/classes/api.eventtype.model.php";

// Whitelist of allowed entities and actions
$allowed_entities = array("asset","category","tag","user","userrole","event","eventtype");
$allowed_actions = array("get","list","add","update","delete","login","logout","checksession","download","import","export","view","embed","preview","thumbnail");

// Enumerate /entity/action from URL (URLS are rewritten by the .htaccess file in htdocs/webroot)
$params = [];
foreach($_GET as $key=>$val){
	if (is_array($val)){
		$params[$key] = array_map(function($item) {
			return stripslashes(strip_tags($item));
		}, $val);
	} else {
		$params[$key] = stripslashes(strip_tags($val));
	}
}

// Instantiate API base controller
$controller = new ApiBaseController();

// Initial checks that entity and action are present in querystring (this has been rewritten using .htaccess rules)
if (!isset($_GET['entity']) || empty($_GET['entity'])){
	$strErrorCode = -1;
	$strErrorDesc = 'Entity parameter missing';
    $strErrorHeader = 'HTTP/1.1 400 Bad Request';
	$controller->sendOutput(json_encode(array('error' => $strErrorCode,'description'=>$strErrorDesc)), 
    	array('Content-Type: application/json', $strErrorHeader)
    );
}
if (!isset($_GET['action']) || empty($_GET['action'])){
	$strErrorCode = -1;
	$strErrorDesc = 'Action parameter missing';
    $strErrorHeader = 'HTTP/1.1 400 Bad Request';
	$controller->sendOutput(json_encode(array('error' => $strErrorCode,'description'=>$strErrorDesc)), 
    	array('Content-Type: application/json', $strErrorHeader)
    );
}

// Check that entity and action are in whitelists
if (!in_array($_GET['entity'],$allowed_entities)){
	$strErrorCode = -1;
	$strErrorDesc = 'Entity not found';
    $strErrorHeader = 'HTTP/1.1 400 Bad Request';
	$controller->sendOutput(json_encode(array('error' => $strErrorCode,'description' =>$strErrorDesc)), 
    	array('Content-Type: application/json', $strErrorHeader)
    );
}
if (!in_array($_GET['action'],$allowed_actions)){
	$strErrorCode = -1;
	$strErrorDesc = 'Action not found';
    $strErrorHeader = 'HTTP/1.1 400 Bad Request';
	$controller->sendOutput(json_encode(array('error' => $strErrorCode,'description'=>$strErrorDesc)), 
    	array('Content-Type: application/json', $strErrorHeader)
    );
}

// Authenticate first of all (only if NOT logging in)
if ($_GET['entity'] == "user" && ($_GET['action'] == "login")){
	// Do nothing - login is the only call that doesn't require a sessiontoken
} else {
	if (empty($_GET['sessiontoken']) && empty($_POST['sessiontoken'])){
		$strErrorCode = -1;
		$strErrorDesc = 'Session token missing';
		$controller->sendOutput(
			json_encode(array('error' => $strErrorCode,'description'=>$strErrorDesc)),
			array('Content-Type: application/json', 'HTTP/1.1 200 OK')
		);
	}
	// Now we've got a session token, make sure it's valid (call the checksession method internally)
	$sessiontoken = isset($_GET['sessiontoken']) ? $_GET['sessiontoken'] : $_POST['sessiontoken'];
	$model = new UserModel();
	$arr = $model->checkSession($sessiontoken);
	if (count($arr) < 1){
		$strErrorCode = -1;
		$strErrorDesc = 'Session token invalid';
		$controller->sendOutput(
			json_encode(array('error' => $strErrorCode,'description'=>$strErrorDesc)),
			array('Content-Type: application/json', 'HTTP/1.1 200 OK')
		);
	}
}

// Concatenate URL's action segment (e.g. 'list') with 'Action' to get controller method name (e.g. listAction)
$strMethodName = $params["action"] . 'Action';

// Instantiate the appropriate controller
if ($params["entity"] == "asset"){
	$controller = new AssetController();
}
if ($params["entity"] == "user"){
	$controller = new UserController();
}
if ($params["entity"] == "userrole"){
	$controller = new UserRoleController();
}
if ($params["entity"] == "event"){
	$controller = new EventController();
}
if ($params["entity"] == "eventtype"){
	$controller = new EventTypeController();
}

// Construct parameters for API transaction log (file-based)
if (API_LOGGING && !in_array($params["action"],API_LOG_ACTIONS_TO_IGNORE)){
	$log_params = "";
	if ($_SERVER["REQUEST_METHOD"] == "POST"){
		foreach ($_POST as $key => $val) {
			$log_params .= "$key=$val&";
		}
		$log_params = trim($log_params,"&");
	} else {
		$log_params = $_SERVER['QUERY_STRING'];
	}
	
	// Get user from result of checkSession(), above
	$log_user = isset($arr) ? $arr[0]["userid"] : "NA";
	$log_sessiontoken = isset($arr) ? $arr[0]["sessiontoken"] : "NA";
	Utils::logapi("url: ".$_SERVER["REQUEST_URI"].", method: ".$_SERVER["REQUEST_METHOD"].", parameters: $log_params, userid: $log_user, sessiontoken: $log_sessiontoken");
}

// Call the controller method and output the result
$controller->{$strMethodName}();	

?>