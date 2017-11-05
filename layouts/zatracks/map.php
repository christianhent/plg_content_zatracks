<?php
/**
 * 
 * @category   GPX Extension
 * @package    Joomla.Plugin
 * @subpackage Content.Zatracks
 * @author     Christian Hent <hent.dev@googlemail.com>
 * @copyright  Copyright (C) 2017 Christian Hent
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://github.com/christianhent/plg_content_zatracks
 * 
 * @version    2.2.3
 * 
 */
defined('_JEXEC') or die;

$app = JFactory::getApplication();
$doc = $app->getDocument();
$doc->addStyleSheet('media/plg_content_zatracks/css/leaflet.css');
$doc->addStyleSheet('media/plg_content_zatracks/css/Control.FullScreen.min.css');
$doc->addStyleSheet('media/plg_content_zatracks/css/map.css');
$doc->addScript('media/plg_content_zatracks/js/leaflet.js');
$doc->addScript('media/plg_content_zatracks/js/d3.v3.min.js');
$doc->addScript('media/plg_content_zatracks/js/Control.FullScreen.js');
$doc->addScript('media/plg_content_zatracks/js/leaflet.elevation-0.0.4.min.js');
?>
<div id="map"></div>
<script type="text/javascript">
	var url  = '<a href="http://openstreetmap.org">OpenStreetMap</a>';
	var tile = new L.TileLayer('http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		maxZoom: 18,
		attribution: 'Map data &copy; ' + url,
	});
	var map  = new L.Map('map',{
		fullscreenControl: true,
		fullscreenControlOptions: {
			position: 'topleft'
		},
		layers: [tile]
	});
	var elevation = L.control.elevation({
	 	position: "topright",
	 	theme: "steelblue-theme",
	 	width: 400,
	 	height: 125,
	 	useHeightIndicator: false,
	 	collapsed: true,
	 	imperial: false
	 });
	var geoJson = L.geoJson(<?php echo $displayData ?>,{
		onEachFeature: elevation.addData.bind(elevation)
	});
	elevation.addTo(map);
	geoJson.addTo(map);
	map.fitBounds(geoJson.getBounds());

	//(waypoint) markers support
		
	/*example usage in content items:
	<ul id="wpts" hidden>
		<li title="Wöhrder Wiese">49.450647,11.0866813</li>
		<li title="Wasserwerk Erlenstegen">49.472041,11.1398093</li>
		<li title="Brücke am Pulversee">49.458071,11.1097983</li>
	</ul>
	*/

	/*document.addEventListener("DOMContentLoaded", function(event)
	{
		var container = document.getElementById("wpts");

		if (typeof(container) != 'undefined' && container != null)
		{
			var wpts   = document.getElementById("wpts").children;
			var string = null;
			var title  = null;
			var latlng = null;

			if (typeof(wpts) != 'undefined' && wpts != null)
			{
				for(i=0; i < wpts.length; i++)
				{
					if( typeof wpts[i].innerHTML === "string" && wpts[i].innerHTML.length > 0 )
					{
						string = wpts[i].innerHTML;
						latlng = string.split(",");

						if( parseFloat(latlng[0]) && parseFloat(latlng[1]) )
						{
							//(waypoint)marker
							var circle = L.circleMarker([latlng[0],latlng[1]], {
								color: '#FF4500',
								fillColor: '#FF4500',
								fillOpacity: 0.75,
								radius: 6,
								stroke: false
							}).addTo(map);
							//popup
							if( typeof wpts[i].title === "string" && wpts[i].title.length > 0 )
							{
								circle.bindPopup(wpts[i].title);
							}
						}
						else
						{
							break;
						}	
					}	
				}
			}
		}	
	});*/
	
</script>
