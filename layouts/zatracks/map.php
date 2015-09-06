<?php
defined('_JEXEC') or die;
# load google map asynchronous and show the encoded polyline
$app    = JFactory::getApplication();
$doc    = $app->getDocument();
$doc->addStyleSheet('media/plg_content_zatracks/css/map.css');
$doc->addScriptDeclaration("
   function initialize(){
     var mapOptions    = {
       mapTypeId: google.maps.MapTypeId.ROADMAP
     }
     var resetBtn      = document.getElementById('map-reset');
     var map           = new google.maps.Map(document.getElementById('map'), mapOptions);
     var decodedPath   = google.maps.geometry.encoding.decodePath('" .$displayData. "');
     var decodedLevels = decodeLevels('BBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB');
     var polyline      = new google.maps.Polyline({
       path: decodedPath,
       levels: decodedLevels,
       strokeColor: '#FF0000',
       strokeOpacity: 1.0,
       strokeWeight: 2,
       map: map
     });
     map.fitBounds(getPolylineBounds(polyline));

     google.maps.event.addDomListener(window, 'resize', function() {
       map.fitBounds(getPolylineBounds(polyline));
       var center = map.getCenter();
       google.maps.event.trigger(map, 'resize');
       map.setCenter(center);
     });
     google.maps.event.addDomListener(resetBtn, 'click',function() {
       map.fitBounds(getPolylineBounds(polyline));
     });

   }
   function decodeLevels(encodedLevelsString) {
     var decodedLevels = [];
     for (var i = 0; i < encodedLevelsString.length; ++i) {
       var level = encodedLevelsString.charCodeAt(i) - 63;
       decodedLevels.push(level);
     }
     return decodedLevels;
   }
   function getPolylineBounds(polyline) {
     var bounds = new google.maps.LatLngBounds;
     polyline.getPath().forEach(function(latLng) {
       bounds.extend(latLng);
     });
     return bounds;
   }
   function loadScript(){
     var script = document.createElement('script');
     script.type = 'text/javascript';
     script.src = 'https://maps.googleapis.com/maps/api/js?libraries=geometry&v=3.exp&signed_in=false&callback=initialize';
     document.body.appendChild(script);
   }
   window.onload = loadScript;
");
?>
<p id="map-actions"><a id="map-reset">reset map</a></p>
<div id="map"></div>