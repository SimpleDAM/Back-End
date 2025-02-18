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

class EventModel extends Database
{
    public function listEvents($start,$limit,$sort,$dir,$filters,$countonly=false)
    {
		$sql = "SELECT eventid,e.eventtypeid,eventtypename,e.userid,assetid,eventip,eventdetails,apiurl,apimethod,apirequest,apiresponse,eventdate,firstname,lastname FROM ".EVENTS." e LEFT JOIN ".EVENT_TYPES." et ON e.eventtypeid = et.eventtypeid LEFT JOIN ".USERS." u ON e.userid = u.userid WHERE eventid > 0 ";
		
		// Do filtering
		$params = [];
		
		// Event type - can now sort by multiple event types
		if (isset($filters["t"]) && !empty($filters["t"])){
			if (is_array($filters["t"])){
				$sql .= "AND (";
				$i = 0;
				foreach($filters["t"] as $t){
					$sql .= "e.eventtypeid = :eventtypeid_$i";
					$sql .= " OR "; // this is where we decide whether to use ALL (AND) or ANY (OR) keywords
					$params[] = [":eventtypeid_$i",$t];
					$i++;
				}
				$sql = trim($sql,"OR ").") ";
			} else {
				$sql .= "AND e.eventtypeid = :eventtypeid ";
				$params[] = [":eventtypeid",$filters["t"]];
			}
		}
		
		// Keyword
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
		
		// Filter by Asset ID
		if (isset($filters["id"]) && !empty($filters["id"])){
			$sql .= "AND assetid = :assetid ";
			$params[] = [":assetid",$filters["id"]];
		}
		
		// Filter by User ID
		if (isset($filters["userid"]) && !empty($filters["userid"])){
			$sql .= "AND e.userid = :userid ";
			$params[] = [":userid",$filters["userid"]];
		}
		
		// Filter by date from
		if (isset($filters["from"]) && !empty($filters["from"])){
			$sql .= "AND DATE(eventdate) >= :from ";
			$params[] = [":from",$filters["from"]];
		}
		
		// Filter by date to
		if (isset($filters["to"]) && !empty($filters["to"])){
			$sql .= "AND DATE(eventdate) <= :to ";
			$params[] = [":to",$filters["to"]];
		}
		
		// For totals
		$sql_count = $sql;
		
		// Get totals
		if ($countonly){
			return $this->select($sql_count, $params);
		}
		
		// If start AND limit are NULL, get ALL records (for downloadable records)
		if (is_null($start) && is_null($limit)){
			$sql .= "ORDER BY $sort $dir";
		} else {
			$sql .= "ORDER BY $sort $dir LIMIT :start, :limit";
			$params[] = [":start", $start];
			$params[] = [":limit", $limit];
		}
		
        return $this->select($sql, $params);
    }
	
	public function getEvent($eventid)
    {
        return $this->select("SELECT eventid,e.eventtypeid,eventtypename,userid,assetid,eventip,eventdetails,apiurl,apimethod,apirequest,apiresponse,eventdate FROM ".EVENTS." e LEFT JOIN ".EVENT_TYPES." et ON e.eventtypeid = et.eventtypeid WHERE eventid = :eventid", [":eventid", $eventid]);
    }


	public function addEvent($eventtypeid,$userid,$eventdetails,$assetid=NULL,$response=NULL)
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
			$api_request = $_GET;
			unset($api_request['sessiontoken']);
			$params[] = [":apirequest",json_encode($api_request,JSON_UNESCAPED_SLASHES)];
		} else {
			$api_request = $_POST;
			unset($api_request['sessiontoken']);
			unset($api_request['password']);
			if (isset($api_request["metadata"])){
				$metadata_ary = json_decode($api_request["metadata"],true);
				$api_request["metadata"] = $metadata_ary;
			}
			$params[] = [":apirequest",json_encode($api_request,JSON_UNESCAPED_SLASHES)];
		}
		
		$params[] = [":apiurl",$url];
		$params[] = [":apimethod",$_SERVER['REQUEST_METHOD']];
		
		// Encode it
		$response = json_encode($response,JSON_UNESCAPED_SLASHES);
		// Final process to replace multiple backslashes with a single one
		$response = preg_replace('/\\\\{2,}/', '\\', $response);
		
		// If the API call was /api/event/list, do NOT store the JSON response - it's self-referential and accumulates too much data :/
		if ($eventtypeid == 25){
			$response = '{"error":0,"description":"success"}';
		}
		
		$params[] = [":apiresponse",$response];		
		// Do the query
        return $this->insert("INSERT INTO ".EVENTS." (eventtypeid,userid,assetid,eventip,eventdetails,apiurl,apimethod,apirequest,apiresponse,eventdate) VALUES (:eventtypeid,:userid,:assetid,:eventip,:eventdetails,:apiurl,:apimethod,:apirequest,:apiresponse,:eventdate)", $params);
    }
	
}
?>