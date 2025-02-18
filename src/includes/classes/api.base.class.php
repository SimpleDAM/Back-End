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

class ApiBaseController
{
	
    /**
     * __call magic method
     */
    public function __call($name, $arguments)
    {
        $this->sendOutput('', array('HTTP/1.1 404 Not Found'));
    }

    /**
     * Get URI elements
     * 
     * @return array
     */
    protected function getUriSegments()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = explode( '/', $uri );
        return $uri;
    }

    /**
     * Get querystring params
     * 
     * @return array
     */
    protected function getQueryStringParams()
    {
		parse_str($_SERVER['QUERY_STRING'],$qry);
		return $qry;
    }
	
	/**
     * Get POST params
     * 
     * @return array
     */
    protected function getPostParams()
    {
		return $_POST;
    }

    /**
     * Send API output - this now contains the API audit logging function
     *
     * @param json $data - the JSON encoded output from the API call
     * @param array $httpHeader - an array of headers to inject into the final response
	 * @param string $filepath - if present, the API will prompt a download (e.g /api/asset/download)
     */
    public function sendOutput($data, $httpHeaders=array(), $filepath=NULL)
    {
        header_remove('Set-Cookie');
		
		// Call audit logging function
		$this->storeAPIAuditTrail($data);
		
		// To allow remote access to the API from another website, add the URL here
		// header('Access-Control-Allow-Origin: https://www.yourremotewebsite.com');
		
        if (is_array($httpHeaders) && count($httpHeaders)) {
            foreach ($httpHeaders as $httpHeader) {
                header($httpHeader);
            }
        } else {
			header('Content-Type: application/json');
			header('HTTP/1.1 200 OK');
		}
		
		// If a file, trigger a download
		if (!is_null($filepath) && file_exists($filepath)){
			session_write_close();
			readfile($filepath);
			exit();
		} else {
			// Output the final API response
        	echo $data;
		}
        exit;
    }
	
	/**
     * Store API Audit trail
     *
	 * @param json $passthrudata - the JSON encoded output from the API call
     */
    public function storeAPIAuditTrail($passthrudata=NULL)
    {
		
		// Get the POST/GET parameters again (for reference)
		if ($_SERVER['REQUEST_METHOD'] == "GET"){
			$api_params = $_GET;
		} else {
			$api_params = $_POST;
		}
		
		// Bail out if the action is on our blacklist
		if (in_array($_GET["action"],API_LOG_ACTIONS_TO_IGNORE) || in_array($_GET["entity"],API_LOG_ENTITIES_TO_IGNORE)){
			return;
		}
		// Decode the API response JSON back into an array
		$api_response_ary = json_decode($passthrudata,true);
		
		// Get user for event audit
		$usermodel = new UserModel();
		$user = $usermodel->getUserFromSession($api_params['sessiontoken']);
		$calling_userid = $user[0]['userid'];
		$calling_username = $user[0]['fullname'];
		
		// Values for the audit trail
		$model = new EventTypeModel();
		
		// Construct the event name from entity and action (e.g. 'asset update' or 'user list')
		$eventname = $_GET['entity'] . " " . $_GET["action"];
		
		// Get the event type id from the above name
		$eventtypeid = $model->getEventTypeIDFromName($eventname);
		
		// If no event is found with a matching name, use 0 (zero) as it's easy to identify
		if (is_array($eventtypeid) && count($eventtypeid) == 1){
			$eventtypeid = $eventtypeid[0]["eventtypeid"];
		} else {
			$eventtypeid = 0;
		}

		// If the entity was asset, we should have been passed an asset ID, except upload, which creates a new asset ID
		$upload = false;
		if($_GET['entity'] == "asset" && $_GET['action'] == "add"){
			$assetid = $api_response_ary["data"]["assetid"];
			if (isset($api_response_ary["data"]["filename"]) && isset($api_response_ary["data"]["filesize"])) {
				// File was uploaded - get the asset ID from the /api/asset/add passthru response
				$filename = $api_response_ary["data"]["filename"];
				$filesize = $api_response_ary["data"]["filesize"];
				$upload = true;
			}
		} else if($_GET['entity'] == "asset" && $_GET['action'] == "update"){
			$assetid = isset($api_params['id']) ? $api_params['id'] : NULL;
			if (isset($api_response_ary["data"]["filename"]) && isset($api_response_ary["data"]["filesize"])) {
				// File was uploaded - get the asset ID from the /api/asset/update passthru response
				$filename = $api_response_ary["data"]["filename"];
				$filesize = $api_response_ary["data"]["filesize"];
				$upload = true;
			}
		} else {
			$assetid = ($_GET['entity'] == "asset" && isset($api_params['id'])) ? $api_params['id'] : NULL;
		}
		// If metadata was posted, add it to the event details for reference
		if (isset($api_params['metadata']) && !empty($api_params['metadata']) && !is_null($api_params['metadata'])){
			// Metadata is always posted in JSON format, so we need to decode it
			$metadata_ary = json_decode($api_params['metadata'],true);
			$metadata = json_encode($metadata_ary,JSON_UNESCAPED_SLASHES);
			if ($upload){
				$eventdetails = $calling_username . " " . $_GET['entity'] . " " . $_GET["action"] . " (asset ID: $assetid, filename: $filename, size: $filesize) Metadata: " . $metadata;
			} else {
				$eventdetails = $calling_username . " " . $_GET['entity'] . " " . $_GET["action"] . " (asset ID: $assetid) Metadata: " . $metadata;
			}
		} else {
			if ($_GET['action'] == "login"){
				$calling_username = $api_response_ary["data"]["user"]["firstname"]. " " . $api_response_ary["data"]["user"]["lastname"];
				$eventdetails = $calling_username . " " . $_GET['entity'] . " " . $_GET["action"];
				$calling_userid = $api_response_ary["data"]["user"]["userid"];
			} else {
				$eventdetails = $calling_username . " " . $_GET['entity'] . " " . $_GET["action"];
			}
			// Include the asset ID in the event details string
			if (!is_null($assetid)){
				if ($upload){
					$eventdetails .= " (asset ID: $assetid, filename: $filename, size: $filesize)";
				} else {
					$eventdetails .= " (asset ID: $assetid)";
				}
			}
		}
		
		// Finally, write the event to the audit trail
		$model = new EventModel();
		$result = $model->addEvent($eventtypeid,$calling_userid,$eventdetails,$assetid,$api_response_ary);
    }
	
}

?>