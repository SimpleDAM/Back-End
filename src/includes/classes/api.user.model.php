<?php
/*
This software is released under the BSD-3-Clause License

Copyright 2022 Daydream Interactive Limited

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.

2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and or other materials provided with the distribution.

3. Neither the name of the copyright holder nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

class UserModel extends Database
{
    public function listUsers($start,$limit,$sort,$dir,$filters,$countonly=false)
    {
		$sql = "SELECT userid,firstname,lastname,email,u.userroleid,userrolename,lastlogindate,datecreated,datemodified FROM ".USERS." u LEFT JOIN ".USER_ROLES." ur ON u.userroleid = ur.userroleid WHERE userid > 0 ";
		
		// Do filtering
		$params = [];
		if (isset($filters["r"]) && !empty($filters["r"])){
			$sql .= "AND u.userroleid = :userroleid ";
			$params[] = [":userroleid",$filters["r"]];
		}
		
		if (isset($filters["q"]) && !empty($filters["q"])){
		
			$keyword_array = explode(" ", $filters["q"]);
			$sql .= "AND ";
			$i = 0;
			foreach($keyword_array as $keyword){
				$sql .= "(firstname LIKE :firstname_$i OR lastname LIKE :lastname_$i OR email LIKE :email_$i)";
				$sql .= " AND "; // this is where we decide whether to use ALL (AND) or ANY (OR) keywords
				$params[] = [":firstname_$i",'%'.$keyword.'%'];
				$params[] = [":lastname_$i",'%'.$keyword.'%'];
				$params[] = [":email_$i",'%'.$keyword.'%'];
				$i++;
			}
			$sql = trim($sql,"AND ");
		}
		
		// For totals
		$sql_count = $sql;
		
		// Get totals
		if ($countonly){
			return $this->select($sql_count, $params);
		}
		
		$sql .= "ORDER BY $sort $dir LIMIT :start, :limit";
		
		$params[] = [":start", $start];
		$params[] = [":limit", $limit];
		
        return $this->select($sql, $params);
    }
	
	public function getUser($userid)
    {
        return $this->select("SELECT userid,firstname,lastname,email,userroleid,lastlogindate,datecreated,datemodified FROM ".USERS." WHERE userid = :userid", [":userid", $userid]);
    }
	
	public function checkUserEmail($email,$userid=NULL)
    {
		if (!is_null($userid)){
			return $this->select("SELECT * FROM ".USERS." WHERE email = :email AND userid != :userid LIMIT 1", [[":email", $email],[":userid", $userid]]);
		}
        return $this->select("SELECT * FROM ".USERS." WHERE email = :email LIMIT 1", [":email", $email]);
    }
	
	public function addUser($firstname,$lastname,$email,$password,$userroleid)
    {
		$params = [];
		$params[] = [":firstname", $firstname];
		$params[] = [":lastname", $lastname];
		$params[] = [":email", $email];
		$params[] = [":password", sha1($password)];
		$params[] = [":userroleid", $userroleid];
		$params[] = [":datecreated", date("Y-m-d H:i:s")];
		$params[] = [":datemodified", date("Y-m-d H:i:s")];
        return $this->insert("INSERT INTO ".USERS." (firstname,lastname,email,password,userroleid,datecreated,datemodified) VALUES (:firstname,:lastname,:email,:password,:userroleid,:datecreated,:datemodified)", $params);
    }
	
	public function updateUser($userid,$firstname=NULL,$lastname=NULL,$email=NULL,$userroleid=NULL,$password=NULL,$password2=NULL)
    {
		$params = [];
		$params[] = [":userid", $userid];
		$params[] = [":datemodified", date("Y-m-d H:i:s")];
		$sql = "UPDATE ".USERS." SET datemodified = :datemodified";
		if (!is_null($firstname) && !empty($firstname)){
			$params[] = [":firstname", $firstname];
			$sql .= ", firstname = :firstname";
		}
		if (!is_null($lastname) && !empty($lastname)){
			$params[] = [":lastname", $lastname];
			$sql .= ", lastname = :lastname";
		}
		if (!is_null($email) && !empty($email)){
			$params[] = [":email", $email];
			$sql .= ", email = :email";
		}
		if (!is_null($userroleid) && !empty($userroleid)){
			$params[] = [":userroleid", $userroleid];
			$sql .= ", userroleid = :userroleid";
		}
		if (!is_null($password) && !empty($password) && !is_null($password2) && !empty($password2)){
			$params[] = [":password", sha1($password)];
			$sql .= ", password = :password";
		}
		$sql .= " WHERE userid = :userid LIMIT 1";
        return $this->update($sql, $params);
    }
	
	public function deleteUser($userid)
     {
		$params = [];
		$params[] = [":userid", $userid];
		$params[] = [":datemodified", date("Y-m-d H:i:s")];
        return $this->update("UPDATE ".USERS." SET isdeleted=1,datemodified=:datemodified WHERE userid=:userid", $params);
    }
	
	public function loginUser($email,$password)
    {
		$params = [];
		$params[] = [":email", $email];
		$params[] = [":password", sha1($password)];
        return $this->select("SELECT userid,firstname,lastname,userroleid FROM ".USERS." WHERE email = :email AND password = :password LIMIT 1", $params);
    }
	
	public function logoutUser($sessiontoken)
    {
		$params = [];
		$params[] = [":sessiontoken", $sessiontoken];
		return $this->delete("DELETE FROM ".SESSIONS." WHERE sessiontoken = :sessiontoken", $params);
    }
	
	public function updateLastLoginDate($userid)
    {
		$params = [];
		$params[] = [":userid", $userid];
		$params[] = [":lastlogindate", date("Y-m-d H:i:s")];
		return $this->update("UPDATE ".USERS." SET lastlogindate = :lastlogindate WHERE userid = :userid", $params);
    }
	
	public function createSession($userid)
    {
		$params = [];
		$params[] = [":userid", $userid];
		$params[] = [":sessiontoken", $this->createGUIDv4()];
		$params[] = [":sessiontimestamp", time()];
        return $this->insert("INSERT INTO ".SESSIONS." (userid,sessiontoken,sessiontimestamp) VALUES (:userid,:sessiontoken,:sessiontimestamp)", $params);
    }
	
	public function getLatestSession($userid)
    {
		$params = [];
		$params[] = [":userid", $userid];
        return $this->select("SELECT sessiontoken,sessiontimestamp FROM ".SESSIONS." WHERE userid = :userid ORDER BY sessionid DESC LIMIT 1", $params);
    }
	
	public function checkSession($sessiontoken)
    {
		$params = [];
		$params[] = [":sessiontoken", $sessiontoken];
		$validsession = time() - SESSION_DURATION; // From api global settings	
		
        return $this->select("SELECT ".SESSIONS.".*, firstname, lastname, userroleid FROM ".SESSIONS." LEFT JOIN ".USERS." ON ".SESSIONS.".userid = ".USERS.".userid WHERE sessiontoken = :sessiontoken AND sessiontimestamp > $validsession", $params);
    }
	
	public function updateSessionTimestamp($sessiontoken)
    {
		$params = [];
		$params[] = [":sessiontimestamp", time()];
		$params[] = [":sessiontoken", $sessiontoken];
		
        return $this->update("UPDATE ".SESSIONS." SET sessiontimestamp = :sessiontimestamp WHERE sessiontoken = :sessiontoken LIMIT 1", $params);
    }
	
	public function getUserFromSession($sessiontoken)
    {
		$params = [];
		$params[] = [":sessiontoken", $sessiontoken];
        return $this->select("SELECT ".SESSIONS.".*, firstname, lastname, CONCAT(firstname,' ',lastname) as fullname, userroleid FROM ".SESSIONS." LEFT JOIN ".USERS." ON ".SESSIONS.".userid = ".USERS.".userid WHERE sessiontoken = :sessiontoken", $params);
    }
	
	private function clearStaleSessions($userid){
		$params = [];
		$params[] = [":userid", $userid];
		return $this->delete("DELETE FROM ".SESSIONS." WHERE userid = :userid", $params);
	}
	
	public function createGUIDv4(){
		if (function_exists('com_create_guid') === true){
			return trim(com_create_guid(), '{}');
		}
		$data = openssl_random_pseudo_bytes(16);
		$data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
		$data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
		return vsprintf('%s-%s-%s-%s-%s-%s-%s-%s', str_split(bin2hex($data), 4));
	}
	
}
?>