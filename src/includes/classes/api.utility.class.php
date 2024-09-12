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

class Utils extends ApiBaseController
{

	// Regular log writer
	public static function debug($message){
	
		if (DEBUG) {
			echo($message."<br />");
		}
		if (LOG_TO_FILE) {
			return (new self)->utils_prv_writelogentry($message);
		}
		return true;
	}
	
	// API transaction log writer
	public static function logapi($message){
		if (API_LOGGING) {
			return (new self)->utils_prv_write_api_log($message);
		}
		return true;
	}
	
	// Write a line to the debug log - should be a private method controlled by debug
	private function utils_prv_writelogentry($message){
		
		$logname = $this->sanitize_title_with_dashes(SITE_TITLE);
		$logfile = $logname."_log_".date("d_m_Y").".txt";
		
		$_SERVER['REMOTE_ADDR'] = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : "127.0.0.1";
		
		// Get name of script
		$currentFile = $_SERVER["SCRIPT_NAME"];
		$parts = explode('/', $currentFile);
		$currentFile = $parts[count($parts) - 2] ."/". $parts[count($parts) - 1];
	
		$the_string = (date('H:i:s'))."\t".$currentFile."\t".$_SERVER['REMOTE_ADDR']."\t".$message."\r\n"; 
		
		if($fh = @fopen(LOG_PATH."/".$logfile, "a+") ){
			fputs($fh, $the_string, strlen($the_string));
			fclose($fh);
			// Change the permissions if mail is the log owner and its permission is 0644	
			$logPerms = substr(sprintf('%o', fileperms(LOG_PATH."/".$logfile)), -4);
			$logOwner = posix_getpwuid(fileowner(LOG_PATH."/".$logfile));
			$logOwner = $logOwner['name'];
			
			if($logPerms != "0666" && $logOwner == "mail"){
				chmod(LOG_PATH."/".$logfile, 0666);
			}
			if($logPerms != "0666" && $logOwner == "www-data"){
				chmod(LOG_PATH."/".$logfile, 0666);
			}
			
			return true;
		} else {
			return false;
		}	
	}
	
	// Write a line to the API transaction log - should be a private method controlled by logapi
	private function utils_prv_write_api_log($message){
		
		$logfile = "api_log_".date("d_m_Y").".txt";
		
		$_SERVER['REMOTE_ADDR'] = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : "127.0.0.1";
	
		$the_string = (date('H:i:s'))."\t".$_SERVER['REMOTE_ADDR']."\t".$message."\r\n"; 
		
		if($fh = @fopen(LOG_PATH."/".$logfile, "a+") ){
			fputs($fh, $the_string, strlen($the_string));
			fclose($fh);
			// Change the permissions if mail is the log owner and its permission is 0644	
			$logPerms = substr(sprintf('%o', fileperms(LOG_PATH."/".$logfile)), -4);
			$logOwner = posix_getpwuid(fileowner(LOG_PATH."/".$logfile));
			$logOwner = $logOwner['name'];
			
			if($logPerms != "0666" && $logOwner == "mail"){
				chmod(LOG_PATH."/".$logfile, 0666);
			}
			if($logPerms != "0666" && $logOwner == "www-data"){
				chmod(LOG_PATH."/".$logfile, 0666);
			}
			
			return true;
		} else {
			return false;
		}	
	}

	public static function sanitize_title_with_dashes($title, $raw_title = '', $context = 'display') {
		$title = strip_tags($title);
		// Preserve escaped octets.
		$title = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $title);
		// Remove percent signs that are not part of an octet.
		$title = str_replace('%', '', $title);
		// Restore octets.
		$title = preg_replace('|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $title);
	
		if (self::seems_utf8($title)) {
			if (function_exists('mb_strtolower')) {
				$title = mb_strtolower($title, 'UTF-8');
			}
			$title = self::utf8_uri_encode($title, 200);
		}
	
		$title = strtolower($title);
		$title = preg_replace('/&.+?;/', '', $title); // kill entities
		$title = str_replace('.', '-', $title);
	
		if ( 'save' == $context ) {
			// Convert nbsp, ndash and mdash to hyphens
			$title = str_replace( array( '%c2%a0', '%e2%80%93', '%e2%80%94' ), '-', $title );
	
			// Strip these characters entirely
			$title = str_replace( array(
				// iexcl and iquest
				'%c2%a1', '%c2%bf',
				// angle quotes
				'%c2%ab', '%c2%bb', '%e2%80%b9', '%e2%80%ba',
				// curly quotes
				'%e2%80%98', '%e2%80%99', '%e2%80%9c', '%e2%80%9d',
				'%e2%80%9a', '%e2%80%9b', '%e2%80%9e', '%e2%80%9f',
				// copy, reg, deg, hellip and trade
				'%c2%a9', '%c2%ae', '%c2%b0', '%e2%80%a6', '%e2%84%a2',
				// grave accent, acute accent, macron, caron
				'%cc%80', '%cc%81', '%cc%84', '%cc%8c',
			), '', $title );
	
			// Convert times to x
			$title = str_replace( '%c3%97', 'x', $title );
		}
	
		$title = preg_replace('/[^%a-z0-9 _-]/', '', $title);
		$title = preg_replace('/\s+/', '-', $title);
		$title = preg_replace('|-+|', '-', $title);
		$title = trim($title, '-');
		return $title;
	}
	
	public static function seems_utf8($str) {
		$length = strlen($str);
		for ($i=0; $i < $length; $i++) {
			$c = ord($str[$i]);
			if ($c < 0x80) $n = 0; # 0bbbbbbb
			elseif (($c & 0xE0) == 0xC0) $n=1; # 110bbbbb
			elseif (($c & 0xF0) == 0xE0) $n=2; # 1110bbbb
			elseif (($c & 0xF8) == 0xF0) $n=3; # 11110bbb
			elseif (($c & 0xFC) == 0xF8) $n=4; # 111110bb
			elseif (($c & 0xFE) == 0xFC) $n=5; # 1111110b
			else return false; # Does not match any model
			for ($j=0; $j<$n; $j++) { # n bytes matching 10bbbbbb follow ?
				if ((++$i == $length) || ((ord($str[$i]) & 0xC0) != 0x80))
					return false;
			}
		}
		return true;
	}
	
	public static function utf8_uri_encode( $utf8_string, $length = 0 ) {
		$unicode = '';
		$values = array();
		$num_octets = 1;
		$unicode_length = 0;
		$string_length = strlen( $utf8_string );
		for ($i = 0; $i < $string_length; $i++ ) {
	
			$value = ord( $utf8_string[ $i ] );
	
			if ( $value < 128 ) {
				if ( $length && ( $unicode_length >= $length ) )
					break;
				$unicode .= chr($value);
				$unicode_length++;
			} else {
				if ( count( $values ) == 0 ) $num_octets = ( $value < 224 ) ? 2 : 3;
	
				$values[] = $value;
	
				if ( $length && ( $unicode_length + ($num_octets * 3) ) > $length )
					break;
				if ( count( $values ) == $num_octets ) {
					if ($num_octets == 3) {
						$unicode .= '%' . dechex($values[0]) . '%' . dechex($values[1]) . '%' . dechex($values[2]);
						$unicode_length += 9;
					} else {
						$unicode .= '%' . dechex($values[0]) . '%' . dechex($values[1]);
						$unicode_length += 6;
					}
	
					$values = array();
					$num_octets = 1;
				}
			}
		}
	
		return $unicode;
	}
	
	// Create a thumbnail or preview from given asset
	public static function create_preview_image($src, $dest, $desired_dimension_size) {
		
		$ext = pathinfo($src, PATHINFO_EXTENSION);
		$filename_only = pathinfo($src, PATHINFO_FILENAME);
		
		// If the GD extension isn't installed, bail out
		if (!extension_loaded('gd')){
			return false;
		}
		
		/* Read the source image */
		$source_image = false;
		if ($ext == "jpg" || $ext == "jpeg"){
			$source_image = @imagecreatefromjpeg($src);
		}
		if ($ext == "png"){
			$source_image = @imagecreatefrompng($src);
		}
		if ($ext == "gif"){
			$source_image = @imagecreatefromgif($src);
		}
		if ($ext == "wbmp"){
			$source_image = @imagecreatefromwbmp($src);
		}
		if ($ext == "xbm"){
			$source_image = @imagecreatefromxbm($src);
		}
		// PHP 7 is required for these
		if ($ext == "bmp"){
			$source_image = @imagecreatefrombmp($src);
		}
		if ($ext == "webp"){
			$source_image = @imagecreatefromwebp($src);
		}
		// If Imagick is installed, attempt to create a preview/thumbnail for PSD, PDF and MP4 files
		if (extension_loaded('imagick') && ($ext == "psd" || $ext == "pdf" || $ext == "mp4" || $ext == "ai")){
			$img = self::file2jpeg($src);
			$source_image = @imagecreatefromjpeg($img);
		}
		
		// Could not read source image
		if (!$source_image){
			return false;
		}
		
		// Rotate image if exif orientation set	
		$exif = @exif_read_data($src);
		
		if(!empty($exif['Orientation'])) {
			switch($exif['Orientation']) {
			case 8:
				$source_image = imagerotate($source_image,90,0);
				break;
			case 3:
				$source_image = imagerotate($source_image,180,0);
				break;
			case 6:
				$source_image = imagerotate($source_image,-90,0);
				break;
			} 
		}
		
		$width = imagesx($source_image);
		$height = imagesy($source_image);
		
		// Check if image is portrait or landscape
		if ($width > $height){
			$orientation = "landscape";
			$desired_height = floor($height * ($desired_dimension_size / $width));
			$desired_width = $desired_dimension_size;
		} else {
			$orientation = "portrait";
			$desired_width = floor($width * ($desired_dimension_size / $height));
			$desired_height = $desired_dimension_size;
		}
	
		/* create a new, "virtual" image */
		$virtual_image = imagecreatetruecolor($desired_width, $desired_height);
		imagefill($virtual_image, 0, 0, imagecolorallocate($virtual_image, 255, 255, 255));
	
		/* copy source image at a resized size */
		imagecopyresampled($virtual_image, $source_image, 0, 0, 0, 0, $desired_width, $desired_height, $width, $height);
	
		/* create the physical thumbnail image to its destination */
		imagejpeg($virtual_image, $dest);
		
		return true;
	}
	
		
	// Imagick create preview image from misc. file types
	private static function file2jpeg($src){
		$im = new Imagick($src);
		
		$im->setimageindex(0);
		
		$im->setiteratorindex (0);
		
		// Remove picture information
		$im->stripimage(); 
		
		//Image quality
		$im->setimagecompressionquality(80);
		
		// Write to import folder as temporary measure
		$dest = IMPORT_PATH."/".basename($src).".jpg";
		$im->writeimage($dest);
		
		chmod($dest, 0755);
		
		return $dest;
	}
	
	// Utility function to convert PHP array to CSV
	public static function array2csv(array &$array) {
		if (count($array) == 0) {
			return null;
		}
		ob_start();
		$array = json_decode( json_encode($array), true);
		$df = fopen("php://output", 'wb');
		fputcsv($df, array_keys(reset($array)));
		// Encode the JSON as base64 to preserve formatting - this needs to be converted back at the user end
		foreach ($array as $fields) {
			$fields["metadata"] = base64_encode(json_encode($fields["metadata"]));
			fputcsv($df, $fields);
		}
		fclose($df);
		return ob_get_clean();
	}
	
	// Validate JSON
	public static function json_validate($string){
		// Decode the JSON data
		$result = json_decode($string);
	
		// Switch and check possible JSON errors
		switch (json_last_error()) {
			case JSON_ERROR_NONE:
				$error = ''; // JSON is valid // No error has occurred
				break;
			case JSON_ERROR_DEPTH:
				$error = 'The maximum stack depth has been exceeded.';
				break;
			case JSON_ERROR_STATE_MISMATCH:
				$error = 'Invalid or malformed JSON.';
				break;
			case JSON_ERROR_CTRL_CHAR:
				$error = 'Control character error, possibly incorrectly encoded.';
				break;
			case JSON_ERROR_SYNTAX:
				$error = 'Syntax error, malformed JSON.';
				break;
			// PHP >= 5.3.3
			case JSON_ERROR_UTF8:
				$error = 'Malformed UTF-8 characters, possibly incorrectly encoded.';
				break;
			// PHP >= 5.5.0
			case JSON_ERROR_RECURSION:
				$error = 'One or more recursive references in the value to be encoded.';
				break;
			// PHP >= 5.5.0
			case JSON_ERROR_INF_OR_NAN:
				$error = 'One or more NAN or INF values in the value to be encoded.';
				break;
			case JSON_ERROR_UNSUPPORTED_TYPE:
				$error = 'A value of a type that cannot be encoded was given.';
				break;
			default:
				$error = 'Unknown JSON error occured.';
				break;
		}
	
		if ($error !== '') {
			// Throw the Exception
			return array("error"=>-1,"description"=>$error);
		}
		// Everything is OK
		return array("error"=>0,"description"=>"success");
	}
	
	public static function generateCallTrace() {
		$e = new Exception();
		$trace = explode("\n", $e->getTraceAsString());
		// Reverse array to make steps line up chronologically
		$trace = array_reverse($trace);
		array_shift($trace); // Remove {main}
		array_pop($trace); // Remove call to this method
		$length = count($trace);
		$result = array();
	   
		for ($i = 0; $i < $length; $i++){
			$result[] = '(' . ($i + 1)  . ')' . substr($trace[$i], strpos($trace[$i], ' '));
		}
	   
		return "\t" . implode(" ", $result);
	}
	
	public static function mime2ext($mime) {
		$mime_map = [
			'video/3gpp2'                                                               => '3g2',
			'video/3gp'                                                                 => '3gp',
			'video/3gpp'                                                                => '3gp',
			'application/x-compressed'                                                  => '7zip',
			'audio/x-acc'                                                               => 'aac',
			'audio/ac3'                                                                 => 'ac3',
			'application/postscript'                                                    => 'ai',
			'audio/x-aiff'                                                              => 'aif',
			'audio/aiff'                                                                => 'aif',
			'audio/x-au'                                                                => 'au',
			'video/x-msvideo'                                                           => 'avi',
			'video/msvideo'                                                             => 'avi',
			'video/avi'                                                                 => 'avi',
			'application/x-troff-msvideo'                                               => 'avi',
			'application/macbinary'                                                     => 'bin',
			'application/mac-binary'                                                    => 'bin',
			'application/x-binary'                                                      => 'bin',
			'application/x-macbinary'                                                   => 'bin',
			'image/bmp'                                                                 => 'bmp',
			'image/x-bmp'                                                               => 'bmp',
			'image/x-bitmap'                                                            => 'bmp',
			'image/x-xbitmap'                                                           => 'bmp',
			'image/x-win-bitmap'                                                        => 'bmp',
			'image/x-windows-bmp'                                                       => 'bmp',
			'image/ms-bmp'                                                              => 'bmp',
			'image/x-ms-bmp'                                                            => 'bmp',
			'application/bmp'                                                           => 'bmp',
			'application/x-bmp'                                                         => 'bmp',
			'application/x-win-bitmap'                                                  => 'bmp',
			'application/cdr'                                                           => 'cdr',
			'application/coreldraw'                                                     => 'cdr',
			'application/x-cdr'                                                         => 'cdr',
			'application/x-coreldraw'                                                   => 'cdr',
			'image/cdr'                                                                 => 'cdr',
			'image/x-cdr'                                                               => 'cdr',
			'zz-application/zz-winassoc-cdr'                                            => 'cdr',
			'application/mac-compactpro'                                                => 'cpt',
			'application/pkix-crl'                                                      => 'crl',
			'application/pkcs-crl'                                                      => 'crl',
			'application/x-x509-ca-cert'                                                => 'crt',
			'application/pkix-cert'                                                     => 'crt',
			'text/css'                                                                  => 'css',
			'text/x-comma-separated-values'                                             => 'csv',
			'text/comma-separated-values'                                               => 'csv',
			'application/vnd.msexcel'                                                   => 'csv',
			'application/x-director'                                                    => 'dcr',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'docx',
			'application/x-dvi'                                                         => 'dvi',
			'message/rfc822'                                                            => 'eml',
			'application/x-msdownload'                                                  => 'exe',
			'video/x-f4v'                                                               => 'f4v',
			'audio/x-flac'                                                              => 'flac',
			'video/x-flv'                                                               => 'flv',
			'image/gif'                                                                 => 'gif',
			'application/gpg-keys'                                                      => 'gpg',
			'application/x-gtar'                                                        => 'gtar',
			'application/x-gzip'                                                        => 'gzip',
			'application/mac-binhex40'                                                  => 'hqx',
			'application/mac-binhex'                                                    => 'hqx',
			'application/x-binhex40'                                                    => 'hqx',
			'application/x-mac-binhex40'                                                => 'hqx',
			'text/html'                                                                 => 'html',
			'image/x-icon'                                                              => 'ico',
			'image/x-ico'                                                               => 'ico',
			'image/vnd.microsoft.icon'                                                  => 'ico',
			'text/calendar'                                                             => 'ics',
			'application/java-archive'                                                  => 'jar',
			'application/x-java-application'                                            => 'jar',
			'application/x-jar'                                                         => 'jar',
			'image/jp2'                                                                 => 'jp2',
			'video/mj2'                                                                 => 'jp2',
			'image/jpx'                                                                 => 'jp2',
			'image/jpm'                                                                 => 'jp2',
			'image/jpeg'                                                                => 'jpg',
			'image/pjpeg'                                                               => 'jpg',
			'application/x-javascript'                                                  => 'js',
			'application/json'                                                          => 'json',
			'text/json'                                                                 => 'json',
			'application/vnd.google-earth.kml+xml'                                      => 'kml',
			'application/vnd.google-earth.kmz'                                          => 'kmz',
			'text/x-log'                                                                => 'log',
			'audio/x-m4a'                                                               => 'm4a',
			'audio/mp4'                                                                 => 'm4a',
			'application/vnd.mpegurl'                                                   => 'm4u',
			'audio/midi'                                                                => 'mid',
			'application/vnd.mif'                                                       => 'mif',
			'video/quicktime'                                                           => 'mov',
			'video/x-sgi-movie'                                                         => 'movie',
			'audio/mpeg'                                                                => 'mp3',
			'audio/mpg'                                                                 => 'mp3',
			'audio/mpeg3'                                                               => 'mp3',
			'audio/mp3'                                                                 => 'mp3',
			'video/mp4'                                                                 => 'mp4',
			'video/mpeg'                                                                => 'mpeg',
			'application/oda'                                                           => 'oda',
			'audio/ogg'                                                                 => 'ogg',
			'video/ogg'                                                                 => 'ogg',
			'application/ogg'                                                           => 'ogg',
			'font/otf'                                                                  => 'otf',
			'application/x-pkcs10'                                                      => 'p10',
			'application/pkcs10'                                                        => 'p10',
			'application/x-pkcs12'                                                      => 'p12',
			'application/x-pkcs7-signature'                                             => 'p7a',
			'application/pkcs7-mime'                                                    => 'p7c',
			'application/x-pkcs7-mime'                                                  => 'p7c',
			'application/x-pkcs7-certreqresp'                                           => 'p7r',
			'application/pkcs7-signature'                                               => 'p7s',
			'application/pdf'                                                           => 'pdf',
			'application/octet-stream'                                                  => 'pdf',
			'application/x-x509-user-cert'                                              => 'pem',
			'application/x-pem-file'                                                    => 'pem',
			'application/pgp'                                                           => 'pgp',
			'application/x-httpd-php'                                                   => 'php',
			'application/php'                                                           => 'php',
			'application/x-php'                                                         => 'php',
			'text/php'                                                                  => 'php',
			'text/x-php'                                                                => 'php',
			'application/x-httpd-php-source'                                            => 'php',
			'image/png'                                                                 => 'png',
			'image/x-png'                                                               => 'png',
			'application/powerpoint'                                                    => 'ppt',
			'application/vnd.ms-powerpoint'                                             => 'ppt',
			'application/vnd.ms-office'                                                 => 'ppt',
			'application/msword'                                                        => 'doc',
			'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
			'application/x-photoshop'                                                   => 'psd',
			'image/vnd.adobe.photoshop'                                                 => 'psd',
			'audio/x-realaudio'                                                         => 'ra',
			'audio/x-pn-realaudio'                                                      => 'ram',
			'application/x-rar'                                                         => 'rar',
			'application/rar'                                                           => 'rar',
			'application/x-rar-compressed'                                              => 'rar',
			'audio/x-pn-realaudio-plugin'                                               => 'rpm',
			'application/x-pkcs7'                                                       => 'rsa',
			'text/rtf'                                                                  => 'rtf',
			'text/richtext'                                                             => 'rtx',
			'video/vnd.rn-realvideo'                                                    => 'rv',
			'application/x-stuffit'                                                     => 'sit',
			'application/smil'                                                          => 'smil',
			'text/srt'                                                                  => 'srt',
			'image/svg+xml'                                                             => 'svg',
			'application/x-shockwave-flash'                                             => 'swf',
			'application/x-tar'                                                         => 'tar',
			'application/x-gzip-compressed'                                             => 'tgz',
			'image/tiff'                                                                => 'tiff',
			'font/ttf'                                                                  => 'ttf',
			'text/plain'                                                                => 'txt',
			'text/x-vcard'                                                              => 'vcf',
			'application/videolan'                                                      => 'vlc',
			'text/vtt'                                                                  => 'vtt',
			'audio/x-wav'                                                               => 'wav',
			'audio/wave'                                                                => 'wav',
			'audio/wav'                                                                 => 'wav',
			'application/wbxml'                                                         => 'wbxml',
			'video/webm'                                                                => 'webm',
			'image/webp'                                                                => 'webp',
			'audio/x-ms-wma'                                                            => 'wma',
			'application/wmlc'                                                          => 'wmlc',
			'video/x-ms-wmv'                                                            => 'wmv',
			'video/x-ms-asf'                                                            => 'wmv',
			'font/woff'                                                                 => 'woff',
			'font/woff2'                                                                => 'woff2',
			'application/xhtml+xml'                                                     => 'xhtml',
			'application/excel'                                                         => 'xl',
			'application/msexcel'                                                       => 'xls',
			'application/x-msexcel'                                                     => 'xls',
			'application/x-ms-excel'                                                    => 'xls',
			'application/x-excel'                                                       => 'xls',
			'application/x-dos_ms_excel'                                                => 'xls',
			'application/xls'                                                           => 'xls',
			'application/x-xls'                                                         => 'xls',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'xlsx',
			'application/vnd.ms-excel'                                                  => 'xlsx',
			'application/xml'                                                           => 'xml',
			'text/xml'                                                                  => 'xml',
			'text/xsl'                                                                  => 'xsl',
			'application/xspf+xml'                                                      => 'xspf',
			'application/x-compress'                                                    => 'z',
			'application/x-zip'                                                         => 'zip',
			'application/zip'                                                           => 'zip',
			'application/x-zip-compressed'                                              => 'zip',
			'application/s-compressed'                                                  => 'zip',
			'multipart/x-zip'                                                           => 'zip',
			'text/x-scriptzsh'                                                          => 'zsh',
		];
	
		return isset($mime_map[$mime]) ? $mime_map[$mime] : false;
	}

}
?>