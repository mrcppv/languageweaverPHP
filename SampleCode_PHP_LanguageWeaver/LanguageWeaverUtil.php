<?php

class LanguageWeaverUtil
{
	// map file extension to BeGlobal file type
	public static function GetFileType($path)
	{
		switch (strtolower(pathinfo($path, PATHINFO_EXTENSION)))
		{
			case "doc":
				return "DOC";
			case "docx":
				return "DOCX";
			case "xls":
				return "XLS";
			case "xlsx":
				return "XLSX";
			case "ppt":
				return "PPT";
			case "pptx":
				return "PPTX";
			case "odt":
				return "ODT";
			case "odp":
				return "ODP";
			case "ods":
				return "ODF";
			case "rtf":
				return "RTF";
			case "xml":
				return "XML";
			case "xliff":
			case "xlf":
			case "sdlxliff":
				return "XLIFF";
			case "tmx":
				return "TMX";
			case "htm":
			case "html":
				return "HTML";
			case "pdf":
				return "PDF";
			case "xline":
				return "XLINE";
			default:
				return "PLAIN";
		}
	}
	
	public static function GUID()
	{
		if (function_exists('com_create_guid') === true)
		{
			return trim(com_create_guid(), '{}');
		}

		return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
	}
}

?>