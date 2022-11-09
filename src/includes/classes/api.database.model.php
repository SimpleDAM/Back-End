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

class Database
{
    protected $connection = null;
	public $exdatabase = null;

    public function __construct()
    {
        try {
			$this->connection = new PDO("mysql:host=".DB_HOST.";charset=utf8mb4;dbname=".DB_DATABASE_NAME, DB_USERNAME, DB_PASSWORD);
    		$this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			$this->exdatabase = $this->connection;
        } catch (Exception $e) {
			$error_msg = "Database construct Error: ".$e->getMessage();
			$this->returnError($error_msg);
        }			
    }

    public function select($query = "" , $params = [])
    {
        try {
            $stmt = $this->executeStatement( $query , $params );
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);			
            return $result;
        } catch(Exception $e) {
			$error_msg = "Database select Error: ".$e->getMessage();
			$this->returnError($error_msg);
        }
        return false;
    }
	
	public function insert($query = "" , $params = [])
    {
        try {
            $stmt = $this->executeStatement( $query , $params );
			$id = $this->connection->lastInsertId();
            return $id;
        } catch(Exception $e) {
			$error_msg = "Database insert Error: ".$e->getMessage();
			$this->returnError($error_msg);
        }
        return false;
    }
	
	public function update($query = "" , $params = [])
    {
        try {
            $stmt = $this->executeStatement( $query , $params );
            return $stmt;
        } catch(Exception $e) {
			$error_msg = "Database update Error: ".$e->getMessage();
			$this->returnError($error_msg);
        }
        return false;
    }
	
	public function delete($query = "" , $params = [])
    {
        try {
            $stmt = $this->executeStatement( $query , $params );
            return $stmt;
        } catch(Exception $e) {
			$error_msg = "Database delete Error: ".$e->getMessage();
			$this->returnError($error_msg);
        }
        return false;
    }

    private function executeStatement($query = "" , $params = [])
    {
        try {
            $stmt = $this->connection->prepare($query);
			
            if($stmt === false) {
                throw New Exception("Unable to do prepared statement: " . $query);
            }
			
			// If there's multiple parameters (e.g. sorting, pagination, filtering)
			if (is_array($params)){
				if (isset($params[0]) && is_array($params[0])){
					foreach($params as $param){
						$stmt->bindValue($param[0], $param[1]);
					}
				// Else there's a single parameter (e.g. getting a single user by id)
				} else {
					if (count($params) > 0){
						$stmt->bindValue($params[0], $params[1]);
					}
				}
			}

            $stmt->execute();

            return $stmt;
        } catch(Exception $e) {
			$error_msg = "Database execute Error: ".$e->getMessage()." | Trace:".Utils::generateCallTrace()." | Query: $query";
			$this->returnError($error_msg);
        }	
    }
	
	private function returnError($description){
		Utils::debug("ERROR:".$description);
		$controller = new ApiBaseController();
		$controller->sendOutput(
			json_encode(array('error' => -1, 'description'=>"Database error. Check logs.")),
			array('Content-Type: application/json', 'HTTP/1.1 200 OK')
		);
	}
}

?>