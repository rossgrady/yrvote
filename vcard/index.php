<?php
require '../vendor/autoload.php';

include("../parser.php");
$parser = new FullNameParser();
#error_reporting(E_ALL);
#ini_set('display_errors', 'On');
$api_url = "https://www.googleapis.com/civicinfo/v2/representatives?roles=legislatorLowerBody&roles=legislatorUpperBody&key=AIzaSyCgsGoD46_KtQmEQ2TMJuM5XtfmbQBdC1s&address=";
// var api_url = "https://www.googleapis.com/civicinfo/v2/representatives?key=AIzaSyCgsGoD46_KtQmEQ2TMJuM5XtfmbQBdC1s&address=";
$address = urlencode($_GET['address']);
$officialID = $_GET['officialID'];
$title = urldecode($_GET['title']);

function getData($url, $headers){
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, $url);
  if($headers[0] !== ""){
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
  };
  curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
  curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_USERAGENT, "yrvote.click web client");
  $response = curl_exec($curl);
  curl_close($curl);
  return $response;
};

$google_headers = array();
$full_url = $api_url . $address;

#print $full_url;

$simple = getData($full_url, $google_headers);

#print $simple."<BR><BR>\n\n";

$data = json_decode($simple);

#print_r($data);

  $official = $data->officials[$officialID];
  $vcard = "BEGIN:VCARD\r\n" .
	"VERSION:3.0\r\n" .
	"PRODID:-//Apple Inc.//iPhone OS 10.2.1//EN\r\n";
  $name = $parser->parse_name($official->name);
  $vcard = $vcard . "N:" . $name['lname'] . ";" . $name['fname'] . ";" . $name['initials'] . ";" . $name['salutation'] . ";" . $name['suffix'] . "\r\n" .
                 "FN:" . $official->name . "\r\n" .
                "ORG:" . $title . "\r\n";
  if($official->phones){
    foreach ($official->phones as $phone){
      $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
      try {
        $parsedphone = $phoneUtil->parse($phone, "US");
      } catch (\libphonenumber\NumberParseException $e) {
        var_dump($e);
      }
      $parsedphone = $phoneUtil->format($parsedphone, \libphonenumber\PhoneNumberFormat::RFC3966);
      $vcard = $vcard . strtoupper($parsedphone) . "\r\n";
    };
  };
  if($official->urls){
    foreach ($official->urls as $url){
      $vcard = $vcard . "URL:" .$url . "\r\n";
    };
  };
  if($official->emails){
    foreach ($official->emails as $email){
      $vcard = $vcard . "EMAIL:" . $email . "\r\n";
    };
  };	
  if($official->channels){
    foreach ($official->channels as $channel){
      switch ($channel->type) {
        case "Facebook":
	  $vcard = $vcard . "X-SOCIALPROFILE;type=facebook:https://facebook.com/" . $channel->id . "\r\n";
	  break;
	case "Twitter":
	  $vcard = $vcard . "X-SOCIALPROFILE;type=twitter:https://twitter.com/" . $channel->id . "\r\n";
	  break;
	case "YouTube":
	  $vcard = $vcard . "X-SOCIALPROFILE;type=youtube:https://youtube.com/" . $channel->id . "\r\n";
	  break;
	case "GooglePlus":
	  $vcard = $vcard . "X-SOCIALPROFILE;type=googleplus:https://plus.google.com/" . $channel->id . "\r\n";
	  break;
	default: 
	  break;
      };
    };
  };	
  if($official->address){
    foreach ($official->address as $address){
      $line1 = $address->line1;
      $line2 = "";
      $line3 = "";
      if($address->line2){
	$line2 = $address->line2;
      };
      if($address->line3){
	$line3 = $address->line3;
      };
      if($line3 !== ""){
        $vcard = $vcard . "ADR:" . $line1 . ";" . $line2 . ";" . $line3 . ";" . $address->city . ";" . $address->state . ";" . $address->zip . ";United States of America\r\n";
        $vcard = $vcard . "LABEL:" . $line1 . "\\n" . $line2 . "\\n" . $line3 . "\\n" . $address->city . "\, " . $address->state . " " . $address->zip . "\\nUnited States of America\r\n";
      }else if($line2 !== ""){
        $vcard = $vcard . "ADR:;" . $line1 . ";" . $line2 . ";" . $address->city . ";" . $address->state . ";" . $address->zip . ";United States of America\r\n";
        $vcard = $vcard . "LABEL:" . $line1 . "\\n" . $line2 . "\\n" . $address->city . "\, ". $address->state . " " . $address->zip . "\\nUnited States of America\r\n";
      }else{
        $vcard = $vcard . "ADR:;;" . $line1 . ";" . $address->city . ";" . $address->state . ";" . $address->zip . ";United States of America\r\n";
        $vcard = $vcard . "LABEL:" . $line1 . "\\n" . $address->city . "\, " . $address->state . " " . $address->zip . "\\nUnited States of America\r\n";
      };
    };
  };

  if($official->photoUrl){
    $handle = fopen($official->photoUrl, 'rb');
    $img = new Imagick();
    $img->readImageFile($handle);
    $img->thumbnailImage(96, 96, true, true);
    $img->setImageCompressionQuality(75);
    // Strip out unneeded meta data
    $img->stripImage();
    $imgtype = $img->getImageFormat();
    $b64img = base64_encode($img->getImageBlob());
    $photostring = "PHOTO;ENCODING=b;TYPE=".$imgtype.":".$b64img;
    $first75 = substr($photostring, 0, 75);
    $theRest = substr($photostring, 75);
    $vcard = $vcard . $first75 . "\r\n " . chunk_split($theRest, 74, "\r\n ");
    $vcard = rtrim($vcard, ' ');
  };
  $vcard = $vcard . "END:VCARD\r\n";
header('Content-Type: text/x-vcard');
header('Content-Disposition: attachment; filename="' . $name["lname"] . '.vcf"');
print $vcard;
