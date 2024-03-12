<?php

include 'LanguageWeaverUtil.php';
include 'LanguageWeaverRest.php';

///////////////////////////////////////////////////////////////////////////////
//
// high level functions to translate strings and files
//
///////////////////////////////////////////////////////////////////////////////

class LanguageWeaverTranslator
{
	private $_beGlobalV4Rest;
	private $_source;
	private $_target;
	private $_flavor;
	
	public function __construct($server, $user, $password, $source, $target, $flavor, $useClientAuthentication)
	{
		$this->_source = $source;
		$this->_target = $target;
		$this->_flavor = $flavor;
		$this->_beGlobalV4Rest = new LanguageWeaverRest($server, $user, $password, $useClientAuthentication);
	}
	
	public function TranslateText($text)
	{
		$this->_beGlobalV4Rest->VerifyToken();
		$response = $this->UploadText($text);
		$id = $response->requestId;
		$response = $this->WaitForTranslation($id);
		return json_decode($response)->translation[0];
	}

	public function TranslateFile($inpath, $outpath)
	{
		$this->_beGlobalV4Rest->VerifyToken();
		$id = $this->UploadFile($inpath)->requestId;
		$fileContent = $this->WaitForTranslation($id);
		if ($fileContent)
		{
			$file = fopen($outpath, 'wb');
			fwrite($file, $fileContent);
			fclose($file);
		}
	}

	///////////////////////////////////////////////////////////////////////////////
	//
	// REST functions to translate strings and files
	//
	///////////////////////////////////////////////////////////////////////////////

	// send text 
	// the result contains the job id
	private function UploadText($text)
	{
		$data = [
			'input' => [$text], 
			'sourceLanguageId' => $this->_source, 
			'targetLanguageId' => $this->_target,
			'model' => $this->_flavor];
		$ch = $this->_beGlobalV4Rest->GetSession( 
			"/mt/translations/async", 
			[CURLOPT_POSTFIELDS => json_encode($data)], null);
			
		$response = $this->_beGlobalV4Rest->Execute($ch);
		return json_decode($response);
	}
	
	// send the content of a file for translation
	// returns the id of the translation job
	private function UploadFile($path)
	{
		$fields = [
			'sourceLanguageId' => $this->_source, 
			'targetLanguageId' => $this->_target,
			'model' => $this->_flavor, 
			'input' => new \CurlFile($path, 'application/octet-stream', pathinfo($path, PATHINFO_BASENAME)),
			'inputFormat' => LanguageWeaverUtil::GetFileType($path) ];
		$ch = $this->_beGlobalV4Rest->GetSessionFormData("/mt/translations/async", 
		[CURLOPT_POSTFIELDS => $fields ], null);

		$response = $this->_beGlobalV4Rest->Execute($ch);
		return json_decode($response);
	}
	

	// wait for job status at url to become done, and then download the translation
	// returns the translation as binary data (can be a string, or a file)
	private function WaitForTranslation($id)
	{
		$state = "";
		while (strtolower($state) != "done" && strtolower($state) != "accepted") // check for 'FAIL' to handle errors
		{
			if (strtolower($state) == 'failed')
				return "Mist";
			$ch = $this->_beGlobalV4Rest->GetSession("/mt/translations/async/$id", null, null);
			$response = $this->_beGlobalV4Rest->Execute($ch);
			if (!$response) return null;

			$state = json_decode($response)->translationStatus;
			echo "translation state $state\n";
			sleep(1);
		}
		
		$ch = $this->_beGlobalV4Rest->GetSession("/mt/translations/async/$id/content", null, null);
		
		$result = $this->_beGlobalV4Rest->Execute($ch);
		return $result;
	}
}
?>