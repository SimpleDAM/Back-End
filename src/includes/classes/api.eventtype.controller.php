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

class EventTypeController extends ApiBaseController
{
	
    /**
     * "/eventtype/list" Endpoint - Get list of event types
     */
    public function listAction()
    {

        $strErrorDesc = '';
		$strErrorCode = 0;
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $arrQueryStringParams = $this->getQueryStringParams();
		$filters = NULL;
		
        if (strtoupper($requestMethod) == 'GET') {
		
			$usermodel = new UserModel();
			$user = $usermodel->getUserFromSession($arrQueryStringParams['sessiontoken']);
		
			// This is an admin only event - kick users out if they attempt this
			if ($user[0]["userroleid"] < 2){
				$this->sendOutput(json_encode(array('error' => -1,'description'=>'Insufficient Privileges')));
			}
			
            try {
                $model = new EventTypeModel();

				$intStart = 0;
                if (isset($arrQueryStringParams['start']) && $arrQueryStringParams['start']) {
                    $intStart = $arrQueryStringParams['start'];
                }
			
                $intLimit = 30;
                if (isset($arrQueryStringParams['limit']) && $arrQueryStringParams['limit']) {
                    $intLimit = $arrQueryStringParams['limit'];
                }
				
				$intSort = "eventtypeid";
                if (isset($arrQueryStringParams['sort']) && $arrQueryStringParams['sort']) {
                    $intSort = $arrQueryStringParams['sort'];
                }
				// Allow use of id - translate to eventid
				$intSort = ($intSort == "id") ? "eventtypeid" : $intSort;
				
				$intDir = "asc";
                if (isset($arrQueryStringParams['dir']) && $arrQueryStringParams['dir']) {
                    $intDir = $arrQueryStringParams['dir'];
                }
				
				if (isset($arrQueryStringParams['q']) && $arrQueryStringParams['q']) {
                    $filters["q"] = $arrQueryStringParams['q'];
                }
				
				// Get total amount of assets
				$numEventTypes = $model->listEventTypes($intStart,$intLimit,$intSort,$intDir,$filters,true);

                $arr = $model->listEventTypes($intStart,$intLimit,$intSort,$intDir,$filters);
				
				$data = [];
				$data["total"] = count($numEventTypes);
				$data["eventtypes"] = $arr;
				
            } catch (Error $e) {
				$strErrorCode = -1;
                $strErrorDesc = $e->getMessage().' Something went wrong! Please contact support.';
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
	
	// Get single event type
	public function getAction()
    {
	
        $strErrorDesc = '';
		$strErrorCode = 0;
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $arrQueryStringParams = $this->getQueryStringParams();
		
        if (strtoupper($requestMethod) == 'GET') {
		
			if (!isset($arrQueryStringParams['id']) || empty($arrQueryStringParams['id'])){
				$this->sendOutput(json_encode(array('error' => -1,'description'=>'Parameter Missing')));
			}
			
            try {
                $model = new EventTypeModel();

                $id = $arrQueryStringParams['id'];
				
                $arr = $model->getEventType($id);
				
				if (count($arr) < 1){
					$strErrorCode = -1;
					$strErrorDesc = 'Event type not found';
            		$strErrorHeader = 'HTTP/1.1 200 OK';
				} else {
                	$data = $arr[0];
					
				}
            } catch (Error $e) {
				$strErrorCode = -1;
                $strErrorDesc = $e->getMessage().' Something went wrong! Please contact support.';
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
	
	// Add an event type
	public function addAction(){

		$arrPostParams = $this->getPostParams();
		$requestMethod = $_SERVER["REQUEST_METHOD"];
		$strErrorDesc = '';
		$strErrorCode = 0;
		$data = [];
		
		if (strtoupper($requestMethod) == 'POST') {
		
			// Get user for event audit
			$usermodel = new UserModel();
			$user = $usermodel->getUserFromSession($arrPostParams['sessiontoken']);
			$calling_userid = $user[0]['userid'];
			$calling_username = $user[0]['fullname'];
		
			// This is an admin only event - kick users out if they attempt this
			if ($user[0]["userroleid"] < 2){
				$this->sendOutput(json_encode(array('error' => -1,'description'=>'Insufficient Privileges')));
			}

			if ( empty($arrPostParams['eventtypename']) ){
				$this->sendOutput(json_encode(array('error' => -1,'description'=>'Parameter(s) Missing')));
			}
		
			try {
				$model = new EventTypeModel();
	
				$eventtypename = $arrPostParams['eventtypename'];			
				$result = $model->addEventType($eventtypename);
				
				// Errors
				if (!$result){
					$strErrorCode = -1;
					$strErrorDesc = 'Event type could not be added';
					$strErrorHeader = 'HTTP/1.1 200 OK';
				} else {
					$data["eventtypeid"] = $result;
				}
			} catch (Error $e) {
				$strErrorCode = -1;
				$strErrorDesc = $e->getMessage().' Something went wrong! Please contact support.';
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
	
	// Update single event type
	public function updateAction() {
		
        $strErrorDesc = '';
		$strErrorCode = 0;
        $requestMethod = $_SERVER["REQUEST_METHOD"];
		$arrPostParams = $this->getPostParams();
		$data = [];
		
        if (strtoupper($requestMethod) == 'POST') {
		
			// Get user for event audit
			$model = new UserModel();		
			$user = $model->getUserFromSession($arrPostParams['sessiontoken']);
			$calling_userid = $user[0]['userid'];
			$calling_username = $user[0]['fullname'];
		
			// This is an admin only method - prevent other users
			if ($user[0]["userroleid"] < 2){
				$this->sendOutput(json_encode(array('error' => -1,'description'=>'Insufficient Privileges')));
			}
			
			if (empty($arrPostParams['id']) || empty($arrPostParams['eventtypename']) ){
				$this->sendOutput(json_encode(array('error' => -1,'description'=>'Parameter(s) Missing')));		
			}
			
            try {
				
				$model = new EventTypeModel();
				$id = $arrPostParams['id'];
				$eventtypename = $arrPostParams['eventtypename'];		

                $arr = $model->updateEventType($id,$eventtypename);

				if (!$arr){
					$strErrorCode = -1;
					$strErrorDesc = 'Could not update event type';
            		$strErrorHeader = 'HTTP/1.1 200 OK';
				} else {
					$data["eventtypeid"] = $id;
				}
				
            } catch (Error $e) {
				$strErrorCode = -1;
                $strErrorDesc = $e->getMessage().' Something went wrong! Please contact support.';
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
	
	/**
     * Endpoint - Delete event type
	 *
     */
	public function deleteAction(){
		
		$strErrorDesc = '';
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $arrPostParams = $this->getPostParams();
		$strErrorCode = 0;
		$data = [];

		// POST method
		if (strtoupper($requestMethod) == 'POST') {
		
			// Get user for event audit
			$usermodel = new UserModel();
			$user = $usermodel->getUserFromSession($arrPostParams['sessiontoken']);
			$calling_userid = $user[0]['userid'];
			$calling_username = $user[0]['fullname'];
			
			if ($user[0]["userroleid"] < 2){
				$this->sendOutput(json_encode(array('error' => -1,'description'=>'Insufficient Privileges')));
			}
			
			if (empty($arrPostParams['id']) || empty($arrPostParams['id']) ){
				$this->sendOutput(json_encode(array('error' => -1,'description'=>'Parameter(s) Missing')));
			}
			
            try {
				
				// User to be deleted
				$id = $arrPostParams['id'];
				$id = (int)$id;
				
				// User to be deleted
				$model = new EventTypeModel();
                $result = $model->deleteEventType($id);
						
				if (!$result){
					$strErrorCode = -1;
					$strErrorHeader = 'The event type could not be deleted';
				} else {
					$data["eventtypeid"] = $id;
				}
			
			} catch (Error $e) {
				$strErrorCode = -1;
				$strErrorDesc = $e->getMessage().' Something went wrong! Please contact support.';
				$strErrorHeader = 'HTTP/1.1 200 OK';
			}
		} else {
			$strErrorCode = -1;
            $strErrorDesc = 'Method not supported';
            $strErrorHeader = 'HTTP/1.1 200 OK';
        }
		
		if (!$strErrorDesc) {
            $this->sendOutput(
				json_encode(array('error' => $strErrorCode,'description'=>'success','data'=>$data)),
                array('Content-Type: application/json', 'HTTP/1.1 200 OK')
            );
        } else {
            $this->sendOutput(json_encode(array('error' => $strErrorCode,'description'=>$strErrorDesc)), 
                array('Content-Type: application/json', 'HTTP/1.1 200 OK')
            );
        }
		
	}
}

?>