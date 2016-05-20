<!DOCTYPE html>
<html>
  <head>
    <title>Geocoding service</title>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no">
    <meta charset="utf-8">
    <link rel="stylesheet" href="css/default.css" type="text/css">
  </head>
  <body>
      
    <div id="map"></div>

    <script type="text/javascript">

      var $_GET = {};
      var errors = [];
      var lat;
      var lng;
      var coords = [];
      var myHome;
      var wickedLocation;

      document.location.search.replace(/\??(?:([^=]+)=([^&]*)&?)/g, function () {
          function decode(s) {
              return decodeURIComponent(s.split("+").join(" "));
          }

          $_GET[decode(arguments[1])] = decode(arguments[2]);
      });

      var addresses = $_GET['addresses'].split(",");

      function initMap() {

        var geocoder = new google.maps.Geocoder();
       
        
        for(var i = 0; i < addresses.length; i++) {
          geocoder.geocode({'address': addresses[i]}, function(results, status) {
            if (status === google.maps.GeocoderStatus.OK) {
              //alert(results[0].geometry.location);
              lat = String(results[0].geometry.location.lat());
              lng = String(results[0].geometry.location.lng());
              coords[i] = [lat, lng];
              //alert("test");
            } else {
              alert('Geocode was not successful for the following reason: ' + status);
            }
          });
        }  

        //alert(lat);
        alert(lng);
        myHome = { "lat" : lat , "lng" : lng };
        wickedLocation = new google.maps.LatLng( myHome.lat, myHome.lng );

        var map = new google.maps.Map(document.getElementById('map'), {
          zoom: 8,
          center: wickedLocation
        });
      }

     
     

    </script> <!--INIT MAP-->
    <?php include "../google-api-key.php"; ?> <!--GOOGLE MAP API-->
  </body>
</html>