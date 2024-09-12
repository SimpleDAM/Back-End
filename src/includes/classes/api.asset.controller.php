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

class AssetController extends ApiBaseController
{
	
	/**
	 * Get an individual asset
     * Endpoint: /api/asset/get
     */
	public function getAction()
    {
	
        $strErrorDesc = '';
		$strErrorCode = 0;
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $arrQueryStringParams = $this->getQueryStringParams();

        if (strtoupper($requestMethod) == 'GET') {
			
			// Get user for event audit
			$usermodel = new UserModel();
			$user = $usermodel->getUserFromSession($arrQueryStringParams['sessiontoken']);
		
			if (!isset($arrQueryStringParams['id']) || empty($arrQueryStringParams['id'])){
				$this->sendOutput(json_encode(array('error' => -1,'description'=>'Parameter Missing')));
			}
			
            try {

                $id = $arrQueryStringParams['id'];
				$model = new AssetModel();
                $arr = $model->getAsset($id);
				
				if (count($arr) < 1){
					$strErrorCode = -1;
					$strErrorDesc = 'Asset not found';
            		$strErrorHeader = 'HTTP/1.1 200 OK';
				} else {
					// Get the data from the SQL results array
					$asset = $arr[0];
					$assetid = $asset["assetid"];
					// Do not expose the actual asset id via the API response
					$asset["assetid"] = $asset["publicassetid"];
					unset($asset["publicassetid"]);
					
					// Strip out \u0000 characters from JSON (before converting to an array)
					$asset["metadata"] = str_replace("\\u0000", "", $asset["metadata"]);
					
					// Convert to an arary			
					$asset["metadata"] = json_decode($asset["metadata"], true);
					
					// Get the number of views for this assets via a discreet query
					$asset["metadata"]["extensions"]["simpledam"]["views"] = 0;
					$vw = $model->getNumViews($assetid);
					if (isset($vw[0]) && count($vw[0]) > 0){
						$asset["metadata"]["extensions"]["simpledam"]["views"] = $vw[0]["total"];
					}
					
					// Get the number of downloads for this assets via a discreet query
					$asset["metadata"]["extensions"]["simpledam"]["downloads"] = 0;
					$dl = $model->getNumDownloads($assetid);
					if (isset($dl[0]) && count($dl[0]) > 0){
						$asset["metadata"]["extensions"]["simpledam"]["downloads"] = $dl[0]["total"];
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
		
			//Audit
			$eventmodel = new EventModel();
			$eventdetails = $user[0]['fullname'] . " got asset $assetid";
			$audit = $eventmodel->addEvent(7,$user[0]['userid'],$eventdetails,$assetid);
			
            $this->sendOutput(
				json_encode(array('error' => $strErrorCode,'description'=>'success','data'=>$asset),JSON_UNESCAPED_SLASHES),
                array('Content-Type: application/json', 'HTTP/1.1 200 OK')
            );
        } else {
            $this->sendOutput(json_encode(array('error' => $strErrorCode,'description'=>$strErrorDesc)), 
                array('Content-Type: application/json', $strErrorHeader)
            );
        }
    }
	
    /**
     * Get list of assets
	 * Endpoint: /api/asset/list
     */
    public function listAction()
    {
		global $allowed_actions, $allowed_entities;
        $strErrorDesc = '';
		$strErrorCode = 0;
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $arrQueryStringParams = $this->getQueryStringParams();
		$filters = [];

        if (strtoupper($requestMethod) == 'GET') {
		
			// Get user for event audit
			$usermodel = new UserModel();
			$user = $usermodel->getUserFromSession($arrQueryStringParams['sessiontoken']);
			
            try {
                $model = new AssetModel();

                $intStart = 0;
                if (isset($arrQueryStringParams['start']) && $arrQueryStringParams['start']) {
                    $intStart = $arrQueryStringParams['start'];
                }
				
				$intLimit = DEFAULT_PER_PAGE;
                if (isset($arrQueryStringParams['limit']) && $arrQueryStringParams['limit']) {
                    $intLimit = $arrQueryStringParams['limit'];
                }
				
				$intSort = "assetid";
                if (isset($arrQueryStringParams['sort']) && $arrQueryStringParams['sort']) {
                    $intSort = $arrQueryStringParams['sort'];
                }
				// Allow use of id - translate to assetid
				$intSort = ($intSort == "id") ? "assetid" : $intSort;
				
				$intDir = "desc";
                if (isset($arrQueryStringParams['dir']) && $arrQueryStringParams['dir']) {
                    $intDir = $arrQueryStringParams['dir'];
                }
				// keyword
				if (isset($arrQueryStringParams['q']) && $arrQueryStringParams['q']) {
                    $filters["q"] = $arrQueryStringParams['q'];
                }
				// tags (not used)
				if (isset($arrQueryStringParams['t']) && $arrQueryStringParams['t']) {
                    $filters["t"] = $arrQueryStringParams['t'];
                }
				
				// source of assets (not used)
				if (isset($arrQueryStringParams['source']) && $arrQueryStringParams['source']) {
                    $filters["source"] = $arrQueryStringParams['source'];
                }
				
				// Add other filters (not currently supported)
				foreach($arrQueryStringParams as $key=>$value){
					if (!array_key_exists($key,$filters) && $key != "start" && $key != "limit" && $key != "sort" && $key != "dir" && $key != "sessiontoken" && $key != "entity" && $key != "action" && $key != "sessiontoken"){
						$filters["$key"] = $value;
					}
				}
						
				// Get total amount of assets
				$numAssets = $model->listAssets($intStart,$intLimit,$intSort,$intDir,$filters,true);
				// Get the actual assets
				$arrAssets = $model->listAssets($intStart,$intLimit,$intSort,$intDir,$filters);
				
				// If the query failed (this is overwritten by api.database.model.php error trapping - to enable the below error, see listAssets function in api.asset.model.php)
				if (!$numAssets || !$arrAssets){
					//$this->sendOutput(json_encode(array('error' => -1,'description'=>'There was a problem retrieving the assets')));
				}
				
				// Format or adjust the object here, prior to JSON output
				$data = [];
				$data["total"] = count($numAssets);
				$data["assets"] = [];
				foreach($arrAssets as $asset){
					
					// Strip out \u0000 characters from JSON (before converting to an array)
					$asset["metadata"] = str_replace("\\u0000", "", $asset["metadata"]);
					// Convert metadata JSON string to an array
					$asset["metadata"] = json_decode($asset["metadata"], true);
					
					// Get the number of views for this assets via a discreet query
					$asset["metadata"]["extensions"]["simpledam"]["views"] = 0;
					$vw = $model->getNumViews($asset["assetid"]);
					if (isset($vw[0]) && count($vw[0]) > 0){
						$asset["metadata"]["extensions"]["simpledam"]["views"] = $vw[0]["total"];
					}
				
					// Get the number of downloads for this assets via a discreet query
					$asset["metadata"]["extensions"]["simpledam"]["downloads"] = 0;
					$dl = $model->getNumDownloads($asset["assetid"]);
					if (isset($dl[0]) && count($dl[0]) > 0){
						$asset["metadata"]["extensions"]["simpledam"]["downloads"] = $dl[0]["total"];
					}
					
					// Do not expose the actual asset id via the API response
					$asset["assetid"] = $asset["publicassetid"];
					unset($asset["publicassetid"]);			

					$data["assets"][] = $asset;
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
			
			// Audit
			$eventmodel = new EventModel();
			$eventdetails = $user[0]['fullname'] . " listed assets";
			$audit = $eventmodel->addEvent(8,$user[0]['userid'],$eventdetails);
				
            $this->sendOutput(
				json_encode(array('error' => $strErrorCode, 'description'=>'success','data'=>$data),JSON_UNESCAPED_SLASHES),
                array('Content-Type: application/json', 'HTTP/1.1 200 OK')
            );
        } else {
            $this->sendOutput(json_encode(array('error' => $strErrorCode, 'description'=>$strErrorDesc)), 
                array('Content-Type: application/json', $strErrorHeader)
            );
        }
    }
	
	
    /**
     * Get an asset thumbnail
	 * Endpoint: /api/asset/thumbnail
     */
	public function thumbnailAction()
    {
	
        $strErrorDesc = '';
		$strErrorCode = 0;
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $arrQueryStringParams = $this->getQueryStringParams();
		$asset_has_binary_data = false;
		
        if (strtoupper($requestMethod) == 'GET') {
		
			if (!isset($arrQueryStringParams['id']) || empty($arrQueryStringParams['id'])){
				$this->sendOutput(json_encode(array('error' => -1,'description'=>'Parameter Missing')));
			}
			
            try {
                $model = new AssetModel();

                if (isset($arrQueryStringParams['id']) && $arrQueryStringParams['id']) {
                    $id = $arrQueryStringParams['id'];
                }

                $arr = $model->getAsset($id);
				$arrHash = $model->getAssetHash($id);
				
				if (count($arr) < 1){
					$strErrorCode = -1;
					$strErrorDesc = 'Asset not found';
            		$strErrorHeader = 'HTTP/1.1 200 OK';
				} else {
					// Get the element from the SQL results array
					$asset = $arr[0];
					$hash = $arrHash[0];
					
					// Strip out crappy \u0000 characters from JSON (before converting to an array)
					$asset["metadata"] = str_replace("\\u0000", "", $asset["metadata"]);
					
					// Convert to an array	
					$asset["metadata"] = json_decode($asset["metadata"], true);	
					
					// Get the metadata pertaining to a file
					$extension = $asset["metadata"]["extension"];
					$mimetype = $asset["metadata"]["mimetype"];
					$filename = $asset["metadata"]["filename"];
					$hashid = $hash["hashid"];
					
					if (!is_null($hashid) && !is_null($filename) && !is_null($extension) && !is_null($mimetype)){
						$asset_has_binary_data = true;
						// Ascertain the preview path
						$thumbnailfilename = $hashid.".jpg"; // all thumbnails are jpeg
						$thumbnailpath = THUMBNAIL_PATH."/".$thumbnailfilename;		
						if (file_exists($thumbnailpath) && is_file($thumbnailpath)){
							$sizedata = getimagesize($thumbnailpath);
						} else {
							if (NO_THUMBNAIL_USE_PLACEHOLDER_IMAGE){
								header("Content-Type: image/png");
								readfile(SITE_PATH."/images/no-thumbnail.png");
								exit();
							}
							$strErrorCode = -1;
							$strErrorDesc = 'Asset thumbnail file not found';
							$strErrorHeader = 'HTTP/1.1 200 OK';
						}
					// Else no file exists for the asset (i.e. metadata-only)
					} else {
						if (NO_THUMBNAIL_USE_PLACEHOLDER_IMAGE){
							header("Content-Type: image/png");
							readfile(SITE_PATH."/images/no-thumbnail.png");
							exit();
						}
						$strErrorCode = -1;
						$strErrorDesc = 'Asset thumbnail file not found (no binary data)';
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

        // Render out image with mimetype header
        if (!$strErrorDesc) {			
			header("Content-Type: ".mime_content_type($thumbnailpath));
			readfile($thumbnailpath);
        } else {
            $this->sendOutput(json_encode(array('error' => $strErrorCode,'description'=>$strErrorDesc)), 
                array('Content-Type: application/json', $strErrorHeader)
            );
        }
    }
	
    /**
     * Get an asset preview
	 * Endpoint: /api/asset/preview
     */
	public function previewAction()
    {
	
        $strErrorDesc = '';
		$strErrorCode = 0;
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $arrQueryStringParams = $this->getQueryStringParams();
		$asset_has_binary_data = false;
		
        if (strtoupper($requestMethod) == 'GET') {
		
			if (!isset($arrQueryStringParams['id']) || empty($arrQueryStringParams['id'])){
				$this->sendOutput(json_encode(array('error' => -1,'description'=>'Parameter Missing')));
			}
			
            try {
                $model = new AssetModel();

                if (isset($arrQueryStringParams['id']) && $arrQueryStringParams['id']) {
                    $id = $arrQueryStringParams['id'];
                }

                $arr = $model->getAsset($id);
				$arrHash = $model->getAssetHash($id);
				
				if (count($arr) < 1){
					$strErrorCode = -1;
					$strErrorDesc = 'Asset not found';
            		$strErrorHeader = 'HTTP/1.1 200 OK';
				} else {
					// Get the element from the SQL results array
					$asset = $arr[0];
					$hash = $arrHash[0];
					
					// Strip out \u0000 characters from JSON (before converting to an array)
					$asset["metadata"] = str_replace("\\u0000", "", $asset["metadata"]);
					
					// Convert to an arary			
					$asset["metadata"] = json_decode($asset["metadata"], true);	
					
					// Get the metadata pertaining to a file
					$hashid = $hash["hashid"];
					$extension = $asset["metadata"]["extension"];
					$mimetype = $asset["metadata"]["mimetype"];
					$filename = $asset["metadata"]["filename"];
					
					// Does the image have binary data?
					if (!is_null($hashid) && !is_null($filename) && !is_null($extension) && !is_null($mimetype)){
						$asset_has_binary_data = true;
						// Ascertain the preview path
						$previewfilename = $hashid.".jpg"; // all previews are jpeg
						$previewpath = PREVIEW_PATH."/".$previewfilename;		
						if (file_exists($previewpath) && is_file($previewpath)){
							$sizedata = getimagesize($previewpath);
						} else {
							if (NO_PREVIEW_USE_PLACEHOLDER_IMAGE){
								header("Content-Type: image/png");
								readfile(SITE_PATH."/images/no-preview.png");
								exit();
							}
							$strErrorCode = -1;
							$strErrorDesc = 'Asset preview file not found';
							$strErrorHeader = 'HTTP/1.1 200 OK';
						}
					// Else no file exists for the asset (i.e. metadata-only)
					} else {
						if (NO_PREVIEW_USE_PLACEHOLDER_IMAGE){
							header("Content-Type: image/png");
							readfile(SITE_PATH."/images/no-preview.png");
							exit();
						}
						$strErrorCode = -1;
						$strErrorDesc = 'Asset preview file not found';
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

        // Render out image with mimetype header
        if (!$strErrorDesc) {			
            header("Content-Type: ".mime_content_type($previewpath));
			readfile($previewpath);
        } else {
            $this->sendOutput(json_encode(array('error' => $strErrorCode,'description'=>$strErrorDesc)), 
                array('Content-Type: application/json', $strErrorHeader)
            );
        }
    }
	
	/**
     * Get an individual asset's binary data (e.g. for embedding via src attributes in HTML <img> or <audio> tags)
	 * Endpoint: /api/asset/embed
     */
	public function embedAction()
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
                $model = new AssetModel();

                if (isset($arrQueryStringParams['id']) && $arrQueryStringParams['id']) {
                    $id = $arrQueryStringParams['id'];
                }

                $arr = $model->getAsset($id);
				$arrHash = $model->getAssetHash($id);
				
				if (count($arr) < 1){
					$strErrorCode = -1;
					$strErrorDesc = 'Asset not found';
            		$strErrorHeader = 'HTTP/1.1 200 OK';
				} else {
					// Get the element from the SQL results array
					$asset = $arr[0];
					$hash = $arrHash[0];
					
					// Strip out \u0000 characters from JSON (before converting to an array)
					$asset["metadata"] = str_replace("\\u0000", "", $asset["metadata"]);
					
					// Convert to an arary			
					$asset["metadata"] = json_decode($asset["metadata"], true);	
					
					// Get the metadata pertaining to a file
					$hashid = $hash["hashid"];
					$extension = $asset["metadata"]["extension"];
					$mimetype = $asset["metadata"]["mimetype"];
					$filename = $asset["metadata"]["filename"];
					
					// Does the asset have binary data?
					if (!is_null($hashid) && !is_null($filename) && !is_null($extension) && !is_null($mimetype)){
						$asset_has_binary_data = true;
						// Ascertain the preview path
						$assetfilename = $hashid.".".$extension;
						$assetpath = ASSET_PATH."/".$assetfilename;		
						if (file_exists($assetpath) && is_file($assetpath)){
							// Image formats
							if (explode("/",$mimetype)[0] == "image"){
								$sizedata = getimagesize($assetpath);
								if (!in_array($extension,SUPPORTED_IMAGE_PREVIEW_TYPES)){
									if (NO_EMBED_USE_PLACEHOLDER_IMAGE){
										header("Content-Type: image/png");
										readfile(SITE_PATH."/images/no-embed-available.png");
										exit();
									}
									
								}
							}
							// Other formats
							if (!in_array($extension,SUPPORTED_BROWSER_TYPES)){
								if (NO_EMBED_USE_PLACEHOLDER_IMAGE){
									header("Content-Type: image/png");
									readfile(SITE_PATH."/images/no-embed-available.png");
									exit();
								} else {
									$strErrorCode = -1;
									$strErrorDesc = 'Cannot embed file type';
									$strErrorHeader = 'HTTP/1.1 200 OK';
								}
							}
						// Else asset file could not be found	
						} else {
							if (NO_BINARY_DATA_USE_PLACEHOLDER_IMAGE){
								header("Content-Type: image/png");
								readfile(SITE_PATH."/images/no-binary-data.png");
								exit();
							}
							$strErrorCode = -1;
							$strErrorDesc = 'Asset file not found';
							$strErrorHeader = 'HTTP/1.1 200 OK';
						}
					// Else no file exists for the asset (i.e. metadata-only)
					} else {
						if (NO_BINARY_DATA_USE_PLACEHOLDER_IMAGE){
							header("Content-Type: image/png");
							readfile(SITE_PATH."/images/no-binary-data.png");
							exit();
						}
						$strErrorCode = -1;
						$strErrorDesc = 'Asset file not found (no binary data)';
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

        // Render out asset with mimetype header
        if (!$strErrorDesc) {			
            header("Content-Type: ".$mimetype);
			readfile($assetpath);
        } else {
            $this->sendOutput(json_encode(array('error' => $strErrorCode,'description'=>$strErrorDesc)), 
                array('Content-Type: application/json', $strErrorHeader)
            );
        }
    }
	
	
	/**
     * Download an individual asset
	 * Endpoint: /api/asset/download
     */
	public function downloadAction(){
		$strErrorDesc = '';
		$strErrorCode = 0;
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $arrQueryStringParams = $this->getQueryStringParams();	
		
		if (strtoupper($requestMethod) == 'GET') {
		
			// Get user for event audit
			$usermodel = new UserModel();
			$user = $usermodel->getUserFromSession($arrQueryStringParams['sessiontoken']);
			$calling_userid = $user[0]['userid'];
			$calling_username = $user[0]['fullname'];
		
			if (!isset($arrQueryStringParams['id']) || empty($arrQueryStringParams['id'])){
				$strErrorCode = -1;
				$this->sendOutput(json_encode(array('error' => $strErrorCode,'description'=>'Parameter Missing')));
			}
			
            try {
                $model = new AssetModel();

                if (isset($arrQueryStringParams['id']) && $arrQueryStringParams['id']) {
                    $id = $arrQueryStringParams['id'];
                }
				if (isset($arrQueryStringParams['assetid']) && $arrQueryStringParams['assetid']) {
                    $id = $arrQueryStringParams['assetid'];
                }

                $arrAsset = $model->getAsset($id);
				$arrHash = $model->getAssetHash($id);
				
				if (count($arrAsset) < 1){
					$strErrorCode = -1;
					$strErrorDesc = 'Asset not found';
            		$strErrorHeader = 'HTTP/1.1 200 OK';
				}
				
                $asset = $arrAsset[0];
				$assetid = $asset["assetid"];
				// Do not expose the actual asset id via the API response
				$asset["assetid"] = $asset["publicassetid"];
				unset($asset["publicassetid"]);
				$hash = $arrHash[0];	
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

        // send output
        if (!$strErrorDesc) {
            // Decode metadata
			$metadata = json_decode($asset["metadata"], true);
			
			// If asset is metadata-only, return with a message
			if (is_null($hash["hashid"])){
				$strErrorCode = 1;
				$this->sendOutput(json_encode(array('error' => $strErrorCode,'description'=>'The requested asset has no associated file (i.e. it contains metadata-only). Download cancelled.')));
			}
			
			// Get name of file and server and prepare for download
			$originalfilename = $metadata["filename"];			
			$extension = $metadata["extension"];
			$mimetype = $metadata["mimetype"];
			$filename = $hash["hashid"].".".$extension;
			$filepath = ASSET_PATH."/".$filename;

			// Rewrite the Adobe Illustrator mimetype to avoid being served a PDF
			if ($extension == "ai"){
				$mimetype = "application/postscript";
			}

			if (file_exists($filepath)){
			
				$size = filesize($filepath);
				
				// Audit
				$eventmodel = new EventModel();
				$eventdetails = $calling_username ." downloaded asset $assetid (".basename($filepath).")";
				$audit = $eventmodel->addEvent(9,$calling_userid,$eventdetails,$assetid);
				
				// Send the headers
				header('Content-Description: File Download');
				header("Content-Type: ".$mimetype);
				header("Content-Length: $size");
				header("Content-Disposition: attachment; filename=\"$originalfilename\"");
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');
				
				session_write_close();
				readfile($filepath);
				exit();
			} else {
				$strErrorCode = -1;
				$strErrorDesc = "The asset file could not be found on the server. Please try again.";
			}
        } else {
            $this->sendOutput(json_encode(array('error' => $strErrorCode,'description'=>$strErrorDesc)), 
                array('Content-Type: application/json', $strErrorHeader)
            );
        }
	}
	

	/**
     * Create a metadata-only asset (i.e. no file)
	 * Endpoint: /api/asset/add
     */
	public function addAction(){
	
		$strErrorDesc = '';
		$strErrorCode = 0;
        $requestMethod = $_SERVER["REQUEST_METHOD"];
		$arrPostParams = $this->getPostParams();
		$data = [];
		$metadata = NULL;
		
		// POST method
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
			
            try {

				// Check if metadata has been posted
				if (isset($arrPostParams['metadata']) && !empty($arrPostParams['metadata'])) {
                    $metadata = $arrPostParams['metadata'];
					$json_valid = Utils::json_validate($metadata);
					if ($json_valid["error"] < 0){
						$this->sendOutput(json_encode(array('error' => -1,'description'=>$json_valid["description"])));
					} else {
						$metadata_ary = json_decode($metadata,true);
						// Overwrite essential metadata fields such as filename, extension and mimetype with empty values
						$metadata_ary["filename"] = NULL;
						$metadata_ary["extension"] = NULL;
						$metadata_ary["mimetype"] = NULL;
						$metadata_ary["filesize"] = 0;
					}
                } else {
					// Construct metadata manually
					$metadata_ary = array(
						"filename"=>NULL,
						"extension"=>NULL,
						"mimetype"=>NULL,
						"filesize"=>0,
						"fullwidth"=>NULL,
						"fullheight"=>NULL,
						"previewwidth"=>NULL,
						"previewheight"=>NULL
					);
				}
				
				// Create metadata extension object if it wasn't POSTed
				if (!array_key_exists("extensions",$metadata_ary)){
					$extensions = new stdClass;
					$extensions->simpledam = new stdClass;
				} else {
					// Convert the decoded extensions JSON array that was POSTed to an object
					$extensions = (object) $metadata_ary["extensions"];
				}
				// Create metadata SimpleDAM extension object if it wasn't POSTed
				if (!isset($metadata_ary["extensions"]["simpledam"])){
					$extensions->simpledam = new stdClass;
				}
				
				// Put description in metadata node
				if (!isset($metadata_ary["extensions"]["simpledam"]["description"])){
					$extensions->simpledam->description = "Default description here";
				}
				
				// Add default values to SimpleDAM metadata extension
				$extensions->simpledam->uploader = $calling_username;
				$extensions->simpledam->views = 0;
				$extensions->simpledam->downloads = 0;
				$metadata_ary["extensions"] = $extensions;

				// Convert to JSON string for database storage
				$metadata = json_encode($metadata_ary);
				// Perform the insert	
				$model = new AssetModel();
				$result = $model->addAsset($calling_userid,NULL,$metadata);
				
				// Error
				if (!$result){
					$strErrorCode = -1;
					$strErrorDesc = 'Could not add asset. Check logs.';
				} else {
					$assetid = $result;
					// Do not expose the actual asset id via the API response
					$data["assetid"] = sha1($result);
					
					// We now need to update the publicassetid (hash), derived from the assetid of the inserted record
					$result2 = $model->updatePublicAssetID($assetid);
					
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
			// Audit
			$eventmodel = new EventModel();
			$eventdetails = $calling_username . " added asset id: ".$assetid;
			$audit = $eventmodel->addEvent(12,$calling_userid,$eventdetails);
			
            $this->sendOutput(
				json_encode(array('error' => $strErrorCode,'description'=>'success','data'=>$data),JSON_UNESCAPED_SLASHES),
                array('Content-Type: application/json', 'HTTP/1.1 200 OK')
            );
        } else {
            $this->sendOutput(json_encode(array('error' => $strErrorCode,'description'=>$strErrorDesc)), 
                array('Content-Type: application/json', $strErrorHeader)
            );
        }
		
	}
	
	/**
     * Upload an asset (file or URL)
	 * Endpoint: /api/asset/upload
     */
	public function uploadAction(){
	
		$strErrorDesc = '';
		$strErrorCode = 0;
        $requestMethod = $_SERVER["REQUEST_METHOD"];
		$arrPostParams = $this->getPostParams();
		$data = [];
		$metadata = NULL;
		$isurl = false;
		$url = '';
		
		// POST method
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
			
            try {
				
				// Check if we're using an upload or a URL
				if (isset($arrPostParams['url']) && !empty($arrPostParams['url'])){
					if (filter_var($arrPostParams['url'], FILTER_VALIDATE_URL) === FALSE) {
						$this->sendOutput(json_encode(array('error' => -1,'description'=>"The URL does not appear to be valid.")));
					}
					$url = $arrPostParams['url'];
					$isurl = true;
				}
				
				// Check the FILE upload for validity
				if (!$isurl){
					
					if (!isset($_FILES['file']['error']) || is_array($_FILES['file']['error'])){
						$this->sendOutput(json_encode(array('error' => -1,'description'=>"Upload error. Please try again.")));
					}
					
					// Check $_FILES['file']['error'] value
					switch ($_FILES['file']['error']) {
						case 0:
							break;
						case 1:
							$strErrorCode = -1;
							$strErrorDesc = 'Exceeded filesize limit.';
							break;
						case 3:
							$strErrorCode = -1;
							$strErrorDesc = 'Partially uploaded. Please try again.';
							break;
						case 4:
							$strErrorCode = -1;
							$strErrorDesc = 'No file detected. Please try again.';
							break;
						case 6:
							$strErrorCode = -1;
							$strErrorDesc = 'No temporary folder.';
							break;
						case 7:
							$strErrorCode = -1;
							$strErrorDesc = 'Could not save to disk.';
							break;
						default:
							$strErrorCode = -1;
							$strErrorDesc = 'Unknown upload error.';
							break;
					}
	
					// Check filesize here against the API's setting
					if ($_FILES['file']['size'] > MAX_UPLOAD_SIZE) {
						$strErrorCode = -1;
						$strErrorDesc = 'Exceeded filesize limit';
					}
					
					// If any errors so far, bail out here
					if ($strErrorCode < 0){
						$this->sendOutput(json_encode(array('error' => -1,'description'=>$strErrorDesc)));
					}
				
				}
				
				// Assign the file a variable, depending upon the method (upload or url)
				
				// If we've been given a URL and it's valid
				if ($isurl){
					$url_file_name = basename($url); // File name - this may contain querystrings etc.
					$url_parts = explode("?",$url_file_name);
					$tmp_file_name = $url_parts[0]; // URL with no parameters
					$tmp_file_ext = pathinfo($tmp_file_name, PATHINFO_EXTENSION); // Extension
					$stated_ext = $tmp_file_ext;
					// See if we can grab the file and put it into our temporary folder
					if (file_put_contents(IMPORT_PATH."/".$tmp_file_name, file_get_contents($url)) !== false) {
						if (file_exists(IMPORT_PATH."/".$tmp_file_name) && is_file(IMPORT_PATH."/".$tmp_file_name)){
							if (empty($tmp_file_ext)){
								$tmp_file_ext = Utils::mime2ext(mime_content_type(IMPORT_PATH."/".$tmp_file_name));
								$stated_ext = $tmp_file_ext;
								rename(IMPORT_PATH."/".$tmp_file_name, IMPORT_PATH."/".$tmp_file_name.".".$tmp_file_ext);
								$tmp_file_name .= ".".$tmp_file_ext;
							}
							$input_file = IMPORT_PATH."/".$tmp_file_name;
						} else {
							// File wasn't written for some reason
							$this->sendOutput(json_encode(array('error' => -1,'description'=>'Could not store the file')));
						}
					} else {
						// File could not be fetched from URL
						$this->sendOutput(json_encode(array('error' => -1,'description'=>'Could not fetch file from URL')));
					}
				// Else we've got a direct upload	
				} else {
					$input_file = $_FILES['file']['tmp_name'];
					$stated_ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
				}
				
				// Do not trust $_FILES['file']['mime'] value - check MIME type manually
				$finfo = new finfo(FILEINFO_MIME_TYPE);
				if (false === $ext = array_search(
					$finfo->file($input_file),
					ALLOWED_MIME_TYPES,
					true
				)) {
					$strErrorDesc = 'The file type is not supported ('.mime_content_type($input_file).')';
					$this->sendOutput(json_encode(array('error' => -1,'description'=>$strErrorDesc)));
				}
				
				// Suffix wasn't derived from detected mime type, bail out
				if (!$ext){
					$strErrorDesc = 'The file type is not supported';
					$this->sendOutput(json_encode(array('error' => -1,'description'=>$strErrorDesc)));
				}
				
				$mimetype = $finfo->file($input_file);
				
				// Create a unique name for the uploaded file
				$original_filename = ($isurl) ? $tmp_file_name : $_FILES['file']['name'];
				$hashed_basename = date('Ymd') . '_' . md5($original_filename . microtime());
				//$hashed_filename = $hashed_basename.".".$ext;
				
				$hashed_filename = ($stated_ext == "ai") ? $hashed_basename.".ai" : $hashed_basename.".".$ext;
				
				$hashed_previewname = $hashed_basename.".jpg"; // Previews are always jpeg
				
				// Store the file in the filesystem with the new hashed filename
				if ($isurl){
					if (!rename($input_file,ASSET_PATH."/".$hashed_filename)){
						$this->sendOutput(json_encode(array('error' => -1,'description'=>"Could not copy file")));
					}
				} else {
					if (!move_uploaded_file($input_file,ASSET_PATH."/".$hashed_filename)) {
						$this->sendOutput(json_encode(array('error' => -1,'description'=>"Could not move uploaded file")));
					}
				}
				
				// At this point our file should be in-site, create a handle for it
				$stored_file = ASSET_PATH."/".$hashed_filename;
				
				// Create thumbnail and preview - only if the input is an image format (jpg, gif, png, bmp, pdf (and ai as pdf))
				$type = explode('/', $mimetype)[0];
				if (ENABLE_PREVIEW_GENERATION && in_array($ext,SUPPORTED_IMAGE_PREVIEW_TYPES)){
				
					if(!Utils::create_preview_image($stored_file,PREVIEW_PATH."/".$hashed_previewname,PREVIEW_SIZE)){
						$errors = true;
						$strErrorCode = 1;
						$strErrorDesc = 'Could not create asset preview. Check logs.';
					}
					
					if(!Utils::create_preview_image($stored_file,THUMBNAIL_PATH."/".$hashed_previewname,THUMBNAIL_SIZE)){
						$errors = true;
						$strErrorCode = 1;
						$strErrorDesc = 'Could not add asset thumbnail. Check logs.';
					}
				}

				// Check if metadata has been posted
				if (isset($arrPostParams['metadata']) && !empty($arrPostParams['metadata'])){
                    $metadata = $arrPostParams['metadata'];
					$json_valid = Utils::json_validate($metadata);
					if ($json_valid["error"] < 0){
						$this->sendOutput(json_encode(array('error' => -1,'description'=>$json_valid["description"])));
					} else {
						$metadata_ary = json_decode($metadata,true);
						// Force essential metadata fields such as filename, extension and mimetype with derived values
						$metadata_ary["filename"] = $original_filename;
						$metadata_ary["extension"] = $ext;
						$metadata_ary["mimetype"] = $mimetype;
						$metadata_ary["filesize"] = filesize($stored_file);
						$metadata_ary["fullwidth"] = NULL;
						$metadata_ary["fullheight"] = NULL;	
						$metadata_ary["previewwidth"] = NULL;
						$metadata_ary["previewheight"] = NULL;
					}
                } else {				
					// Construct metadata manually
					$metadata_ary = array(
						"filename"=>$original_filename,
						"extension"=>($stated_ext == "ai") ? $stated_ext : $ext,
						"mimetype"=>$mimetype,
						"filesize"=>filesize($stored_file),
						"fullwidth"=>NULL,
						"fullheight"=>NULL,
						"previewwidth"=>NULL,
						"previewheight"=>NULL
					);
				}
				
				// If upload is an image, get its EXIF data, detect its dimensions and overwrite corresponding metadata values
				$hasexif = false;
				if (in_array($ext,SUPPORTED_IMAGE_PREVIEW_TYPES)){
					// Check for and extract EXIF data if present and enabled in settings
					if (EXTRACT_UPLOADED_EXIF_DATA){
						$exif = @exif_read_data($stored_file, 'IFD0', true);
						if ($exif){
							foreach($exif as $key=>$value){
								if (!in_array($key,EXIF_DATA_TO_KEEP)){
									unset($exif[$key]);
								}
							}
							// Encode to JSON string to tighten up and get rid of rogue characters
							$exif_json = json_encode($exif);	
							$exif_clean = str_replace("\\u0000", "", $exif_json);
							// Convert back into the metadata exif array for storage
							$hasexif = true;
							$exif_ary = json_decode($exif_clean,true);							
						}
					}
					if ($type == "image"){
						$sizedata = getimagesize($stored_file);
						$metadata_ary["fullwidth"] = $sizedata[0];
						$metadata_ary["fullheight"] = $sizedata[1];
					}
					if (file_exists(PREVIEW_PATH."/".$hashed_previewname) && is_file(PREVIEW_PATH."/".$hashed_previewname)){
						$thumbdata = getimagesize(PREVIEW_PATH."/".$hashed_previewname);
						$metadata_ary["previewwidth"] = $thumbdata[0];
						$metadata_ary["previewheight"] = $thumbdata[1];
					}
				}
				
				// Create metadata extension object if it wasn't POSTed
				if (!array_key_exists("extensions",$metadata_ary)){
					$extensions = new stdClass;
					$extensions->simpledam = new stdClass;
				} else {
					// Metadata 'extensions' JSON node was POSTed, make sure it's last in the metadata array
					$x = $metadata_ary['extensions'];
					unset($metadata_ary['extensions']);
					$metadata_ary['extensions'] = $x;
					// Convert the decoded extensions JSON array that was POSTed to an object
					$extensions = (object) $metadata_ary["extensions"];
				}
				
				// Create metadata SimpleDAM extension object if it wasn't POSTed
				if (!isset($metadata_ary["extensions"]["simpledam"])){
					$extensions->simpledam = new stdClass;
				} else {
					$extensions->simpledam = (object) $metadata_ary["extensions"]["simpledam"];
				}
				// Populate with default description if it wasn't POSTed
				if (!isset($extensions->simpledam->description)){
					$extensions->simpledam->description = "Default description here";
				}
				
				// Add default values to SimpleDAM metadata extension
				$extensions->simpledam->uploader = $calling_username;
				$extensions->simpledam->views = 0;
				$extensions->simpledam->downloads = 0;
				
				// Add EXIF data to extensions
				if ($hasexif){
					unset($metadata_ary["exif"]);
					$extensions->exif = new stdClass;
					$extensions->exif = (object) $exif_ary;
				}
				
				// Add the whole extensions node back into the metadata array
				$metadata_ary["extensions"] = $extensions;

				// Convert to JSON string for database storage
				$metadata = json_encode($metadata_ary);
				
				// Perform the insert	
				$model = new AssetModel();
				$result = $model->addAsset($calling_userid,$hashed_basename,$metadata);
				
				// change the error message here
				if (!$result){
					$strErrorCode = -1;
					$strErrorDesc = 'Could not add asset. Check logs.';
				} else {
					$assetid = $result;
					// Do not expose the actual asset id via the API response
					$data["assetid"] = sha1($result);
					
					// We now need to update the publicassetid (hash), derived from the assetid of the inserted record
					$result2 = $model->updatePublicAssetID($assetid);
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
		
			// Audit
			$eventmodel = new EventModel();
			$eventdetails = $calling_username . " uploaded $original_filename";
			$audit = $eventmodel->addEvent(15,$calling_userid,$eventdetails);
			
            $this->sendOutput(
				json_encode(array('error' => $strErrorCode,'description'=>'success','data'=>$data),JSON_UNESCAPED_SLASHES),
                array('Content-Type: application/json', 'HTTP/1.1 200 OK')
            );
        } else {
            $this->sendOutput(json_encode(array('error' => $strErrorCode,'description'=>$strErrorDesc)), 
                array('Content-Type: application/json', $strErrorHeader)
            );
        }
		
	}
	
	/**
     * Update an asset (optional file or URL)
	 * Endpoint: /api/asset/update
     */
	public function updateAction(){
	
		$strErrorDesc = '';
		$strErrorCode = 0;
        $requestMethod = $_SERVER["REQUEST_METHOD"];
		$arrPostParams = $this->getPostParams();
		$data = [];
		$metadata = NULL;
		$replacing_file = false;			
		$isurl = false;
		$url = '';
		
		// POST method
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
			
			// We need an ID for this operation
			if (!isset($arrPostParams['id']) || empty($arrPostParams['id'])){
				$this->sendOutput(json_encode(array('error' => -1,'description'=>'Parameter Missing')));
			}
			
            try {
			
				$id = $arrPostParams['id'];
				
				$assetmodel = new AssetModel();
                $arr = $assetmodel->getAsset($id);
				$arrHash = $assetmodel->getAssetHash($id);
				
				if (count($arr) < 1){
					$strErrorCode = -1;
					$strErrorDesc = 'Asset not found';
					$this->sendOutput(json_encode(array('error' => $strErrorCode,'description'=>$strErrorDesc)));
				} else {
					// Get the asset from the SQL results array
					$asset = $arr[0];
					$hash = $arrHash[0];
					$original_metadata = json_decode($asset["metadata"],true);
					$original_mimetype = $original_metadata["mimetype"];
					$original_type = (!is_null($original_mimetype)) ? explode('/', $original_mimetype)[0] : NULL;
					$hashed_basename = $hash["hashid"];
				}
				
				// Check if we're using an upload or a URL
				if (isset($arrPostParams['url']) && !empty($arrPostParams['url'])){
					if (filter_var($arrPostParams['url'], FILTER_VALIDATE_URL) === FALSE) {
						$this->sendOutput(json_encode(array('error' => -1,'description'=>"The URL does not appear to be valid.")));
					}
					$url = $arrPostParams['url'];
					$isurl = true;
				}
				
				// Check the FILE upload for validity
				if (!$isurl){
				
					// Have we got a file? Replace that first
					if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
						
						// If this request fails, treat as invalid
						if ( !isset($_FILES['file']['error']) || is_array($_FILES['file']['error'])){
							$this->sendOutput(json_encode(array('error' => -1,'description'=>"Upload error. Please try again.")));
						}
						
						$replacing_file = true;
						
						// Check $_FILES['file']['error'] value
						switch ($_FILES['file']['error']) {
							case 0:
								break;
							case 1:
								$strErrorCode = -1;
								$strErrorDesc = 'Exceeded filesize limit.';
								break;
							case 3:
								$strErrorCode = -1;
								$strErrorDesc = 'Partially uploaded. Please try again.';
								break;
							case 4:
								$strErrorCode = -1;
								$strErrorDesc = 'No file detected. Please try again.';
								break;
							case 6:
								$strErrorCode = -1;
								$strErrorDesc = 'No temporary folder.';
								break;
							case 7:
								$strErrorCode = -1;
								$strErrorDesc = 'Could not save to disk.';
								break;
							default:
								$strErrorCode = -1;
								$strErrorDesc = 'Unknown upload error.';
								break;
						}
		
						// Check filesize here against the API's setting
						if ($_FILES['file']['size'] > MAX_UPLOAD_SIZE) {
							$strErrorCode = -1;
							$strErrorDesc = 'Exceeded filesize limit';
						}
						
						// If any errors so far, bail out here
						if ($strErrorCode < 0){
							$this->sendOutput(json_encode(array('error' => -1,'description'=>$strErrorDesc)));
						}
					}
					
				} // end if not URL field
				
				
				// If we've been given a URL and it's valid
				if ($isurl){
					$url_file_name = basename($url); // File name - this may contain querystrings etc.
					$url_parts = explode("?",$url_file_name);
					$tmp_file_name = $url_parts[0]; // URL with no parameters
					$tmp_file_ext = pathinfo($tmp_file_name, PATHINFO_EXTENSION); // Extension
					$stated_ext = $tmp_file_ext;
					// See if we can grab the file and put it into our temporary folder
					if (file_put_contents(IMPORT_PATH."/".$tmp_file_name, file_get_contents($url)) !== false) {
						if (file_exists(IMPORT_PATH."/".$tmp_file_name) && is_file(IMPORT_PATH."/".$tmp_file_name)){
							if (empty($tmp_file_ext)){
								$tmp_file_ext = Utils::mime2ext(mime_content_type(IMPORT_PATH."/".$tmp_file_name));
								$stated_ext = $tmp_file_ext;
								rename(IMPORT_PATH."/".$tmp_file_name, IMPORT_PATH."/".$tmp_file_name.".".$tmp_file_ext);
								$tmp_file_name .= ".".$tmp_file_ext;
							}
							$input_file = IMPORT_PATH."/".$tmp_file_name;
							$replacing_file = true;
						} else {
							// File wasn't written for some reason
							$this->sendOutput(json_encode(array('error' => -1,'description'=>'Could not store the file')));
						}
					} else {
						// File could not be fetched from URL
						$this->sendOutput(json_encode(array('error' => -1,'description'=>'Could not fetch file from URL')));
					}
				// Else we've got a direct upload	
				} else {
					if ($replacing_file){
						$input_file = $_FILES['file']['tmp_name'];
						$stated_ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
					}
				}
					
				// Do not trust $_FILES['file']['mime'] value - check MIME type manually
				if ($replacing_file){
					
					$finfo = new finfo(FILEINFO_MIME_TYPE);
					if (false === $ext = array_search(
						$finfo->file($input_file),
						ALLOWED_MIME_TYPES,
						true
					)) {
						$strErrorDesc = 'The uploaded file type is not supported';
						$this->sendOutput(json_encode(array('error' => -1,'description'=>$strErrorDesc)));
					}
					
					// Suffix wasn't derived from detected mime type, bail out
					if (!$ext){
						$strErrorDesc = 'The uploaded file type is not supported ()';
						$this->sendOutput(json_encode(array('error' => -1,'description'=>$strErrorDesc)));
					}
					
					$mimetype = $finfo->file($input_file);
					
					// Create a unique name for the uploaded file
					$original_filename = ($isurl) ? $tmp_file_name : $_FILES['file']['name'];
					$hashed_basename = date('Ymd') . '_' . md5($original_filename . microtime());
					//$hashed_filename = $hashed_basename.".".$ext;
					
					$hashed_filename = ($stated_ext == "ai") ? $hashed_basename.".ai" : $hashed_basename.".".$ext;
					
					$hashed_previewname = $hashed_basename.".jpg"; // Previews are always jpegs
					
					// Store the file in the filesystem with the new hashed filename
					if ($isurl){
						if (!rename($input_file,ASSET_PATH."/".$hashed_filename)){
							$this->sendOutput(json_encode(array('error' => -1,'description'=>"Could not copy file")));
						}
					} else {
						if (!move_uploaded_file($input_file,ASSET_PATH."/".$hashed_filename)) {
							$this->sendOutput(json_encode(array('error' => -1,'description'=>"Could not move uploaded file")));
						}
					}
					
					// At this point our file should be in-situ, create a handle for it
					$stored_file = ASSET_PATH."/".$hashed_filename;
					
					// Create thumbnail and preview - only if the input is an image format (jpg, gif, png, bmp, psd)
					$type = explode('/', $mimetype)[0];
					if (ENABLE_PREVIEW_GENERATION && in_array($ext,SUPPORTED_IMAGE_PREVIEW_TYPES)){
					
						if(!Utils::create_preview_image($stored_file,PREVIEW_PATH."/".$hashed_previewname,PREVIEW_SIZE)){
							$errors = true;
							$strErrorCode = -1;
							$strErrorDesc = 'Could not create asset preview ('.PREVIEW_PATH."/".$hashed_previewname.'). Check logs.';
						}		
						
						if(!Utils::create_preview_image($stored_file,THUMBNAIL_PATH."/".$hashed_previewname,THUMBNAIL_SIZE)){
							$errors = true;
							$strErrorCode = -1;
							$strErrorDesc = 'Could not add asset thumbnail ('.THUMBNAIL_PATH."/".$hashed_previewname.'). Check logs.';
						}
						
					}
				
				} // end if replacing file (either method)
				
				// Check if metadata has been posted
				if (isset($arrPostParams['metadata']) && !empty($arrPostParams['metadata'])){
                    $metadata = $arrPostParams['metadata'];
					$json_valid = Utils::json_validate($metadata);
					if ($json_valid["error"] < 0){
						$this->sendOutput(json_encode(array('error' => -1,'description'=>$json_valid["description"])));
					} else {
						// Decode the POSTed metadata into an array
						$metadata_ary = json_decode($metadata,true);
						// If true, merge existing and POSTed metadata (the latter takes precedence), else it's overwritten
						if (MERGE_ASSET_METADATA || isset($arrPostParams['merge'])){
							$metadata_ary = array_merge($original_metadata, $metadata_ary);
						}
						// Force essential metadata fields such as filename, extension and mimetype with derived values
						if ($replacing_file){
							$metadata_ary["filename"] = $original_filename;
							//$metadata_ary["extension"] = $ext;
							$metadata_ary["extension"] = ($stated_ext == "ai") ? $stated_ext : $ext;
							$metadata_ary["mimetype"] = $mimetype;
							$metadata_ary["filesize"] = filesize($stored_file);
							// Reset these (they're processed again below)
							$metadata_ary["fullwidth"] = NULL;
							$metadata_ary["fullheight"] = NULL;	
							$metadata_ary["previewwidth"] = NULL;
							$metadata_ary["previewheight"] = NULL;
						} else {
							// Else NOT replacing file - selectively keep original metadata fields
							//$metadata_ary["filename"] = (array_key_exists("filename",$metadata_ary)) ? $metadata_ary["filename"] : $original_metadata["filename"];
							// Uncomment the line above and comment out the line below to allow the filename to be updated
							$metadata_ary["filename"] = $original_metadata["filename"];	
							$metadata_ary["extension"] = $original_metadata["extension"];
							$metadata_ary["mimetype"] = $original_metadata["mimetype"];
							$metadata_ary["filesize"] = $original_metadata["filesize"];
							// For images only - preserve the original dimensions
							if ($original_type == "image"){
								$metadata_ary["fullwidth"] = $original_metadata["fullwidth"];
								$metadata_ary["fullheight"] = $original_metadata["fullheight"];
								$metadata_ary["previewwidth"] = $original_metadata["previewwidth"];
								$metadata_ary["previewheight"] = $original_metadata["previewheight"];
							}
						}
					}
                } else {			
					// No metadata was POSTed. Construct metadata manually only if replacing file with a new upload
					if ($replacing_file){
						$metadata_ary = array(
							"filename"=>$original_filename,
							//"extension"=>$ext,
							"extension"=>($stated_ext == "ai") ? $stated_ext : $ext,
							"mimetype"=>$mimetype,
							"filesize"=>filesize($stored_file),
							"fullwidth"=>NULL,
							"fullheight"=>NULL,
							"previewwidth"=>NULL,
							"previewheight"=>NULL
						);
					} else {
						// Else no file or metadata was POSTed - use existing asset's metadata
						$metadata_ary = json_decode($asset["metadata"],true);
					}
				}
				
				// If upload is an image, get its EXIF data, detect its dimensions and overwrite corresponding metadata values
				$hasexif = false;
				if ($replacing_file && in_array($ext,SUPPORTED_IMAGE_PREVIEW_TYPES)/*$type == "image"*/){
					// Check for and extract EXIF data if present and enabled in settings
					if (EXTRACT_UPLOADED_EXIF_DATA){
						$exif = @exif_read_data($stored_file, 'IFD0', true);
						if ($exif){
							foreach($exif as $key=>$value){
								if (!in_array($key,EXIF_DATA_TO_KEEP)){
									unset($exif[$key]);
								}
							}
							// Encode to JSON string to tighten up and get rid of rogue characters
							$exif_json = json_encode($exif);	
							$exif_clean = str_replace("\\u0000", "", $exif_json);
							// Convert back into the metadata exif array for storage
							$exif_ary = json_decode($exif_clean,true);
							$hasexif = true;
						}
					}
					if ($type == "image"){
						$sizedata = getimagesize($stored_file);
						$metadata_ary["fullwidth"] = $sizedata[0];
						$metadata_ary["fullheight"] = $sizedata[1];
					}
					if (file_exists(PREVIEW_PATH."/".$hashed_previewname) && is_file(PREVIEW_PATH."/".$hashed_previewname)){
						$thumbdata = getimagesize(PREVIEW_PATH."/".$hashed_previewname);
						$metadata_ary["previewwidth"] = $thumbdata[0];
						$metadata_ary["previewheight"] = $thumbdata[1];
					}
				}
				
				// Create metadata extension object if it wasn't POSTed
				if (!array_key_exists("extensions",$metadata_ary)){
					$extensions = new stdClass;
					$extensions->simpledam = new stdClass;
				} else {
					// Metadata 'extensions' JSON node was POSTed, make sure it's last in the metadata array
					$x = $metadata_ary['extensions'];
					unset($metadata_ary['extensions']);
					$metadata_ary['extensions'] = $x;
					// Convert the decoded extensions JSON array that was POSTed to an object
					$extensions = (object) $metadata_ary["extensions"];
				}
				
				// Create metadata SimpleDAM extension object if it wasn't POSTed
				if (!isset($metadata_ary["extensions"]["simpledam"])){
					$extensions->simpledam = new stdClass;
				} else {
					$extensions->simpledam = (object) $metadata_ary["extensions"]["simpledam"];
				}
				// Populate with default description if it wasn't POSTed
				if (!isset($extensions->simpledam->description)){
					if (isset($original_metadata["extensions"]["simpledam"]["description"])){
						$extensions->simpledam->description = $original_metadata["extensions"]["simpledam"]["description"];
					} else {
						$extensions->simpledam->description = "Default description here";
					}
				}
				// Populate user/uploader if it wasn't POSTed
				if (!isset($extensions->simpledam->uploader)){
					if (isset($original_metadata["extensions"]["simpledam"]["uploader"])){
						$extensions->simpledam->uploader = $original_metadata["extensions"]["simpledam"]["uploader"];
					} else {
						$extensions->simpledam->uploader = $calling_username;
					}
				}
				
				// Add EXIF data to extensions
				if ($hasexif){
					unset($metadata_ary["exif"]);
					$extensions->exif = new stdClass;
					$extensions->exif = (object) $exif_ary;
				}
				
				// Add the whole extensions node back into the metadata array
				$metadata_ary["extensions"] = $extensions;

				// Convert back to JSON string for database storage
				$metadata = json_encode($metadata_ary);
				
				// Perform the update	
				$result = $assetmodel->updateAsset($id,$hashed_basename,$metadata);
				
				// change the error message here
				if (!$result){
					$strErrorCode = -1;
					$strErrorDesc = 'Could not update asset. Check logs.';
				} else {
					$assetid = $asset["assetid"];
					// Do not expose the actual asset id via the API response
					$data["assetid"] = sha1($assetid);
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
			// Audit
			$eventmodel = new EventModel();
			$eventdetails = $calling_username . " updated asset id: ".$assetid;
			$audit = $eventmodel->addEvent(16,$calling_userid,$eventdetails);
			
            $this->sendOutput(
				json_encode(array('error' => $strErrorCode,'description'=>'success','data'=>$data),JSON_UNESCAPED_SLASHES),
                array('Content-Type: application/json', 'HTTP/1.1 200 OK')
            );
        } else {
            $this->sendOutput(json_encode(array('error' => $strErrorCode,'description'=>$strErrorDesc)), 
                array('Content-Type: application/json', 'HTTP/1.1 200 OK')
            );
        }
		
	}
	
	/**
     * Delete an asset
	 * Endpoint: /api/asset/delete
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
		
			// This is an admin only event - kick users out if they attempt this
			if ($user[0]["userroleid"] < 2){
				$this->sendOutput(json_encode(array('error' => -1,'description'=>'Insufficient Privileges')));
			}
		
			if (empty($arrPostParams['id']) || empty($arrPostParams['id']) ){
				$this->sendOutput(json_encode(array('error' => 'Parameter(s) Missing')));
			}
			
            try {
				
				$id = $arrPostParams['id'];
				$assetmodel = new AssetModel();
                $arr = $assetmodel->getAsset($id);
				
				if (count($arr) < 1){
					$strErrorCode = -1;
					$strErrorDesc = 'Asset not found';
					$this->sendOutput(json_encode(array('error' => $strErrorCode,'description'=>$strErrorDesc)));
				} else {
					// Asset exists, delete it
					$asset = $arr[0];
					$result = $assetmodel->deleteAsset($id);
					if (!$result){
						$strErrorCode = -1;
						$strErrorHeader = 'The asset could not be deleted';
					} else {
						$assetid = $asset["assetid"];
						// Do not expose the actual asset id via the API response
						$data["assetid"] = $asset["publicassetid"];	
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
		
		if (!$strErrorDesc) {;
			// Audit
			$eventmodel = new EventModel();
			$eventdetails = $calling_username . " deleted asset id: ".$assetid;
			$audit = $eventmodel->addEvent(17,$calling_userid,$eventdetails);
			
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

	
	/**
     * Import assets
	 * Endpoint: /api/asset/import
     */
	public function importAction(){
		
		$strErrorCode = 0;
		$strErrorDesc = '';
		$strErrorHeader = 'HTTP/1.1 200 OK';
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $arrQueryStringParams = $this->getQueryStringParams();
		
		// Init the feedback
		$data = [];			
		$data["total"] = 0;
		$data["numimported"] = 0;
		$data["numfailed"] = 0;
		$data["imported"] = [];
		$data["failed"] = [];
		
		// GET method
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
			
			if (empty($arrQueryStringParams['source']) || empty($arrQueryStringParams['source']) ){
				$this->sendOutput(json_encode(array('error' => -1,'description'=>'Parameter Missing')));
			}
			
            try {
				// Import method
				if (isset($arrQueryStringParams["source"]) && $arrQueryStringParams["source"] == "local") {
					
					$errors = false;
					
					// Check the local import/watch folder for files
					$assets = glob(IMPORT_PATH."/*.*");
					
					// If no files
					if (count($assets) < 1){
						$this->sendOutput(json_encode(array('error' => 1,'description'=>'No import files were detected')));
					} else {
					// Else iterate and attempt import
						$data["total"] = count($assets);
						foreach ($assets as $filename) {
						
							// Get mimetype and extension
							$finfo = new finfo(FILEINFO_MIME_TYPE);
							if (false === $ext = array_search(
								$finfo->file($filename),
								ALLOWED_MIME_TYPES,
								true
							)) {
								$strErrorDesc = 'The file type is not supported ('.mime_content_type($filename).')';
								$this->sendOutput(json_encode(array('error' => -1,'description'=>$strErrorDesc)));
							}
							
							// Suffix wasn't derived from detected mime type, bail out
							if (!$ext){
								$strErrorDesc = 'The file type is not supported';
								$this->sendOutput(json_encode(array('error' => -1,'description'=>$strErrorDesc)));
							}	
							$mimetype = $finfo->file($filename);
							
														
							// Create a unique name for the imported file
							$original_filename = basename($filename);
							$hashed_basename = date('Ymd') . '_' . md5($original_filename . microtime());
							$hashed_filename = $hashed_basename.".".$ext;
							$hashed_previewname = $hashed_basename.".jpg";
							$datecreated = date("Y-m-d H:i:s");
							$filesize = filesize($filename);
							$metadata = NULL;
							
							// Create thumbnail and preview - only if the input is an image format (jpg, gif, png, bmp)
							$type = explode('/', $mimetype)[0];
							
							if (ENABLE_PREVIEW_GENERATION && in_array($ext,SUPPORTED_IMAGE_PREVIEW_TYPES)){
							
								if(!Utils::create_preview_image($filename,PREVIEW_PATH."/".$hashed_previewname,PREVIEW_SIZE)){
									$errors = true;
									$strErrorCode = 1;
									$strErrorDesc = 'Could not create asset preview. Check logs.';
								}
								
								if(!Utils::create_preview_image($filename,THUMBNAIL_PATH."/".$hashed_previewname,THUMBNAIL_SIZE)){
									$errors = true;
									$strErrorCode = 1;
									$strErrorDesc = 'Could not add asset thumbnail. Check logs.';
								}
							}
							
							// Build metadata stub
							$metadata_ary = array(
								"filename"=>$original_filename,
								"extension"=>$ext,
								"mimetype"=>$mimetype,
								"filesize"=>$filesize,
								"fullwidth"=>NULL,
								"fullheight"=>NULL,
								"previewwidth"=>NULL,
								"previewheight"=>NULL
							);
							
							// If upload is an image, get its EXIF data, detect its dimensions and overwrite corresponding metadata values
							$hasexif = false;
							if (in_array($ext,SUPPORTED_IMAGE_PREVIEW_TYPES)){
								// Check for and extract EXIF data if present and enabled in settings
								if (EXTRACT_UPLOADED_EXIF_DATA){
									$exif = @exif_read_data($filename, 'IFD0', true);
									if ($exif){
										foreach($exif as $key=>$value){
											if (!in_array($key,EXIF_DATA_TO_KEEP)){
												unset($exif[$key]);
											}
										}
										// Encode to JSON string to tighten up and get rid of rogue characters
										$exif_json = json_encode($exif);	
										$exif_clean = str_replace("\\u0000", "", $exif_json);
										// Convert back into the metadata exif array for storage
										$hasexif = true;
										$exif_ary = json_decode($exif_clean,true);										
									}
								}
								if ($type == "image"){
									$sizedata = getimagesize($filename);
									$metadata_ary["fullwidth"] = $sizedata[0];
									$metadata_ary["fullheight"] = $sizedata[1];
								}
								if (file_exists(PREVIEW_PATH."/".$hashed_previewname) && is_file(PREVIEW_PATH."/".$hashed_previewname)){
									$thumbdata = getimagesize(PREVIEW_PATH."/".$hashed_previewname);
									$metadata_ary["previewwidth"] = $thumbdata[0];
									$metadata_ary["previewheight"] = $thumbdata[1];
								}
							}
							
							// Additional metadata extensions etc.
							$extensions = new stdClass;
							$extensions->simpledam = new stdClass;
							$extensions->simpledam = new stdClass;
							$extensions->simpledam->description = "Default description here";
							// Add default values to SimpleDAM metadata extension
							$extensions->simpledam->uploader = $calling_username;
							$extensions->simpledam->views = 0;
							$extensions->simpledam->downloads = 0;
							
							// Add EXIF data to extensions
							if ($hasexif){
								unset($metadata_ary["exif"]);
								$extensions->exif = new stdClass;
								$extensions->exif = (object) $exif_ary;
							}
							
							// Add the whole extensions node back into the metadata array
							$metadata_ary["extensions"] = $extensions;
			
							// Convert to JSON string for database storage
							$metadata = json_encode($metadata_ary);
		
							// If no errors, move file and add to database
							if (!$errors){
								// Add asset via API
								$model = new AssetModel();
								$result = $model->addAsset($calling_userid,$hashed_basename,$metadata);
		
								if (!$result){
									$data["numfailed"] += 1;
									$data["failed"][] = basename($filename);
									Utils::debug("Could not insert imported asset into database (".basename($filename).")");
								} else {
									// Successful import here
									$assetid = $result;									
									// We now need to update the publicassetid (hash), derived from the assetid of the inserted record
									$result2 = $model->updatePublicAssetID($assetid);
									
									// Update import stats
									$data["numimported"] += 1;
									$data["imported"][] = array("assetid"=>$assetid,"filename"=>basename($filename));
									
									// Audit
									$eventmodel = new EventModel();
									$eventdetails = $calling_username." imported local asset ".basename($filename)." (id: $assetid)";
									$audit = $eventmodel->addEvent(11,$calling_userid,$eventdetails);
									
									// Move original file from import to original file
									rename($filename, ASSET_PATH."/".$hashed_filename);
									
								}
							} else {
								$strErrorCode = 1;
								$strErrorDesc = 'At least one file failed to import. Check results.';
								$data["numfailed"] += 1;
								$data["failed"][] = basename($filename);
								Utils::debug("Could not import asset (".basename($filename).")");
							}
						}
						if (!$errors || $strErrorCode == 0){
							$strErrorDesc = 'The import was successfully completed';
						}
					}
				} else {
					// No other import methods are available
					$strErrorCode = -1;
					$strErrorDesc = 'There are no import methods or they are currently disabled';
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
		
		$this->sendOutput(json_encode(array('error' => $strErrorCode,'description'=>$strErrorDesc, 'data'=>$data),JSON_UNESCAPED_SLASHES), 
			array('Content-Type: application/json', $strErrorHeader)
		);
		
	}
	
	/**
     * Endpoint - Export assets
	 *
	 * @param string $destination Destination for assets Default: json
	 * @param string $filename Optional. Name of exported file Default: SimpleDAM-Export-yyyy-mm-dd-hh-mm-ss.json
	 * @param string $zip Optional. Zip up exported file Default: false
     */
	public function exportAction(){
	
		set_time_limit(300);
		$strErrorDesc = '';
		$strErrorCode = 0;
		$strErrorHeader = 'HTTP/1.1 200 OK';
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $arrQueryStringParams = $this->getQueryStringParams();
		$filters = [];
		$suffix = "json";
		$allowed_formats = array("json","csv");

		// GET method
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
			
			if (empty($arrQueryStringParams['destination']) || empty($arrQueryStringParams['destination']) ){
				$this->sendOutput(json_encode(array('error' => -1, "description"=> 'Parameter(s) Missing')));
			}
			
            try {
			
				// Destination - Only json/csv at the moment
				if (isset($arrQueryStringParams['destination']) && $arrQueryStringParams['destination']) {
					$filters["destination"] = $arrQueryStringParams['destination'];
				}
				
				// Zip?
				if (isset($arrQueryStringParams['zip']) && $arrQueryStringParams['zip']) {
					$filters["zip"] = $arrQueryStringParams['zip'];
				}
				
				// Init
				$res = [];
				$model = new AssetModel();
				$mydb = new Database();
				$eventmodel = new EventModel();
				$exported = [];
				$failed = [];
				
				// Get all the assets from the database
				$numAssets = $model->listAssets(0,1,"assetid","asc",NULL,true);
				$total = count($numAssets);
				$assets2go = $model->listAssets(0,$total,"assetid","asc",NULL);
				
				// Put assets into an array
				foreach($assets2go as $asset){
					$asset["metadata"] = json_decode(str_replace("\u0000","",$asset["metadata"]));
					$res[] = $asset;
				}
				// Process for specified format
				if ($filters["destination"] == "json"){
					$data = json_encode($res, JSON_UNESCAPED_SLASHES);
					$suffix = "json";
				} else if ($filters["destination"] == "csv"){
					$data = Utils::array2csv($res);
					$suffix = "csv";
				}
				$filename = "SimpleDAM-Export-".date("Y-m-d-H-i-s");
				
				// If filename is set in parameters
				if (isset($arrQueryStringParams['filename']) && !empty($arrQueryStringParams['filename']) ) {
					$filename = Utils::sanitize_title_with_dashes($arrQueryStringParams['filename']);
				}
				
				// If zip option is set and is true, zip up the JSON
				if (isset($filters["zip"]) && $filters["zip"] == "true"){
					// Prepare File
					$file = tempnam("tmp", "zip");
					$zip = new ZipArchive();
					$zip->open($file, ZipArchive::OVERWRITE);
					// Stuff with content
					$zip->addFromString($filename.".$suffix", $data);
	
					// Close and send to users
					$zip->close();
					header('Content-Type: application/zip');
					header('Content-Length: ' . filesize($file));
					header('Content-Disposition: attachment; filename="'.$filename.'.zip"');
					readfile($file);
					unlink($file);
				} else {			
					// No zip requested, send the headers
					$filename = $filename.".$suffix";
					header('Content-Description: File Download');
					if ($filters["destination"] == "json"){
						header('Content-Type: application/json');
					} else if ($filters["destination"] == "csv"){
						header('Content-Type: text/csv');
					}
					header("Content-Length: ".strlen($data));
					header("Content-Disposition: attachment; filename=\"$filename\"");
					header('Expires: 0');
					header('Cache-Control: must-revalidate');
					header('Pragma: public');
					echo $data;
				}
				
				// Audit
				$eventdetails = $calling_username." exported assets";
				$audit = $eventmodel->addEvent(10,$calling_userid,$eventdetails);		
				$strErrorDesc = 'success';
				// Exit - we don't want to send an API response on successful export (the payload is the file)
				exit();
		
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
		
		$this->sendOutput(json_encode(array('error' => $strErrorCode,'description'=>$strErrorDesc)), 
			array('Content-Type: application/json', $strErrorHeader)
		);
		
	}
}

?>