
var api_url = "https://www.googleapis.com/civicinfo/v2/representatives?roles=legislatorLowerBody&roles=legislatorUpperBody&key=AIzaSyCgsGoD46_KtQmEQ2TMJuM5XtfmbQBdC1s&address=";
// var api_url = "https://www.googleapis.com/civicinfo/v2/representatives?key=AIzaSyCgsGoD46_KtQmEQ2TMJuM5XtfmbQBdC1s&address=";

var placeSearch, autocomplete;

function initialize() {
  // Create the autocomplete object, restricting the search
  // to geographical location types.
  autocomplete = new google.maps.places.Autocomplete(
     (document.getElementById('autocomplete')),
      { types: ['geocode'] });
  // When the user selects an address from the dropdown,
  // populate the address fields in the form.
  google.maps.event.addListener(autocomplete, 'place_changed', function() {
    grabRepData();
  });
}

var repData;

function grabRepData() {
  // Get the place details from the autocomplete object.
  var place = autocomplete.getPlace();
  var addr = encodeURIComponent(place.formatted_address);
  addr = addr.replace(/%20/g, "+")
  var full_url = api_url + addr
  $.ajax({
       url: full_url,
       cache: false,
  })
      .done(function(data){
	console.log(data);
	repData = data;
	var container = document.createElement("div"); 
	container.id="accordion"; 
	container.role="tablist"; 
	container.setAttribute("aria-multiselectable","true");
	for (var i = 0; i < data.offices.length; i++){
	    console.log(data.offices[i]);
	    var newoffice = '<div class="card">' +
			    '<div class="card-header" role="tab" id="heading'+i+'">' +
			    '<h5 class="mb-0">' +
			    '<a data-toggle="collapse" data-parent="#accordion" href="#collapse'+i+'" aria-expanded="true" aria-controls="collapse'+i+'">' +
			    data.offices[i].name + 
			    '</a>' +
			    '</h5>' +
			    '</div>' +
			    '<div id="collapse'+i+'" class="collapse" role="tabpanel" aria-labelledby="heading'+i+'">' +
			    '<div class="card-block">';
	    for (index in data.offices[i].officialIndices){
		var official = data.officials[data.offices[i].officialIndices[index]];
		console.log(official.name);
		newoffice = newoffice + 
			'<div class="official-name">' + official.name + '</div>';
// PARTY
		if(official.party !== undefined){
		  newoffice = newoffice + '<div class="official-party">' +
			'Party: ' + official.party + '</div>';
		};
// END PARTY
// PHOTO
		if(official.photoUrl !== undefined){
		  newoffice = newoffice +'<div class="official-photo"><img src="' +
			  official.photoUrl + '"></div>';
		};
// END PHOTO
// ADDRESS	
		if(official.address !== undefined){
		  for (adridx in official.address){
		    newoffice = newoffice +'<div class="official-address">' +
		    official.address[adridx].line1 + '<BR>';
		    if(official.address[adridx].line2 !== undefined){
		      newoffice = newoffice + official.address[adridx].line2 + '<BR>';
		    };
		    if(official.address[adridx].line3 !== undefined){
		      newoffice = newoffice + official.address[adridx].line3 + '<BR>';
		    };
		    newoffice = newoffice + official.address[adridx].city + ', ' +
		    official.address[adridx].state + ' ' +
		    official.address[adridx].zip + '</div>';
		  };
		};	
// END ADDRESS
// PHONES
		if(official.phones !== undefined){
		  for (phnidx in official.phones){
		    newoffice = newoffice +'<div class="official-phone">' +
		    official.phones[phnidx] + '</div>';
		  };
		};	
// END PHONES
// VCARD LINK
  		var ofcname = encodeURIComponent(data.offices[i].name);
  		ofcname = ofcname.replace(/%20/g, "+")
		newoffice = newoffice +'<div class="official-vcard-link">' +
		'<a href="vcard.php?title=' + ofcname + '&officialID=' + data.offices[i].officialIndices[index] + '&address=' + addr +'"">Add to Contacts</a></div>';
// END VCARD

	    };
	    newoffice = newoffice +'</div>' + '</div>';
	    $(container).append(newoffice);
	}
	$("#results").append(container);
  })
}

function createVcard(officialID, office){

  var official = repData.officials[officialID];
  var vcard = "BEGIN:VCARD\r\n" +
	"VERSION:3.0\r\n";
  var name = parseFullName(official.name);
  console.log(name);
  vcard = vcard + "N:" + name.last + ";" + name.first + ";" + name.middle + ";" + name.title + ";" + name.suffix + "\r\n" +
                 "FN:" + official.name + "\r\n" +
                "ORG:" + office + "\r\n";
  if(official.photoUrl !== undefined){
    vcard = vcard + "PHOTO;VALUE=URI:" + official.photoUrl + "\r\n";
  };
  if(official.phones !== undefined){
    for (phnidx in official.phones){
      vcard = vcard + "TEL;TYPE=WORK,VOICE:" + official.phones[phnidx] + "\r\n";
    };
  };	
  if(official.urls !== undefined){
    for (urlidx in official.urls){
      vcard = vcard + "URL:" + official.urls[urlidx] + "\r\n";
    };
  };	
  if(official.emails !== undefined){
    for (emlidx in official.emails){
      vcard = vcard + "EMAIL:" + official.emails[emlidx] + "\r\n";
    };
  };	
  if(official.channels !== undefined){
    for (chnidx in official.channels){
      switch (official.channels[chnidx].type) {
        case "Facebook":
	  vcard = vcard + "X-SOCIALPROFILE;type=facebook:https://facebook.com/" + official.channels[chnidx].id + "\r\n";
	  break;
	case "Twitter":
	  vcard = vcard + "X-SOCIALPROFILE;type=twitter:https://twitter.com/" + official.channels[chnidx].id + "\r\n";
	  break;
	case "YouTube":
	  vcard = vcard + "X-SOCIALPROFILE;type=youtube:https://youtube.com/" + official.channels[chnidx].id + "\r\n";
	  break;
	case "GooglePlus":
	  vcard = vcard + "X-SOCIALPROFILE;type=googleplus:https://plus.google.com/" + official.channels[chnidx].id + "\r\n";
	  break;
	default: 
	  break;
      };
    };
  };	
  if(official.address !== undefined){
    for(adridx in official.address){
      var line1 = official.address[adridx].line1;
      if(official.address[adridx].line2 !== undefined){
	var line2 = official.address[adridx].line2;
      };
      if(official.address[adridx].line3 !== undefined){
	var line3 = official.address[adridx].line3;
      };
      if(line3 !== undefined){
        vcard = vcard + "ADR:" + line1 + ";" + line2 + ";" + line3 + ";" + official.address[adridx].city + ";" + official.address[adridx].state + ";" + official.address[adridx].zip + ";United States of America\r\n";
        vcard = vcard + "LABEL:" + line1 + "\\n" + line2 + "\\n" + line3 + "\\n" + official.address[adridx].city + "\, " + official.address[adridx].state + " " + official.address[adridx].zip + "\\nUnited States of America\r\n";
      }else if(line2 !== undefined){
        vcard = vcard + "ADR:;" + line1 + ";" + line2 + ";" + official.address[adridx].city + ";" + official.address[adridx].state + ";" + official.address[adridx].zip + ";United States of America\r\n";
        vcard = vcard + "LABEL:" + line1 + "\\n" + line2 + "\\n" + official.address[adridx].city + "\, " + official.address[adridx].state + " " + official.address[adridx].zip + "\\nUnited States of America\r\n";
      }else{
        vcard = vcard + "ADR:;;" + line1 + ";" + official.address[adridx].city + ";" + official.address[adridx].state + ";" + official.address[adridx].zip + ";United States of America\r\n";
        vcard = vcard + "LABEL:" + line1 + "\\n" + official.address[adridx].city + "\, " + official.address[adridx].state + " " + official.address[adridx].zip + "\\nUnited States of America\r\n";
      };
    };
  };

  vcard = vcard + "END:VCARD";
  // let's test out whether we can make FileSaver work
  event.preventDefault();
  saveAs(
    new Blob(
        [vcard], {type: "text/x-vcard; charset=utf-8"}
    )
    , name.last +".vcf"
  );
};

// [START region_geolocation]
// Bias the autocomplete object to the user's geographical location,
// as supplied by the browser's 'navigator.geolocation' object.
function geolocate() {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function(position) {
      var geolocation = new google.maps.LatLng(
          position.coords.latitude, position.coords.longitude);
      var circle = new google.maps.Circle({
        center: geolocation,
        radius: position.coords.accuracy
      });
      autocomplete.setBounds(circle.getBounds());
    });
  }
}
// [END region_geolocation]
