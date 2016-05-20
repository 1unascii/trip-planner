<!DOCTYPE html>
<html>
  <head>
    <title>Geocoding service</title>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no">
    <meta charset="utf-8">
    <link rel="stylesheet" href="css/default.css" type="text/css">
  </head>
  <body>
    <div id="floating-panel">
      <input id="address" type="textbox" value="Sydney, NSW">
      <input id="submit" type="button" value="Geocode">
    </div>
    <div id="map"></div>

    <script type="text/javascript" src="js/geocoder.js"></script> <!--INIT MAP-->
    <?php include "../google-api-key.php"; ?> <!--GOOGLE MAP API-->
  </body>
</html>