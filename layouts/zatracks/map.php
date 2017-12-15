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

	// ....your markers code goes here
</script>
