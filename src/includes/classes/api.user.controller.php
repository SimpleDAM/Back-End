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

class UserController extends ApiBaseController
{
	// Login
	public function loginAction(){
		$strErrorDesc = '';
		$strErrorCode = 0;
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $arrPostParams = $this->getPostParams();
		$data = array();
		
		if (strtoupper($requestMethod) == 'POST') {
		
			if ( !isset($arrPostParams['email']) || empty($arrPostParams['email']) || empty($arrPostParams['password']) || empty($arrPostParams['password']) ){
				$this->sendOutput(json_encode(array('error' => -1,'description'=>'Parameter(s) Missing')));
			}
			
            try {
                $model = new UserModel();
                if (isset($arrPostParams['email']) && $arrPostParams['email']) {
                    $email = $arrPostParams['email'];
                }
				
				if (isset($arrPostParams['password']) && $arrPostParams['password']) {
                    $password = $arrPostParams['password'];
                }

                $loginArray = $model->loginUser($email,$password);
				
				if (count($loginArray) < 1){
					$strErrorCode = -1;
					$strErrorDesc = 'Incorrect Username or Password';
            		$strErrorHeader = 'HTTP/1.1 200 OK';
				} else {
					$data["user"] = $loginArray[0];
					$userid = $data["user"]["userid"];
					$userroleid = $data["user"]["userroleid"];
					// Insert session into database table
					$createSessionArray = $model->createSession($userid);
					if ($createSessionArray){
						$sessionArray = $model->getLatestSession($userid);
						if (count($sessionArray) > 0){
							$data["session"] = $sessionArray[0];
							// Update last login date
							$update_login_date = $model->updateLastLoginDate($userid);
						}
					}
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
	
	// Log out
	public function logoutAction(){

		$arrQueryStringParams = $this->getQueryStringParams();
		if ( !isset($arrQueryStringParams['sessiontoken']) || empty($arrQueryStringParams['sessiontoken']) ){
			$this->sendOutput(json_encode(array('error' => -1,'description'=>'Parameter(s) Missing')));
		}
			
		$usermodel = new UserModel();
		$user = $usermodel->getUserFromSession($arrQueryStringParams['sessiontoken']);
		$calling_userid = $user[0]['userid'];
		$calling_username = $user[0]['fullname'];

		$this->storeAPIAuditTrail();
		
		// And call the API logout method internally
		$result = $usermodel->logoutUser($arrQueryStringParams['sessiontoken']);
		if ($result){
			// Redirect to index page
			header("Location: /?logout=1");
			exit();
		}
	}
	
	// Check session
	public function checksessionAction(){
	
		$strErrorDesc = '';
		$strErrorCode = 0;
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $arrPostParams = $this->getPostParams();
		$data = [];
		
		if (strtoupper($requestMethod) == 'POST') {
		
			if ( !isset($arrPostParams['sessiontoken']) || empty($arrPostParams['sessiontoken']) ){
				$this->sendOutput(json_encode(array('error' => -1,'description'=>'Parameter(s) Missing')));
			}
			
            try {
                $model = new UserModel();
				
				$sessionid = 0;
                $sessiontoken = $arrPostParams['sessiontoken'];
                $arr = $model->checkSession($sessiontoken);
				
				if (count($arr) < 1){
					$strErrorCode = -1;
					$strErrorDesc = 'Session token invalid';
            		$strErrorHeader = 'HTTP/1.1 200 OK';
				} else {
					$data = $arr[0];
					// Attempt to update the current session's timestamp
					try {
						$model->updateSessionTimestamp($sessiontoken);
					} catch(Error $e){
						$strErrorCode = 1;
						$strErrorDesc = "Could not update the current sessiontoken timestamp (".$e->getMessage().")";
						$strErrorHeader = 'HTTP/1.1 200 OK';
					}
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
     * "/user/list" Endpoint - Get list of users - admin only
     */
    public function listAction()
    {
	
        $strErrorDesc = '';
		$strErrorCode = 0;
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $arrQueryStringParams = $this->getQueryStringParams();
		$filters = NULL;
		
        if (strtoupper($requestMethod) == 'GET') {
		
			// Get user for event audit
			$usermodel = new UserModel();
			$user = $usermodel->getUserFromSession($arrQueryStringParams['sessiontoken']);
			$calling_userid = $user[0]['userid'];
			$calling_username = $user[0]['fullname'];
		
			// This is an admin only event - kick users out if they attempt this
			if ($user[0]["userroleid"] < 2){
				$this->sendOutput(json_encode(array('error' => -1,'description'=>'Insufficient Privileges')));
			}
			
            try {

				$intStart = 0;
                if (isset($arrQueryStringParams['start']) && $arrQueryStringParams['start']) {
                    $intStart = $arrQueryStringParams['start'];
                }
			
                $intLimit = 10;
                if (isset($arrQueryStringParams['limit']) && $arrQueryStringParams['limit']) {
                    $intLimit = $arrQueryStringParams['limit'];
                }
				
				$intSort = "userid";
                if (isset($arrQueryStringParams['sort']) && $arrQueryStringParams['sort']) {
                    $intSort = $arrQueryStringParams['sort'];
                }
				// Allow use of id - translate to userid
				$intSort = ($intSort == "id") ? "userid" : $intSort;
				
				$intDir = "asc";
                if (isset($arrQueryStringParams['dir']) && $arrQueryStringParams['dir']) {
                    $intDir = $arrQueryStringParams['dir'];
                }
				
				if (isset($arrQueryStringParams['r']) && $arrQueryStringParams['r']) {
                    $filters["r"] = $arrQueryStringParams['r'];
                }
				
				if (isset($arrQueryStringParams['q']) && $arrQueryStringParams['q']) {
                    $filters["q"] = $arrQueryStringParams['q'];
                }
				
				$numUsers = $usermodel->listUsers($intStart,$intLimit,$intSort,$intDir,$filters,true);
				
                $arr = $usermodel->listUsers($intStart,$intLimit,$intSort,$intDir,$filters);
				
				$data = [];
				$data["total"] = count($numUsers);
				$data["users"] = $arr;
				
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
	
	// Get single user
	public function getAction()
    {
	
        $strErrorDesc = '';
		$strErrorCode = 0;
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $arrQueryStringParams = $this->getQueryStringParams();

        if (strtoupper($requestMethod) == 'GET') {
		
			// Get user for event audit
			$model = new UserModel();					
			$user = $model->getUserFromSession($arrQueryStringParams['sessiontoken']);
			$calling_userid = $user[0]['userid'];
			$calling_username = $user[0]['fullname'];
		
			if (!isset($arrQueryStringParams['id']) || empty($arrQueryStringParams['id'])){
				$this->sendOutput(json_encode(array('error' => -1,'description'=>'Parameter Missing')));
			}
			
            try {
			
                $id = $arrQueryStringParams['id'];
				
				// Unless the user is an admin, they cannot see other users - kick them out if they try!
				if ($user[0]["userroleid"] < 2){
					if ($calling_userid != $id){
						$this->sendOutput(json_encode(array('error' => -1,'description'=>'Insufficient Privileges')));
					}
				}

                $arrUser = $model->getUser($id);
				
				if (count($arrUser) < 1){
					$strErrorCode = -1;
					$strErrorDesc = 'User not found';
            		$strErrorHeader = 'HTTP/1.1 200 OK';
				} else {
					$data = $arrUser[0];
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
	
	// Add single user
	public function addAction()
    {
		
        $strErrorDesc = '';
		$strErrorCode = 0;
        $requestMethod = $_SERVER["REQUEST_METHOD"];
		$arrPostParams = $this->getPostParams();
		$data = [];
		$password = NULL;
		$password2 = NULL;
		
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
			
			if (empty($arrPostParams['firstname']) || empty($arrPostParams['lastname']) || empty($arrPostParams['email']) || empty($arrPostParams['password']) || empty($arrPostParams['password2']) || empty($arrPostParams['userroleid'])){
				$this->sendOutput(json_encode(array('error' => -1,'description' => 'Parameter(s) Missing')));
			}
			
            try {
                $model = new UserModel();
				
				if (isset($arrPostParams['firstname']) && $arrPostParams['firstname']) {
                    $firstname = $arrPostParams['firstname'];
                }
				
				if (isset($arrPostParams['lastname']) && $arrPostParams['lastname']) {
                    $lastname = $arrPostParams['lastname'];
                }
				
				if (isset($arrPostParams['email']) && $arrPostParams['email']) {
                    $email = $arrPostParams['email'];
                }
				
				if (isset($arrPostParams['userroleid']) && $arrPostParams['userroleid']) {
                    $userroleid = $arrPostParams['userroleid'];
                }
				
				if (isset($arrPostParams['password']) && $arrPostParams['password']) {
                    $password = $arrPostParams['password'];
                }
				
				if (isset($arrPostParams['password2']) && $arrPostParams['password2']) {
                    $password2 = $arrPostParams['password2'];
                }
						
				if ($password != $password2){
					$strErrorCode = -1;
					$strErrorDesc = 'Passwords do not match';
					$strErrorHeader = 'HTTP/1.1 200 OK';
					$this->sendOutput(json_encode(array('error' => $strErrorCode,'description'=>$strErrorDesc)), 
						array('Content-Type: application/json', $strErrorHeader)
					);
				}
				
				// Check if user exists first
				$dupe = $model->checkUserEmail($email);
				
				if (count($dupe) > 0){
					$strErrorCode = -1;
					$strErrorDesc = 'That email address has already been used';
					$strErrorHeader = 'HTTP/1.1 200 OK';
					$this->sendOutput(json_encode(array('error' => $strErrorCode,'description'=>$strErrorDesc)), 
						array('Content-Type: application/json', $strErrorHeader)
					);
				}

                $result = $model->addUser($firstname,$lastname,$email,$password,$userroleid);

				if (!$result){
					$strErrorCode = -1;
					$strErrorDesc = 'Could not add user';
            		$strErrorHeader = 'HTTP/1.1 200 OK';
				} else {
					$data["userid"] = $result;
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
	
	// Update single user
	public function updateAction()
    {
		
        $strErrorDesc = '';
		$strErrorCode = 0;
        $requestMethod = $_SERVER["REQUEST_METHOD"];
		$arrPostParams = $this->getPostParams();
		$data = [];
		$firstname = NULL;
		$lastname = NULL;
		$email = NULL;
		$userroleid = NULL;
		$password = NULL;
		$password2 = NULL;
		
        if (strtoupper($requestMethod) == 'POST') {
		
			// Get user for event audit
			$model = new UserModel();		
			$user = $model->getUserFromSession($arrPostParams['sessiontoken']);
			$calling_userid = $user[0]['userid'];
			$calling_username = $user[0]['fullname'];
		
			if (!isset($arrPostParams['id']) || empty($arrPostParams['id'])){
				$this->sendOutput(json_encode(array('error' => -1,'description'=>'Parameter(s) Missing')));		
			}
			
            try {

               $id = $arrPostParams['id'];
				
				// Unless the user is an admin, they cannot update other users - kick them out if they try!
				if ($user[0]["userroleid"] < 2){
					if ($calling_userid != $id){
						$this->sendOutput(json_encode(array('error' => -1,'description'=>'Insufficient Privileges')));
					}
				}
				
				if (isset($arrPostParams['firstname']) && !empty($arrPostParams['firstname'])) {
                    $firstname = $arrPostParams['firstname'];
                }
				
				if (isset($arrPostParams['lastname']) && !empty($arrPostParams['lastname'])) {
                    $lastname = $arrPostParams['lastname'];
                }
				
				if (isset($arrPostParams['email']) && !empty($arrPostParams['email'])) {
                    $email = $arrPostParams['email'];
                }
				
				if (isset($arrPostParams['userroleid']) && !empty($arrPostParams['userroleid'])) {
                    $userroleid = $arrPostParams['userroleid'];
                }
				
				if (isset($arrPostParams['password']) && !empty($arrPostParams['password'])) {
                    $password = $arrPostParams['password'];
                }
				
				if (isset($arrPostParams['password2']) && !empty($arrPostParams['password2'])) {
                    $password2 = $arrPostParams['password2'];
                }
				
				if (!is_null($password) && !isset($password2)){
					$strErrorCode = -1;
					$strErrorDesc = 'You must confirm the password';
					$strErrorHeader = 'HTTP/1.1 200 OK';
					$this->sendOutput(json_encode(array('error' => $strErrorCode,'description'=>$strErrorDesc)), 
						array('Content-Type: application/json', $strErrorHeader)
					);
				}
				
				if (!is_null($password) && !is_null($password2)){
					if ($password != $password2){
						$strErrorCode = -1;
						$strErrorDesc = 'Passwords do not match';
						$strErrorHeader = 'HTTP/1.1 200 OK';
						$this->sendOutput(json_encode(array('error' => $strErrorCode,'description'=>$strErrorDesc)), 
							array('Content-Type: application/json', $strErrorHeader)
						);
					}
				}
				
				// Check if user exists first
				$dupe = $model->checkUserEmail($email,$id);
				if (count($dupe) > 0){
					$strErrorCode = -1;
					$strErrorDesc = 'That email address has already been used';
					$strErrorHeader = 'HTTP/1.1 200 OK';
					$this->sendOutput(json_encode(array('error' => $strErrorCode,'description'=>$strErrorDesc)), 
						array('Content-Type: application/json', $strErrorHeader)
					);
				}

                $result = $model->updateUser($id,$firstname,$lastname,$email,$userroleid,$password,$password2);

				if (!$result){
					$strErrorCode = -1;
					$strErrorDesc = 'Could not update user';
            		$strErrorHeader = 'HTTP/1.1 200 OK';
				} else {	
					// Audit
					$data["userid"] = $id;
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
     * Endpoint - Delete user
	 *
     */
	public function deleteAction(){
		
		$strErrorDesc = '';
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $arrPostParams = $this->getPostParams();
		$strErrorCode = 0;

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
                $arr = $usermodel->getUser($id);
				
				if (count($arr) < 1){
					$strErrorCode = -1;
					$strErrorDesc = 'User not found';
					$this->sendOutput(json_encode(array('error' => $strErrorCode,'description'=>$strErrorDesc)));
				} else {
					// User exists, delete them
					$result = $usermodel->deleteUser($id);
					if (!$result){
						$strErrorCode = -1;
						$strErrorHeader = 'The user could not be deleted';
					} else {
						$deletedusername = $arr[0]["firstname"]." ".$arr[0]["lastname"];
						$data["userid"] = $id;
					}
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