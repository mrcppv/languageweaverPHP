<?php

include 'LanguageWeaverTranslator.php';

$server="https://api.languageweaver.com";
$source="eng";
$target="fra";
$flavor="generic";

/* use either client id/secret or user/password authentication
$useClientAuthentication = true;
$user="1234567890abcdefghij1234567890ab";
$password="1234567890abcdefghij1234567890abcdefghij1234567890abcdefghij1234";
*/

$useClientAuthentication = false;
$user="user@company.com";
$password="myPassword";

$translator = new LanguageWeaverTranslator($server, $user, $password, $source, $target, $flavor, $useClientAuthentication);

$translation = $translator->TranslateText("Hello, how are you today?");
echo $translation."\n";

// to debug unicode output, where echo won't work
$file = fopen("C:\\testdata\\unicode-out.txt", 'wb');
fwrite($file, $translation);
fclose($file);

$translator->TranslateFile("C:\\testdata\\EN-test.txt", "C:\\testdata\\EN-test-target.txt");
$translator->TranslateFile("C:\\testdata\\EN-test.docx", "C:\\testdata\\EN-test-target.docx");
$translator->TranslateFile("C:\\testdata\\EN-test.rtf", "C:\\testdata\\EN-test-target.rtf");
// pdf will be converted to docx; the output is docx, too.
$translator->TranslateFile("C:\\testdata\\EN-test.pdf", "C:\\testdata\\EN-test-target.pdf.docx");
?>