<?php
include_once('config.inc');

$username = $_POST['middmediaUploadUsername'];
$directory = $_POST['middmediaUploadDirectory'];
$file = base64_encode(file_get_contents($_FILES['async-upload']['tmp_name']));
$filename = $_FILES['async-upload']['name'];
$filetype = $_FILES['async-upload']['type'];
$filesize = $_FILES['async-upload']['size'];
$response = "Success";

try {
  $client = new SoapClient(MIDDMEDIA_SOAP_WSDL);
  $types = $client->serviceGetTypes($username, 'blogs', MIDDMEDIA_SOAP_KEY);
  $extension = substr($filename, strrpos($filename, '.') + 1);
  if (in_array($extension, $types)) {
    $client->serviceAddVideo($username, 'blogs', MIDDMEDIA_SOAP_KEY, $directory, $file, $filename, $filetype, $filesize);
  } else {
    $response = $extension . " is not a supported file type.";
  }
} catch(Exception $ex) {
  $response = $ex->faultstring;
}

header('Location: ' . $_POST['_wp_http_referer'] . "&response=" . $response);
?>