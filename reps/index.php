<!DOCTYPE html>
<html>
    <head>
        <meta name="viewport" content="initial-scale=1.0, user-scalable=no">
        <meta charset="utf-8">
        <title>They're YOUR Reps</title>
    <link href="https://fonts.googleapis.com/css?family=Alfa+Slab+One" rel="stylesheet">
    <script
      src="https://code.jquery.com/jquery-3.1.1.min.js"
      integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8="
      crossorigin="anonymous">
    </script>
    <script 
      src="https://cdnjs.cloudflare.com/ajax/libs/tether/1.4.0/js/tether.min.js" 
      integrity="sha384-DztdAPBWPRXSA/3eYEEUWrWCy7G5KFbe8fFjk5JAIxUYHKkDx6Qin1DkWx51bBrb" 
      crossorigin="anonymous">
    </script>
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" 
      href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" 
      integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" 
      crossorigin="anonymous">

    <!-- Optional theme -->
    <link rel="stylesheet" 
      href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" 
      integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" 
      crossorigin="anonymous">

    <!-- Latest compiled and minified JavaScript -->
    <script 
      src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" 
      integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" 
      crossorigin="anonymous">
    </script>
    <link type="text/css" rel="stylesheet"  href="/css/style.css">
    </head>
<body>
<?php
//error_reporting(E_ALL);
// ini_set('display_errors', 'On');

require '../vendor/autoload.php';

$goog_url = "https://www.googleapis.com/civicinfo/v2/representatives?roles=legislatorLowerBody&roles=legislatorUpperBody&key=AIzaSyCgsGoD46_KtQmEQ2TMJuM5XtfmbQBdC1s&address=";
// $goog_url = "https://www.googleapis.com/civicinfo/v2/representatives?key=AIzaSyCgsGoD46_KtQmEQ2TMJuM5XtfmbQBdC1s&address=";

$openstates_url = "https://openstates.org/api/v1/legislators/geo/";

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

function prettyPrint(&$data){
  print "<pre>";
  print_r($data);
  print "</pre>";
};

// go get the initial set of data from Google's Civic Information API:
$google_headers = array();
$niceaddr = $_GET['address'];
$addr = urlencode($niceaddr);
$full_goog_url = $goog_url . $addr;
$goog_response = getData($full_goog_url, $google_headers);
$data = json_decode($goog_response);

// now pull in state-level information from the openstates API:
$openstates_headers = array();
$latitude = $_GET['lat'];
$longitude = $_GET['lng'];
$full_openstates_url = $openstates_url . "?lat=" . $latitude . "&long=" . $longitude;
$openstates_response = getData($full_openstates_url, $openstates_headers);
$osdata = json_decode($openstates_response);


function parseGoog($gdata_official){
  // takes in a single official array element from google's data
  $newOfficial = new stdClass();
  // the name
  $newOfficial->full_name = $gdata_official->name;
  $parser = new FullNameParser();
  $name = $parser->parse_name($gdata_official->name);
  $newOfficial->last_name = $name['lname'];
  $newOfficial->first_name = $name['fname'];
  $newOfficial->middle_name = $name['initials'];
  $newOfficial->suffixes = $name['suffix'];
  // party
  $newOfficial->party = $gdata_official->party;
  // photo
  $newOfficial->photo_url = $gdata_official->photoUrl;
  // phones
  $newOfficial->phones = $gdata_official->phones;
  // urls
  $newOfficial->urls = $gdata_official->urls;
  // emails
  $newOfficial->emails = $gdata_official->emails;
  // social media channels
  $newOfficial->channels = $gdata_official->channels;
  // addresses
  $newOfficial->addresses = $gdata_official->address;

  return $newOfficial;
};


$mergedOfficials = array();

foreach($data->offices as $office){
  $divisionId = $office->divisionId;
  foreach($office->officialIndices as $index){
    $newofficial = parseGoog($data->officials[$index]);
    $newofficial->division = $divisionId;
    $newindex = array_push($mergedOfficials, $newofficial);
    $office->newIndices[] = $newindex - 1;
  }
}


function parseOpen($odata_official){
// takes in a single official array element from openstates' data
  $newOfficial = new stdClass();
  // the name
  $newOfficial->full_name = $odata_official->full_name;
  $newOfficial->last_name = $odata_official->last_name;
  $newOfficial->first_name = $odata_official->first_name;
  $newOfficial->middle_name = $odata_official->middle_name;
  $newOfficial->suffixes = $odata_official->suffixes;
  // openstates ID
  $newOfficial->osID = $odata_official->id;
  // division
  $newOfficial->division = $odata_official->boundary_id;
  // party
  $newOfficial->party = $odata_official->party;
  // photo
  $newOfficial->photo_url = $odata_official->photo_url;
  // gotta extract stuff from the offices array
  $newOfficial->addresses = array();
  $newOfficial->phones = array();
  $newOfficial->urls = array();
  $newOfficial->emails = array();
  foreach($odata_official->offices as $office){
    $newAddress = new stdClass();
    $newAddress->locationName = $office->name;
    $matches = array();
    $pattern = "/(?<line1>.+)\n?(?<line2>.*)[,\n]+(?<city>[\s\w\.\-]+)[,\s]+(?<state>[A-Z]{2})[\s]+(?<zip>[0-9\-]+)$/";
    preg_match($pattern, $office->address, $matches);
    $newAddress->line1 = $matches['line1'];
    if($matches['line2'] !== ""){
      $newAddress->line2 = $matches['line2'];
    };
    $newAddress->city = ltrim($matches['city']);
    $newAddress->state = $matches['state'];
    $newAddress->zip = $matches['zip'];
    array_push($newOfficial->addresses, $newAddress);
    if($office->phone !== null && !in_array($office->phone, $newOfficial->phones)){
      array_push($newOfficial->phones, $office->phone);
    };
    if($office->email !== null && !in_array($office->email, $newOfficial->emails)){
      array_push($newOfficial->emails, $office->email);
    };
  };
  // urls
    if($odata_official->url !== null && !in_array($odata_official->url, $newOfficial->urls)){
      array_push($newOfficial->urls, $odata_official->url);
    };
  // emails
    if($odata_official->email !== null && !in_array($odata_official->email, $newOfficial->emails)){
      array_push($newOfficial->emails, $odata_official->email);
    };
  return $newOfficial;
};


foreach($osdata as $official){
    $newofficial = parseOpen($official);
    $newindex = array_push($mergedOfficials, $newofficial);
//  now here goes the logic to selectively replace stuff in the master list, or whatever
}

prettyPrint($mergedOfficials);

print 	'<div class="bd-pageheader">' .
	'<div class="title"><a href="/">They\'re YOUR Reps</a></div>'.
	'</div>'.
	'<div class="container-fluid">'.
	'<div class="padding h5">Representatives for '.$niceaddr.'</div>'.
	'<div id="accordion" class="panel-group" role="tablist" aria-multiselectable="true">';

    if($data->offices){
	foreach($data->offices as $i => $value){
	    $newoffice = '<div class="panel panel-default">' .
			    '<div class="panel-heading" role="tab" id="heading'.$i.'">' .
			    '<h4 class="panel-title">' .
			    '<a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse'.$i.'" aria-expanded="false" aria-controls="collapse'.$i.'">' .
			    $data->offices[$i]->name .
			    '</a>' .
			    '</h4>' .
			    '</div>' .
			    '<div id="collapse'.$i.'" class="panel-collapse collapse" role="tabpanel" aria-labelledby="heading'.$i.'">' .
			    '<div class="panel-body">';
	    foreach($data->offices[$i]->officialIndices as $index){
		$official = $data->officials[$index];
		$newoffice = $newoffice .
			'<div class="official-card">' . 
			'<div class="row"><div class="col-md-6 col-xs-12">' .
			'<div class="official-name h2 text-center">' . $official->name . '</div>' .
			'</div></div>'.
			'<div class="row">';
			
// PHOTO
		if($official->photoUrl){
		  $newoffice = $newoffice . '<div class="col-md-3 col-sm-5 col-xs-12">'.
			'<div class="official-photo"><img class="center-block" src="' .
			  $official->photoUrl . '"></div>';
		$newoffice = $newoffice .'</div><div class="col-md-4 col-sm-7 col-xs-12 p-left">';
		}else{
		  $newoffice = $newoffice .'<div class="col-md-6 col-xs-12 p-left">';
		};
// END PHOTO
// PARTY
		if($official->party){
		  $newoffice = $newoffice . '<div class="official-party">' .
			'Party: ' . $official->party . '</div>';
		};
// END PARTY
// ADDRESS	
		if($official->address){
		  foreach ($official->address as $address){
		    $newoffice = $newoffice .'<div class="official-address"><address>' .
		    $address->line1 . '<BR>';
		    if($address->line2){
		      $newoffice = $newoffice . $address->line2 . '<BR>';
		    };
		    if($address->line3){
		      $newoffice = $newoffice . $address->line3 . '<BR>';
		    };
		    $newoffice = $newoffice . $address->city . ', ' .
		    $address->state . ' ' .
		    $address->zip . '</address></div>';
		  };
		};	
// END ADDRESS
// PHONES
		if($official->phones){
		  foreach ($official->phones as $phone){
      		    $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
      		    try {
        		$parsedphone = $phoneUtil->parse($phone, "US");
      		    } catch (\libphonenumber\NumberParseException $e) {
        		var_dump($e);
      		    }
      		    $parsedphone = $phoneUtil->format($parsedphone, \libphonenumber\PhoneNumberFormat::RFC3966);
		    $newoffice = $newoffice .'<div class="official-phone"><a href="' . $parsedphone . '">' .
		    $phone . '</a></div>';
		  };
		};	
// END PHONES
// URLS
  if($official->urls){
    foreach ($official->urls as $url){
      $newoffice = $newoffice . '<div class="official-url">' .
      '<a href="' .$url . '">Official website</a></div>';
    };
  };
// END URLS
// EMAILS
  if($official->emails){
    foreach ($official->emails as $email){
      $newoffice = $newoffice . '<div class="official-email">' .
      '<a href="mailto:' . $email . '">' . $email .'</a></div>';
    };
  };
// END EMAILS
// CHANNELS
  if($official->channels){
    foreach ($official->channels as $channel){
      switch ($channel->type) {
        case "Facebook":
      $newoffice = $newoffice . '<div class="official-facebook"><a href="https://facebook.com/' . $channel->id . '">Facebook</a></div>';
      break;
    case "Twitter":
      $newoffice = $newoffice . '<div class="official-twitter"><a href="https://twitter.com/' . $channel->id . '">Twitter</a></div>';
      break;
    case "YouTube":
      $newoffice = $newoffice . '<div class="official-youtube"><a href="https://youtube.com/' . $channel->id . '">YouTube</a></div>';
      break;
    case "GooglePlus":
      $newoffice = $newoffice . '<div class="official-googleplus"><a href="https://plus.google.com/' . $channel->id . '">Google+</a></div>';
      break;
    default:
      break;
      };
    };
  };
// END CHANNELS
$newoffice = $newoffice . '</div></div><div class="row"><div class="col-md-6 col-xs-12">';
// VCARD LINK
        $ofcname = urlencode($data->offices[$i]->name);
        $newoffice = $newoffice . '<div class="official-vcard-link text-center">' .
        '<a href="/vcard/?title=' . $ofcname . '&officialID=' . $index . '&address=' . $addr .'"">Add to Contacts</a></div>';
// END VCARD
$newoffice = $newoffice . '</div></div></div>';
	    };
	    $newoffice = $newoffice .'</div></div></div>';
	    print $newoffice;

	};
    }else{
	print '<h1>Sorry, something went horribly wrong. <a href="/">Try again?</a></h1>';
};
?>
</div></div>
<div class="padding"></div>
<footer class="footer">
<div class="container"><p class="footer-text">Built by <a href="https://twitter.com/rossgrady">@rossgrady</a>. &nbsp; &nbsp; Data: <a href="https://developers.google.com/civic-information/">Google</a>.</p>
    </div>
    </footer>
</body>
</html>
