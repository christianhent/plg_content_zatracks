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
 * @version    2.2.2
 * 
 */
defined('_JEXEC') or die;

$app = JFactory::getApplication();
$doc = $app->getDocument();
$doc->addStyleSheet('https://unpkg.com/leaflet@1.1.0/dist/leaflet.css');
$doc->addStyleSheet('media/plg_content_zatracks/css/map.css');
$doc->addScript('https://unpkg.com/leaflet@1.1.0/dist/leaflet.js');
$doc->addScript('media/plg_content_zatracks/js/Polyline.encoded.js');
$doc->addScript('http://d3js.org/d3.v3.min.js');
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
	var encoded = '<?php echo $displayData ?>';
	var polyline = L.Polyline.fromEncoded(encoded, {
		color: 'red',
		weight: 3,
		opacity: 0.5
	});

	polyline.addTo(map);
	map.fitBounds(polyline.getBounds());	
</script>
