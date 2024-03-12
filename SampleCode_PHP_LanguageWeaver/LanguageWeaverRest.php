<?php

///////////////////////////////////////////////////////////////////////////////
//
// Helper functions to prepare and execute REST calls
//
///////////////////////////////////////////////////////////////////////////////

class LanguageWeaverRest
{
	private $_server;
	private $_user;
	private $_password;
	private $_useClientAuthentication;
	
	private $_token;
	private $_tokenExpiry;

	public function __construct($server, $user, $password, $useClientAuthentication)
	{
		$this->_server = $server;
		$this->_user = $user;
		$this->_password = $password;
		$this->_useClientAuthentication = $useClientAuthentication;
	}
	

	// create a session and fill in default and custom options
	// you don't need to specify GET or POST, that's controlled by having a 
	// CURLOPT_POSTFIELDS option or not
	// - command is the URL without the server, like /v1/translations,
	// - extraOptions is an array with key => value pairs to add to the options
	// - headers is an array with "key: value" strings to be added to the header
	public function GetSessionShared($command, $extraOptions, $extraHeaders, $addDefaultType)
	{
		$ch = curl_init();
		$options = $this->GetDefaultConfig($command);

		if ($extraOptions != null)
			$options = array_replace($options, $extraOptions);
		
		$this->GetToken();
		if ($addDefaultType)
			$headers = ["Authorization: Bearer $this->_token", 'Content-type: application/json'];
		else
			$headers = ["Authorization: Bearer $this->_token"];

		// optional: add a unique ID identifying your call. This can be sent to support
		// in case of problems, to help identifying the data related to your call
		$callId = "php-SampleCode-MyCompany-".LanguageWeaverUtil::GUID();
		array_push($headers, "Trace-ID: $callId");
		
		if ($extraHeaders != null)
			$headers = array_replace($headers, $extraHeaders);

		curl_setopt_array($ch, $options);
		// needs to be a separate command, otherwise the POSTFIELDS enforces urlencode
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		return $ch;
	}

	public function GetSession($command, $extraOptions, $extraHeaders)
	{
		return $this->GetSessionShared($command, $extraOptions, $extraHeaders, true);
	}

	public function GetSessionFormData($command, $extraOptions, $extraHeaders)
	{
		return $this->GetSessionShared($command, $extraOptions, $extraHeaders, false);
	}

	// send a REST call and check for errors
	public function Execute($ch)
	{
		$response = curl_exec($ch);
		$error = curl_error($ch);
		if($error) // the most minimalistic error handling
		{
			echo "\nerror: $error\n";
			$result = null;
		}
		else
		{
			$result = $response;
		}
		
		curl_close($ch);
		return $result; 
	}

	// Get default options for the active command and setup
	private function GetDefaultConfig($command)
	{
		$options = $this->GetBaseConfig($command);
		return $options;
	}

	// options shared by all calls
	private function GetBaseConfig($command)
	{
		return [
			CURLOPT_URL => $this->_server."/v4"."$command",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_BINARYTRANSFER => true,
			CURLOPT_FAILONERROR => true,
//			CURLOPT_VERBOSE => 1,
			CURLOPT_CAINFO => dirname(__FILE__)."/cacert.pem",
			// the CAINFO is there to avoid an SSL error
		];
	}

	// Get token for user/password authorization
	// this is an expensive operation, and tokens remain valid for 24 hours,
	// so buffering the token is a good idea
	public function VerifyToken()
	{
		$nowInMilliseconds = time() * 1000;
		$remainingMilliseconds = $this->_tokenExpiry - $nowInMilliseconds;
		if ($remainingMilliseconds < 5000) // give it 5 seconds to react
		{
			$this->GetToken();
		}
	}

	private function GetToken()
	{
		if ($this->_useClientAuthentication) {
			$fields = ["clientId" => $this->_user,"clientSecret" => $this->_password];
			$url = $this->_server."/v4/token";
		} else {
			$fields = ["username" => $this->_user,"password" => $this->_password];
			$url = $this->_server."/v4/token/user";
		}
		$options = [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => json_encode($fields),
			CURLOPT_CAINFO => dirname(__FILE__)."/cacert.pem"
		];	
		$ch = curl_init();
		curl_setopt_array($ch, $options);
		// needs to be a separate command, otherwise the POSTFIELDS enforces urlencode
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-type: application/json']);

		$response = $this->Execute($ch);
		$this->_token = json_decode($response)->accessToken;
		$this->_tokenExpiry = json_decode($response)->expiresAt - 10000; // assume 10 seconds early, to allow the next call to definitely have a valid token
	}

}
?>