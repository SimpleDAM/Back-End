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

class AssetModel extends Database
{
    public function listAssets($start,$limit,$sort,$dir,$filters,$countonly=false)
    {
		$sql = "SELECT assetid,publicassetid,metadata,a.userid,a.datecreated,a.datemodified FROM ".ASSETS." a LEFT JOIN ".USERS." u ON a.userid = u.userid WHERE a.isdeleted = 0 ";
		
		$allowed_sort_ary = array("assetid", "userid", "datecreated", "datemodified");
		// If the provided sort field isn't supported
		if (!in_array($sort,$allowed_sort_ary)){
			// Uncomment the line below to return false and use the error condition in api.asset.controller.php (listAction)
			//return false;
		}
		
		// Do filtering
		$params = [];
		// keyword		
		if (isset($filters["q"]) && !empty($filters["q"])){
			$keyword_array = explode(" ", $filters["q"]);
			$sql .= "AND ";
			$i = 0;
			foreach($keyword_array as $keyword){
				$sql .= "(metadata LIKE :metadata_$i OR publicassetid LIKE :asset_$i OR a.datecreated like :datecreated_$i)";
				$sql .= " AND "; // this is where we decide whether to use ALL (AND) or ANY (OR) keywords
				$params[] = [":metadata_$i",'%'.$keyword.'%'];
				$params[] = [":asset_$i",'%'.$keyword.'%'];
				$params[] = [":datecreated_$i",'%'.$keyword.'%'];
				$i++;
			}
			$sql = trim($sql,"AND ");
		}
		
		// Other filters
		$f = 0;
		if (!is_null($filters) && is_array($filters)){
			foreach($filters as $field=>$value){
				if ($field == "q"){
					continue;
				}
				$sql .= " AND ";
				if ($value == "NULL" || $value == "null"){
					$sql .= "$field IS NULL";
				} else {
					$sql .= "$field = :field_$f";
					$params[] = [":field_$f",$value];
				}
				$f++;
			}
		}
		
		// For totals
		$sql_count = $sql;
		
		// Return if only requesting the total
		if ($countonly){
			return $this->select($sql_count, $params);
		}
		
		$sql .= " ORDER BY $sort $dir LIMIT :start, :limit";
		
		$params[] = [":start", $start];
		$params[] = [":limit", $limit];
		
        return $this->select($sql, $params);
    }
	
	public function getAsset($publicassetid)
    {
        return $this->select("SELECT assetid,publicassetid,metadata,u.userid,a.datecreated,a.datemodified FROM ".ASSETS." a LEFT JOIN ".USERS." u ON a.userid = u.userid WHERE a.publicassetid = :publicassetid AND a.isdeleted = 0", [":publicassetid", $publicassetid]);
    }
	
	public function getAssetIDHash($assetid)
    {
        return $this->select("SELECT publicassetid FROM ".ASSETS." WHERE assetid = :assetid", [":assetid", $assetid]);
    }
	
	public function getAssetHash($publicassetid)
    {
        return $this->select("SELECT hashid FROM ".ASSETS." WHERE publicassetid = :publicassetid", [":publicassetid", $publicassetid]);
    }
	
	public function addAsset($userid,$hashed_basename,$metadata=NULL)
     {
		$params = [];	
		$params[] = [":metadata", $metadata];
		$params[] = [":userid", $userid];
		$params[] = [":hashid", $hashed_basename];
		$params[] = [":datecreated", date("Y-m-d H:i:s")];
		$params[] = [":datemodified", date("Y-m-d H:i:s")];
        return $this->insert("INSERT INTO ".ASSETS." (hashid,metadata,userid,datecreated,datemodified) VALUES (:hashid,:metadata,:userid,:datecreated,:datemodified)", $params);
    }
	
	public function updateAsset($publicassetid,$hashed_basename,$metadata=NULL)
     {
		$params = [];
		$params[] = [":publicassetid", $publicassetid];
		$params[] = [":hashid", $hashed_basename];
		$params[] = [":metadata", $metadata];
		$params[] = [":datemodified", date("Y-m-d H:i:s")];
        return $this->update("UPDATE ".ASSETS." SET hashid=:hashid,metadata=:metadata,datemodified=:datemodified WHERE publicassetid=:publicassetid", $params);
    }
	
	// This is called immediately after the asset is created
	public function updatePublicAssetID($assetid)
     {
		$params = [];
		$params[] = [":publicassetid", sha1($assetid)];
		$params[] = [":assetid", $assetid];
        return $this->update("UPDATE ".ASSETS." SET publicassetid=:publicassetid WHERE assetid=:assetid", $params);
    }
	
	public function deleteAsset($publicassetid)
     {
		$params = [];
		$params[] = [":publicassetid", $publicassetid];
		$params[] = [":datemodified", date("Y-m-d H:i:s")];
        return $this->update("UPDATE ".ASSETS." SET isdeleted=1,datemodified=:datemodified WHERE publicassetid=:publicassetid", $params);
    }
	
	// Utility functions
	public function getNumDownloads($assetid)
     {
		$params = [];
		$params[] = [":assetid", $assetid];
        return $this->select("SELECT COUNT(1) as total FROM ".EVENTS." WHERE assetid=:assetid AND eventtypeid=9", $params);
    }
	
	public function getNumViews($assetid)
     {
		$params = [];
		$params[] = [":assetid", $assetid];
        return $this->select("SELECT COUNT(1) as total FROM ".EVENTS." WHERE assetid=:assetid AND eventtypeid=7", $params);
    }
	
}
?>