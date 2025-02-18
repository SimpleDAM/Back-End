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

class EventController extends ApiBaseController
{
	
    /**
     * "/event/list" Endpoint - Get list of events
     */
    public function listAction()
    {
	
        $strErrorDesc = '';
		$strErrorCode = 0;
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $arrQueryStringParams = $this->getQueryStringParams();
		$filters = NULL;
		
        if (strtoupper($requestMethod) == 'GET') {
		
			// Get user
			$usermodel = new UserModel();
			$user = $usermodel->getUserFromSession($arrQueryStringParams['sessiontoken']);
		
			// This is an admin only event - kick users out if they attempt this
			if ($user[0]["userroleid"] < 2){
				$this->sendOutput(json_encode(array('error' => -1,'description'=>'Insufficient Privileges')));
			}
			
            try {
                $model = new EventModel();

				$intStart = 0;
                if (isset($arrQueryStringParams['start']) && $arrQueryStringParams['start']) {
                    $intStart = $arrQueryStringParams['start'];
                }
			
                $intLimit = 10;
                if (isset($arrQueryStringParams['limit']) && $arrQueryStringParams['limit']) {
                    $intLimit = $arrQueryStringParams['limit'];
                }
				
				$intSort = "eventid";
                if (isset($arrQueryStringParams['sort']) && $arrQueryStringParams['sort']) {
                    $intSort = $arrQueryStringParams['sort'];
                }
				// Allow use of id - translate to eventid
				$intSort = ($intSort == "id") ? "eventid" : $intSort;
				
				$intDir = "asc";
                if (isset($arrQueryStringParams['dir']) && $arrQueryStringParams['dir']) {
                    $intDir = $arrQueryStringParams['dir'];
                }
				
				// Event type
				if (isset($arrQueryStringParams['t']) && $arrQueryStringParams['t']) {
                    $filters["t"] = $arrQueryStringParams['t'];
                }
				
				// Keyword
				if (isset($arrQueryStringParams['q']) && $arrQueryStringParams['q']) {
                    $filters["q"] = $arrQueryStringParams['q'];
                }
				
				// Asset ID
				if (isset($arrQueryStringParams['id']) && $arrQueryStringParams['id']) {
                    $filters["id"] = $arrQueryStringParams['id'];
                }
				
				// User ID
				if (isset($arrQueryStringParams['userid']) && $arrQueryStringParams['userid']) {
                    $filters["userid"] = $arrQueryStringParams['userid'];
                }
				
				// From Date
				if (isset($arrQueryStringParams['from']) && $arrQueryStringParams['from']) {
                    $filters["from"] = $arrQueryStringParams['from'];
                }
				
				// To Date
				if (isset($arrQueryStringParams['to']) && $arrQueryStringParams['to']) {
                    $filters["to"] = $arrQueryStringParams['to'];
                }
				
				// Format - csv, json or apicalls (cURL)
				if (isset($arrQueryStringParams['format']) && $arrQueryStringParams['format']) {
                    $filters["format"] = $arrQueryStringParams['format'];
                }
				
				// If the user has requested a downloadable format, get ALL records
				if (isset($filters["format"]) && !empty($filters["format"])){
					if (strtolower($filters["format"]) == "csv" || strtolower($filters["format"]) == "json" || strtolower($filters["format"]) == "apicalls"){
						$intStart = NULL;
						$intLimit = NULL;
					}
				}
				
				// Get total amount of assets
				$numEvents = $model->listEvents($intStart,$intLimit,$intSort,$intDir,$filters,true);

				// Get the actual events
                $arr = $model->listEvents($intStart,$intLimit,$intSort,$intDir,$filters);
				
				$data = [];
				$data["total"] = count($numEvents);
				$data["events"] = $arr;
				
            } catch (Error $e) {
				$strErrorCode = -1;
                $strErrorDesc = $e->getMessage().'Something went wrong! Please contact support.';
                $strErrorHeader = 'HTTP/1.1 200 OK';
            }
        } else {
			$strErrorCode = -1;
            $strErrorDesc = 'Method not supported';
            $strErrorHeader = 'HTTP/1.1 200 OK';
        }

        // Send output
        if (!$strErrorDesc) {
			if (isset($filters["format"]) && !empty($filters["format"])){
				if (strtolower($filters["format"]) == "csv"){
					// $data["events"]
					header('Content-Type: application/csv');
					header('Content-Disposition: attachment; filename="simpledam_events_export.csv";');
					$fp = fopen('php://output', 'w');
					// Column headers
					$keys = array_keys($data["events"][0]);
    				fputcsv($fp, $keys);
					foreach ($data["events"] as $line) {
						fputcsv($fp, $line);
					}
					exit();
				}
				if (strtolower($filters["format"]) == "json"){
					// $data["events"]
					header('Content-Type: application/json');
					header('Content-Disposition: attachment; filename="simpledam_events_export.json";');
					// Make sure the request/response values are well-formatted JSON
					$new_events = [];
					foreach($data["events"] as $event){
						// Convert request/response from JSON back to an array
						$event["apirequest"] = json_decode($event["apirequest"],true);
						$event["apiresponse"] = json_decode($event["apiresponse"],true);
						$new_events[] = $event;
					}
					$data["events"] = $new_events;
					// Encode the whole lot
					$data = json_encode($data,JSON_UNESCAPED_SLASHES);
					$data = preg_replace('/\\\\{2,}/', '\\', $data);
					echo $data;
					exit();
				}
				if (strtolower($filters["format"]) == "apicalls"){
					// $data["events"]
					header('Content-Type: text/plain');
					header('Content-Disposition: attachment; filename="simpledam_events_export.txt";');
					//$fp = fopen('php://output', 'w');
					//fwrite($fp, $data);
					foreach ($data["events"] as $line) {
						if ($line["apimethod"] == "GET"){
							$cmd = 'curl -X GET "'.SITE_URL.$line["apiurl"].'&sessiontoken='.$arrQueryStringParams['sessiontoken'].'" -w "\n"'."\n";
						} else {
							$request_ary = json_decode($line["apirequest"],true);
							$metadata = json_encode($request_ary["metadata"]);
							unset($request_ary["metadata"]);
							$cmd = 'curl -X POST -d \'sessiontoken='.$arrQueryStringParams['sessiontoken'].'&'.http_build_query($request_ary).'&metadata='.$metadata.'\' "'.SITE_URL.$line["apiurl"].'" -w "\n"'."\n";
						}
						echo $cmd;
					}	
					exit();
				}
			}
            $this->sendOutput(
				json_encode(array('error' => $strErrorCode,'description'=>'success','data'=>$data)),
                array('Content-Type: application/json', 'HTTP/1.1 200 OK')
            );
        } else {
            $this->sendOutput(json_encode(array('error' => $strErrorCode,'description'=>$strErrorDesc)), 
                array('Content-Type: application/json', $strErrorHeader)
            );
        }
    }
	
	// Get single event
	public function getAction()
    {
	
        $strErrorDesc = '';
		$strErrorCode = 0;
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $arrQueryStringParams = $this->getQueryStringParams();

        if (strtoupper($requestMethod) == 'GET') {
		
			// Get user
			$usermodel = new UserModel();
			$user = $usermodel->getUserFromSession($arrQueryStringParams['sessiontoken']);
		
			// This is an admin only event - kick users out if they attempt this
			if ($user[0]["userroleid"] < 2){
				$this->sendOutput(json_encode(array('error' => -1,'description'=>'Insufficient Privileges')));
			}
		
			if (!isset($arrQueryStringParams['id']) || empty($arrQueryStringParams['id'])){
				$this->sendOutput(json_encode(array('error' => -1,'description'=>'Parameter Missing')));
			}
				
            try {
                $model = new EventModel();

                if (isset($arrQueryStringParams['id']) && $arrQueryStringParams['id']) {
                    $id = $arrQueryStringParams['id'];
                }
				if (isset($arrQueryStringParams['eventid']) && $arrQueryStringParams['eventid']) {
                    $id = $arrQueryStringParams['eventid'];
                }

                $arr = $model->getEvent($id);
				
				if (count($arr) < 1){
					$strErrorCode = -1;
					$strErrorDesc = 'Event not found';
            		$strErrorHeader = 'HTTP/1.1 200 OK';
				} else {
                	$data = $arr[0];
									
					// Obfuscate actual assetid if it exists
					if (isset($data["assetid"])){
						$assetModel = new AssetModel();
						$data["assetid"] = $assetModel->getAssetIDHash($data["assetid"])[0]["publicassetid"];
					}
					
				}
            } catch (Error $e) {
				$strErrorCode = -1;
                $strErrorDesc = $e->getMessage().'Something went wrong! Please contact support.';
                $strErrorHeader = 'HTTP/1.1 200 OK';
            }
        } else {
			$strErrorCode = -1;
            $strErrorDesc = 'Method not supported';
            $strErrorHeader = 'HTTP/1.1 200 OK';
        }

        // Send output
        if (!$strErrorDesc) {
            $this->sendOutput(
				json_encode(array('error' => $strErrorCode,'description'=>'success','data'=>$data)),
                array('Content-Type: application/json', 'HTTP/1.1 200 OK')
            );
        } else {
            $this->sendOutput(json_encode(array('error' => $strErrorCode,'description'=>$strErrorDesc)), 
                array('Content-Type: application/json', $strErrorHeader)
            );
        }
    }
	
	// Add an audit event - parameters: eventypeid, userid, details, assetid, response
	private function addAction($params){
	
		$strErrorDesc = '';
		$strErrorCode = 0;
		$data = [];
		
		if ( !isset($params['eventtypeid']) || empty($params['userid']) ){
			return false;
		}
	
		try {
			// Add the event
			$model = new EventModel();
			$eventtypeid = $params['eventtypeid'];
			$userid = (!isset($params['userid'])) ? NULL : $params['userid'];
			$assetid = (!isset($params['assetid'])) ? NULL : $params['assetid'];
			$eventip = $_SERVER['REMOTE_ADDR'];
			$eventdetails = (!isset($params['eventdetails'])) ? NULL : $params['eventdetails'];
			$response = (!isset($params['response'])) ? NULL : $params['response'];

			$result = $model->addEvent($eventtypeid,$userid,$eventdetails,$assetid,$response);
			
			if (!$result){
				$strErrorCode = -1;
				$strErrorDesc = 'Event could not be added';
				$strErrorHeader = 'HTTP/1.1 200 OK';
			} else {		
				$data = $arr[0];
			}
		} catch (Error $e) {
			$strErrorCode = -1;
			$strErrorDesc = $e->getMessage().'Something went wrong! Please contact support.';
			$strErrorHeader = 'HTTP/1.1 200 OK';
		}
		
		// Send output
        if (!$strErrorDesc) {
            $this->sendOutput(
				json_encode(array('error' => $strErrorCode,'description'=>'success','data'=>$data)),
                array('Content-Type: application/json', 'HTTP/1.1 200 OK')
            );
        } else {
            $this->sendOutput(json_encode(array('error' => $strErrorCode,'description'=>$strErrorDesc)), 
                array('Content-Type: application/json', $strErrorHeader)
            );
        }

	}
}

?>