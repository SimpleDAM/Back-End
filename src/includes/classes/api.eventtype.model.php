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

class EventTypeModel extends Database
{
    public function listEventTypes($start,$limit,$sort,$dir,$filters,$countonly=false)
    {
		$sql = "SELECT eventtypeid, eventtypename FROM ".EVENT_TYPES." WHERE eventtypeid > 0 AND isdeleted = 0 ";
		
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
	
	public function getEventType($eventtypeid)
    {
        return $this->select("SELECT eventtypeid, eventtypename FROM ".EVENT_TYPES." WHERE eventtypeid = :eventtypeid AND isdeleted = 0", [":eventtypeid", $eventtypeid]);
    }


	public function addEventType($eventtypename)
    {
		$params = [];
		$params[] = [":eventtypename", $eventtypename];
        return $this->insert("INSERT INTO ".EVENT_TYPES." (eventtypename) VALUES (:eventtypename)", $params);
    }
	
	public function updateEventType($eventtypeid,$eventtypename)
    {
		$params = [];
		$params[] = [":eventtypeid", $eventtypeid];
		$params[] = [":eventtypename", $eventtypename];
		$sql = "UPDATE ".EVENT_TYPES." SET eventtypename = :eventtypename WHERE eventtypeid = :eventtypeid LIMIT 1";
        return $this->update($sql, $params);
    }
	
	public function deleteEventType($eventtypeid)
     {
		$params = [];
		$params[] = [":eventtypeid", $eventtypeid];
        return $this->update("UPDATE ".EVENT_TYPES." SET isdeleted=1 WHERE eventtypeid=:eventtypeid", $params);
    }
	
}
?>