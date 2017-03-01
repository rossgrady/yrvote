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
  crossorigin="anonymous"></script>
     <script src="https://cdnjs.cloudflare.com/ajax/libs/tether/1.4.0/js/tether.min.js" integrity="sha384-DztdAPBWPRXSA/3eYEEUWrWCy7G5KFbe8fFjk5JAIxUYHKkDx6Qin1DkWx51bBrb" crossorigin="anonymous"></script>
<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

<!-- Optional theme -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
        <link type="text/css" rel="stylesheet"  href="/css/style.css">
    </head>
<body>
<?php
require '../vendor/autoload.php';
#error_reporting(E_ALL);
#ini_set('display_errors', 'On');

$api_url = "https://www.googleapis.com/civicinfo/v2/representatives?roles=legislatorLowerBody&roles=legislatorUpperBody&key=AIzaSyCgsGoD46_KtQmEQ2TMJuM5XtfmbQBdC1s&address=";
// var api_url = "https://www.googleapis.com/civicinfo/v2/representatives?key=AIzaSyCgsGoD46_KtQmEQ2TMJuM5XtfmbQBdC1s&address=";


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

$niceaddr = $_GET['address'];

$addr = urlencode($niceaddr);

$full_url = $api_url . $addr;

#print $full_url;

$simple = getData($full_url, $google_headers);

#print $simple."<BR><BR>\n\n";

$data = json_decode($simple);

#print_r($data);

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
    <div class="container"><p class="footer-text">Built by <a href="https://twitter.com/rossgrady">@rossgrady</a></p>
    </div>
    </footer>
</body>
</html>
