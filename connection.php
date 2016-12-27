<?php
/**
 *Class to instantiate different api connections
 * 
 * @author Adam Bilsing <adambilsing@gmail.com>
 */
class connection
{
	/**
	 *public and private variables 
	 *
	 * @var string stores data for the class
	 */
	static public $_path;
	static private $_user;
	static private $_token;
	static private $_headers;

	/**
	 * Sets $_path, $_user, $_token, $_headers upon class instantiation
	 * 
	 * @param $user, $path, $token required for the class
	 * @return void
	 */
	public function __construct($user, $path, $token) {
		$path = explode('/api/v2/', $path);
		$this->_path = $path[0];
		$this->_user = $user;
		$this->_token = $token;
		
		$encodedToken = base64_encode($this->_user.":".$this->_token);

		$authHeaderString = 'Authorization: Basic ' . $encodedToken;
		$this->_headers = array($authHeaderString, 'Accept: application/json','Content-Type: application/json');

	}	


	public static function http_parse_headers( $header )
    {
        $retVal = array();
        $fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $header));
        foreach( $fields as $field ) {
            if( preg_match('/([^:]+): (.+)/m', $field, $match) ) {
                $match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper("\0")', strtolower(trim($match[1])));
                if( isset($retVal[$match[1]]) ) {
                    $retVal[$match[1]] = array($retVal[$match[1]], $match[2]);
                } else {
                    $retVal[$match[1]] = trim($match[2]);
                }
            }
        }
        if ($retVal['X-Bc-Apilimit-Remaining'] <= 100) {
        	sleep(300);
        }
    }

    public function error($body, $url, $json, $type) {
    	global $error;
    	if (isset($json)) {
	    	$results = json_decode($body, true);
			$results = $results[0];
			$results['type'] = $type;
			$results['url'] = $url;
			$results['payload'] = $json;
			$error = $results;
		} else {
			$results = json_decode($body, true);
			$results = $results[0];
			$results['type'] = $type;
			$results['url'] = $url;
			$error = $results;
		}
    }

	/**
	 * Performs a get request to the instantiated class
	 * 
	 * Accepts the resource to perform the request on
	 * 
	 * @param $resource string $resource a string to perform get on
	 * @return results or var_dump error
	 */
	public function get($resource) { 
	
		$url = $this->_path . '/api/v2/' . $resource;
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $this->_headers);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_VERBOSE, 1);
		curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_HTTPGET, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);            
		$response = curl_exec($curl);
		
		$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
		$headers = substr($response, 0, $header_size);
		$body = substr($response, $header_size);
		self::http_parse_headers($headers);
		curl_close ($curl);
		if ($http_status == 200) {
			$results = json_decode($body, true);
			return $results;
		} else {
			$this->error($body, $url, null, 'GET');
		} 

	
	}

	/**
	 * Performs a put request to the instantiated class
	 * 
	 * Accepts the resource to perform the request on, and fields to be sent
	 * 
	 * @param string $resource a string to perform get on
	 * @param array $fields an array to be sent in the request
	 * @return results or var_dump error
	 */
	public function put($resource, $fields) {
			
		$url = $this->_path . '/api/v2' . $resource;
		// $json = json_encode($fields);  OLD ONE
		$json = substr(json_encode($fields), 1, -1);
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $this->_headers);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_VERBOSE, 1);
		curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($curl, CURLOPT_POSTFIELDS, $json); 
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec($curl);
		$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
		$headers = substr($response, 0, $header_size);
		$body = substr($response, $header_size);
		self::http_parse_headers($headers);
		curl_close($curl);
		if ($http_status == 200) {
			$results = json_decode($body, true);
			return $results;
		} else {
			$this->error($body, $url, $json, 'PUT');
		}

	}

	/** 
	 *  returns Random Password For User
	 * 
	 */	
		
	public function generate_random_password($length = 10)
	{
		$alphabets = range('A','Z');
		$Smallalphabets = range('a','z');    
		$numbers = range('0','9');
		$additional_characters = array('_','.');
		$final_array = array_merge($alphabets,$Smallalphabets,$numbers,$additional_characters);
		
		$password = '';
		
		while($length--)
		{
			$key = array_rand($final_array);
			$password .= $final_array[$key];
		}
		return $password;
	}    		
	
	
	
	
	/**
	 * Performs a post request to the instantiated class
	 * 
	 * Accepts the resource to perform the request on, and fields to be sent
	 * 
	 * @param string $resource a string to perform get on
	 * @param array $fields an array to be sent in the request
	 * @return results or var_dump error
	 */
	public function post($resource, $fields) {
		global $error;
		$url = $this->_path . '/api/v2' . $resource;
		$json = json_encode($fields);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $this->_headers);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_VERBOSE, 1);
		curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec ($curl);
		$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
		$headers = substr($response, 0, $header_size);
		$body = substr($response, $header_size);
		self::http_parse_headers($headers);
		curl_close ($curl);
		if ($http_status == 201) {
			$results = json_decode($body, true);
			return $results;
		} else {
			$this->error($body, $url, $json, 'POST');
		}
	}

	/**
	 * Performs a delete request to the instantiated class
	 * 
	 * Accepts the resource to perform the request on
	 * 
	 * @param string $resource a string to perform get on
	 * @return proper response or var_dump error
	 */
	public function delete($resource) {
			
		$url = $this->_path . '/api/v2' . $resource;
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $this->_headers);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_VERBOSE, 1);
		curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec($curl);
		$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
		$headers = substr($response, 0, $header_size);
		$body = substr($response, $header_size);
		self::http_parse_headers($headers);	        
		curl_close ($curl);
		if ($http_status == 204) {
	     	return $http_status . ' DELETED';
		 } else {
		 	$this->error($body, $url, null, 'DELETE');
		 }
	}
	
	public function get_children($id){
		
		$child = array();	
		if($id > 0){
			$req1 = 'categories?parent_id='.$id;
			$result1 = $this->get($req1);
			
			if(count($result1) > 0){
				$child = array();
				for($i=0;$i<count($result1);$i++){
					$array_1 = (array) $result1[$i];
					$child1 = $this->get_children($array_1['id']);
					######
					$nwarray1 = array();
					$nwarray1['id'] = $array_1['id'];
	                $nwarray1['name'] = $array_1['name'];
	                if($array_1['parent_id'] > 0)
	                        $nwarray1['isRoot'] = false;
	                else
	                        $nwarray1['isRoot'] = true;

	                if($array_1['parent_id'] > 0)
	                        $nwarray1['isLeaf'] = true;
	                else
	                        $nwarray1['isLeaf'] = false;

	                $nwarray1['parentCategoryId'] = $array_1['parent_id'];
	                if(count($child1) > 0){
							$nwarray1['children'] = $child1;
						}else{
							$nwarray1['children'] = null;	
						}
	                $nwarray1['description'] = $array_1['description'];
	                $nwarray1['ErrorCode'] = null;
	                $nwarray1['Message'] = null;
	                $nwarray1['UserFriendly'] = true;
	                array_push($child,$nwarray1);
	                ########	
				}		
			}else{
				$child = array();
			}
			return  $child;			
		}else{
			return false;	
		}	
		return $child;		
	}
	
	function indent($json) {
	    $result      = '';
	    $pos         = 0;
	    $strLen      = strlen($json);
	    $indentStr   = '  ';
	    $newLine     = "\n";
	    $prevChar    = '';
	    $outOfQuotes = true;

	    for ($i=0; $i<=$strLen; $i++) {

	        // Grab the next character in the string.
	        $char = substr($json, $i, 1);

	        // Are we inside a quoted string?
	        if ($char == '"' && $prevChar != '\\') {
	            $outOfQuotes = !$outOfQuotes;

	        // If this character is the end of an element,
	        // output a new line and indent the next line.
	        } else if(($char == '}' || $char == ']') && $outOfQuotes) {
	            $result .= $newLine;
	            $pos --;
	            for ($j=0; $j<$pos; $j++) {
	                $result .= $indentStr;
	            }
	        }

	        // Add the character to the result string.
	        $result .= $char;

	        // If the last character was the beginning of an element,
	        // output a new line and indent the next line.
	        if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
	            $result .= $newLine;
	            if ($char == '{' || $char == '[') {
	                $pos ++;
	            }

	            for ($j = 0; $j < $pos; $j++) {
	                $result .= $indentStr;
	            }
	        }

	        $prevChar = $char;
	    }

	    return $result;
	}
}

?>