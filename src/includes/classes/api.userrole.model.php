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

class UserRoleModel extends Database
{
    public function listUserRoles($start,$limit,$sort,$dir,$filters,$countonly=false)
    {
		$sql = "SELECT userroleid,userrolename FROM ".USER_ROLES." WHERE userroleid > 0 AND isdeleted = 0 ";
		
		$params = [];
		
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
	
	public function getUserRole($userroleid)
    {
        return $this->select("SELECT userroleid,userrolename FROM ".USER_ROLES." WHERE userroleid = :userroleid AND isdeleted = 0", [":userroleid", $userroleid]);
    }


	public function addUserRole($userrolename)
    {
		$params = [];
		$params[] = [":userrolename", $userrolename];
        return $this->insert("INSERT INTO ".USER_ROLES." (userrolename) VALUES (:userrolename)", $params);
    }
	
	public function updateUserRole($userroleid,$userrolename)
    {
		$params = [];
		$params[] = [":userroleid", $userroleid];
		$params[] = [":userrolename", $userrolename];
		$sql = "UPDATE ".USER_ROLES." SET userrolename = :userrolename WHERE userroleid = :userroleid LIMIT 1";
        return $this->update($sql, $params);
    }
	
	public function deleteUserRole($userroleid)
     {
		$params = [];
		$params[] = [":userroleid", $userroleid];
        return $this->update("UPDATE ".USER_ROLES." SET isdeleted=1 WHERE userroleid=:userroleid", $params);
    }
	
}
?>