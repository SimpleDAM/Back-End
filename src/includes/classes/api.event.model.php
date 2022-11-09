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

class EventModel extends Database
{
    public function listEvents($start,$limit,$sort,$dir,$filters,$countonly=false)
    {
		$sql = "SELECT eventid,e.eventtypeid,eventtypename,e.userid,assetid,eventip,eventdetails,apiurl,apimethod,eventdate,firstname,lastname FROM ".EVENTS." e LEFT JOIN ".EVENT_TYPES." et ON e.eventtypeid = et.eventtypeid LEFT JOIN ".USERS." u ON e.userid = u.userid WHERE eventid > 0 ";
		
		// Do filtering
		$params = [];
		if (isset($filters["t"]) && !empty($filters["t"])){
			$sql .= "AND e.eventtypeid = :eventtypeid ";
			$params[] = [":eventtypeid",$filters["t"]];
		}
		
		if (isset($filters["q"]) && !empty($filters["q"])){
		
			$keyword_array = explode(" ", $filters["q"]);
			$sql .= "AND ";
			$i = 0;
			foreach($keyword_array as $keyword){
				$sql .= "(eventdetails LIKE :eventdetails_$i)";
				$sql .= " AND "; // this is where we decide whether to use ALL (AND) or ANY (OR) keywords
				$params[] = [":eventdetails_$i",'%'.$keyword.'%'];
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
	
	public function getEvent($eventid)
    {
        return $this->select("SELECT eventid,e.eventtypeid,eventtypename,userid,assetid,eventip,eventdetails,apiurl,apimethod,eventdate FROM ".EVENTS." e LEFT JOIN ".EVENT_TYPES." et ON e.eventtypeid = et.eventtypeid WHERE eventid = :eventid", [":eventid", $eventid]);
    }


	public function addEvent($eventtypeid,$userid,$eventdetails,$assetid=NULL)
    {
		$params = [];
		$params[] = [":eventtypeid", $eventtypeid];
		$params[] = [":userid", $userid];
		$params[] = [":assetid", $assetid];
		$params[] = [":eventip", $_SERVER['REMOTE_ADDR']];
		$params[] = [":eventdetails", $eventdetails];
		$params[] = [":eventdate", date("Y-m-d H:i:s")];
		$url = $_SERVER["REQUEST_URI"];
		// Remove the session token querystring from the event
		if ($_SERVER['REQUEST_METHOD'] == "GET"){
			$keytoremove = 'sessiontoken';
			$url_without_sessiontoken = preg_replace('~(\?|&)'.$keytoremove.'=[^&]*~', '$1', $_SERVER["REQUEST_URI"]);
			$url = str_replace("?&","?",$url_without_sessiontoken);
		}
		$params[] = [":apiurl",$url];
		$params[] = [":apimethod",$_SERVER['REQUEST_METHOD']];
        return $this->insert("INSERT INTO ".EVENTS." (eventtypeid,userid,assetid,eventip,eventdetails,apiurl,apimethod,eventdate) VALUES (:eventtypeid,:userid,:assetid,:eventip,:eventdetails,:apiurl,:apimethod,:eventdate)", $params);
    }
	
}
?>