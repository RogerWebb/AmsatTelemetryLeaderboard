<!DOCTYPE html>
<html lang="en">
  <head>
    <title>AMSAT Telemetry Leaderboard Map</title>
    <!-- The line below is only needed for old environments like Internet Explorer and Android 4.x -->
    <script src="https://cdn.polyfill.io/v2/polyfill.min.js?features=fetch,requestAnimationFrame,Element.prototype.classList,URL"></script>
    <script src="include/jquery.js"></script>
    <script src="include/ol.js"></script>
    <link rel="stylesheet" href="include/ol.css">
    <style>
      .map {
        width: 100%;
        height:800px;
      }
    </style>
    <script type="application/javascript">

    jQuery(document).ready(function() {
        init_amsat_ground_station_map(jQuery("#spacecraft").val(), jQuery("#last_x").val());

        jQuery("#btnUpdateMap").click(function() {
            update_amsat_ground_station_map(jQuery("#spacecraft").val(), jQuery("#last_x").val());
        });
    });

    </script>
  </head>
  <body>
    <div id="control_panel">
     <b>Spacecraft</b>
     <select id="spacecraft">
      <option value="1">Fox-1A (AO-85)</option>
      <option value="2" selected>Fox-1B (AO-91)</option>
      <option value="3">Fox-1Cliff (AO-95)</option>
      <option value="4">Fox-1D (AO-92)</option>
      <option value="6">HuskySat-1</option>
     </select>
     <b>Show Data For Last </b>
     <select id="last_x">
      <option value="-90 minutes">90 Minutes</option>
      <option value="-24 hours">24 Hours</option>
      <option value="-30 days">30 days</option>
     </select>
     <input type="button" id="btnUpdateMap" value="Update" />
    </div>
    <div id="map" class="map"></div>
    <script src="index.js"></script>
  </body>
</html>
