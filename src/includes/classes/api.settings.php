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

// Don't forget to add .htaccess to the includes folder to prevent public access to this file!
define("SITE_TITLE","SimpleDAM");

// Database credentials
define("DB_HOST", "localhost");
define("DB_USERNAME", "your_db_username");
define("DB_PASSWORD", "your_db_password");
define("DB_DATABASE_NAME", "simpledam");
define("DB_TABLE_PREFIX", "simpledam");
define("TABLE_PREFIX",DB_TABLE_PREFIX."_");

// Datebase tables
define("ASSETS",TABLE_PREFIX."assets");
define("USERS",TABLE_PREFIX."users");
define("USER_ROLES",TABLE_PREFIX."user_roles");
define("SESSIONS",TABLE_PREFIX."sessions");
define("EVENTS",TABLE_PREFIX."events");
define("EVENT_TYPES",TABLE_PREFIX."event_types");

// URLs & Paths
define("SITE_URL","https://yourdomainname.com");
define("BASE_PATH","/var/www/simpledam");
define("SITE_PATH",$_SERVER['DOCUMENT_ROOT']); //htdocs
define("INCLUDE_PATH",BASE_PATH."/includes");
define("PLUGIN_PATH",INCLUDE_PATH."/plugins");
define("API_PATH",SITE_PATH."/api");
define("API_URL",SITE_URL."/api");
define("ASSET_PATH",BASE_PATH."/assets");
define("ORIGINAL_PATH",ASSET_PATH);
define("PREVIEW_PATH",BASE_PATH."/preview");
define("THUMBNAIL_PATH",BASE_PATH."/thumbnail");
define("IMPORT_PATH",BASE_PATH."/import");
define("LOG_PATH",BASE_PATH."/logs");

// Email, Logging & Miscellaneous Settings
define("LOG_NOTIFY_EMAIL",array("mail@example.com"));
define("DEFAULT_SENDER_NAME","SimpleDAM");
define("NO_REPLY_EMAIL","noreply@example.com");
define("ADMIN_EMAIL",array("admin@example.com"));

define("DEBUG",false);
define("LOG_TO_FILE",true);
define("API_LOGGING",true);
define("API_LOG_ACTIONS_TO_IGNORE",array("login","logout","checksession","preview","thumbnail","embed"));
define("SESSION_DURATION",43200); // For API session tokens, not PHP's $_SESSION
define("PLUGINS_ENABLED",true);
define("IMPORT_ENABLED",false);

// Pagination
define("DEFAULT_PER_PAGE",20);

// Uploads
define("MAX_UPLOAD_SIZE",52428800); // 50 Megabytes

// Allowed MIME type
define("ALLOWED_MIME_TYPES",array(
	// Image
	'jpg' => 'image/jpeg',
	'png' => 'image/png',
	'gif' => 'image/gif',
	'bmp' => 'image/bmp',
	'wbmp'=> 'image/vnd.wap.wbmp',	
	'webp'=> 'image/webp',
	'xbm' => 'image/xbm',
	'psd' => 'image/psd',
	'psd' => 'image/vnd.adobe.photoshop',
	'tiff'=> 'image/tiff',
	'jp2' => 'image/jp2',
	'iff' => 'image/iff',
	'ico' => 'image/vnd.microsoft.icon',
	// Video
	'avi' => 'video/x-msvideo',
	'm4v' => 'video/mp4',
	'm4v' => 'video/x-mp4',
	'mov' => 'video/quicktime',
	'mpeg'=> 'video/mpeg',
	'mp4' => 'video/mp4',
	'3gp' => 'video/3gpp',
	// Audio
	'm4a' => 'audio/m4a',
	'm4a' => 'audio/x-m4a',
	'mp3' => 'audio/mpeg',
	'wav' => 'audio/wav',
	'ogg' => 'audio/ogg',
	'flac'=> 'audio/x-flac',
	// Text
	'csv' => 'text/csv',
	'txt' => 'text/plain',
	// Documents and others
	'json'=> 'application/json',
	'doc' => 'application/msword',
	'docx'=> 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
	'pdf' => 'application/pdf',
	'rtf' => 'application/rtf',
	'xls' => 'application/vnd.ms-excel',
	'xlsx'=> 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
	'zip' => 'application/zip'				
));

// Create image previews
define("ENABLE_PREVIEW_GENERATION",true);

// Formats that support creating image-based previews (using GD, ImageMagick or FFMPEG)
define("SUPPORTED_IMAGE_PREVIEW_TYPES",array("jpg","png","gif","bmp","xbm","webp","wbmp","psd","pdf","mp4"));

// Formats that can be viewed in a browser (without ImageMagick or FFMPEG)
define("SUPPORTED_BROWSER_TYPES",array("jpg","png","gif","bmp","xbm","webp","wbmp","mp3","m4a","mp4","ogg","pdf","txt","json"));

// Preview image sizes
define("THUMBNAIL_SIZE",150);
define("PREVIEW_SIZE",350);

// Placeholder images for various preview states
define("NO_THUMBNAIL_USE_PLACEHOLDER_IMAGE",false);
define("NO_PREVIEW_USE_PLACEHOLDER_IMAGE",false);
define("NO_BINARY_DATA_USE_PLACEHOLDER_IMAGE",false);
define("NO_EMBED_USE_PLACEHOLDER_IMAGE",false);

// EXIF and metadata
define("EXTRACT_UPLOADED_EXIF_DATA",true);
define("EXIF_DATA_TO_KEEP",array("IFD0","EXIF"));
define("MERGE_ASSET_METADATA",false);
?>