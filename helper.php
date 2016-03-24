<?php
defined('JPATH_BASE') or die;

use Joomla\Utilities\ArrayHelper;

class PlgContentZatracksHelper
{
	public static function loadGPXLib($path)
	{
		$interfaces = array('location\distance\distanceinterface');
		$classes = array(
			'polylineencoder',
			'gpxprocessor',
			'location\coordinate',
			'location\polyline',
			'location\ellipsoid',
			'location\distance\vincenty'
			);

		JLoader::register('GPXProcessor', $path . 'processor.php');
		JLoader::register('PolylineEncoder', $path . 'polylineEncoder.php');
		JLoader::register('Location\Coordinate', $path . 'Location/Coordinate.php');
		JLoader::register('Location\Polyline', $path . 'Location/Polyline.php');
		JLoader::register('Location\Ellipsoid', $path . 'Location/Ellipsoid.php');
		JLoader::register('Location\Distance\DistanceInterface', $path . 'Location/Distance/DistanceInterface.php');
		JLoader::register('Location\Distance\Vincenty', $path . 'Location/Distance/Vincenty.php');

		foreach ($classes as $class) {
			$exists = class_exists($class);
			if (!$exists) {
				return false;
			}	
		}

		foreach ($interfaces as $interface) {
			$exists = interface_exists($interface);
			if (!$exists) {
				return false;
			}	
		}

		return true;
	}

	public static function uploadFile($file, $maxsize, $tmpFile)
	{
		jimport('joomla.filesystem.file');

		$app          = JFactory::getApplication();

		$max_filesize = (int) ($maxsize * 1024 * 1024);

		$filename = JFile::makeSafe($file['name']);

		$src = $file['tmp_name'];

		$dest = JPath::clean( JPATH_ROOT . '/tmp/' . $tmpFile );

		if (isset($file['name']) && $file['name'] !='')
		{
			if ( strtolower(JFile::getExt($filename) ) == 'gpx')
			{
				if($max_filesize > 0 && (int) $file['size'] > $max_filesize) 
				{
					$app->enqueueMessage(JText::_('PLG_CONTENT_ZATRACKS_MESSAGE_FILE_UPLOAD_NO_SUCCESS_FILESIZE_LIMIT'), 'notice');

					return false;
				}

				if ( JFile::upload($src, $dest) )
				{
					$app->enqueueMessage(JText::_('PLG_CONTENT_ZATRACKS_MESSAGE_FILE_UPLOAD_SUCCESS'), 'message');

					return true;
				} 
				else
				{
					$app->enqueueMessage(JText::_('PLG_CONTENT_ZATRACKS_MESSAGE_FILE_UPLOAD_NO_SUCCESS'), 'error');

					return false;
				}
			}
			else
			{
				$app->enqueueMessage(JText::_('PLG_CONTENT_ZATRACKS_MESSAGE_FILE_UPLOAD_NO_SUCCESS_WRONG_EXT'), 'error');

				return false;
			}
		}

		return false;
	}

	public static function deleteFile($file)
	{
		$filepath = JPath::clean( JPATH_ROOT . '/tmp/' . $file );

		if( JFile::delete($filepath) )
		{
			return true;
		}

		return false;
	}

	public static function saveTrack($content_id, $context, $processedData, $editableFormData)
	{
		$db     = JFactory::getDbo();
		$query  = $db->getQuery(true);
		$query->select($db->quoteName('content_id'))
		->from($db->quoteName('#__zatracks'))
		->where($db->quoteName('content_id') . ' = ' . $content_id);
		$db->setQuery($query);
		$db->execute();
		$exists = (bool) $db->getNumRows();

		$data             = new stdClass;
		$data->content_id = $content_id;
		$data->context    = $context;
		$data->activity   = $editableFormData->activity;

		if (isset($processedData))
		{
			$data->name      = $processedData->name;
			$data->starttime = $processedData->starttime;
			$data->duration  = $processedData->duration;
			$data->geojson   = $processedData->geojson;
			$data->polyline  = $processedData->polyline;
			$data->avs       = $processedData->avs;
			$data->distance  = $processedData->distance;	
		}
		else
		{
			// editable data
			$data->name      = $editableFormData->name;
			$data->avs       = $editableFormData->avs;
			$data->distance  = $editableFormData->distance;
		}

		if ($exists)
		{
			$db->updateObject('#__zatracks', $data, 'content_id');
		}
		else
		{
			$db->insertObject('#__zatracks', $data);
		}
	}

	public static function loadTrack($data)
	{
		if (empty($data->id))
		{
			return $data;
		}

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*')
		->from($db->quoteName('#__zatracks'))
		->where($db->quoteName('content_id') . ' = ' . $data->id);
		$db->setQuery($query);
		$track = $db->loadObject();

		if(!isset($track))
		{
			return $data;
		}

		if (!isset($data->track))
		{
			$data->track = new stdClass();
		}

		$data->track->name      = $track->name;
		$data->track->activity  = $track->activity;
		$data->track->starttime = $track->starttime;
		$data->track->duration  = $track->duration;
		$data->track->distance  = $track->distance;
		$data->track->avs       = $track->avs;
		$data->track->geojson   = $track->geojson;
		$data->track->polyline  = $track->polyline;

		return $data;
	}

	public static function renderTrack($row, $params, $trackLayout)
	{
		if(!isset($row->track))
		{
			return false;
		}

		$include_categories = $params->get('include_categories');

		if (empty($include_categories))
		{
			return false;
		}

		if (!in_array($row->catid, $include_categories))
		{
			return false;
		}

		if (is_object($row))
		{
			$track = ArrayHelper::fromObject($row->track);

			$data = array('track' => $track, 'plg_params' => $params->toArray());

			return $trackLayout->render($data);
		}

		return false;
	}

	public static function ingestGPX($file)
	{
		$app     = JFactory::getApplication();
		$gpx     = new GPXProcessor();
		$gpxFile = JFile::makeSafe($file);
		$gpxExt  =  JFile::getExt($gpxFile);
		$gpxPath = JPath::clean(JPATH_ROOT.'/tmp/'.$gpxFile);

		if ($gpxExt == 'gpx')
		{
			try
			{
				if (!$gpx->loadFile($gpxPath))
				{
					$app->enqueueMessage(JText::_('PLG_CONTENT_ZATRACKS_MESSAGE_FILE_LOAD_NO_SUCCESS'), 'error');

					return false;
				}

				if($gpx)
				{
					$refObj  = new ReflectionObject( $gpx );
					$refProp = $refObj->getProperty( 'xml' );
					$refProp->setAccessible( true );
					$test = $refProp->getValue( $gpx );

					if(!$test->trk)
					{
						$app->enqueueMessage(JText::_('PLG_CONTENT_ZATRACKS_MESSAGE_INVALID_GPX_DATA'), 'error');

						return false;
					}
					else
					{
						$gpx->ingest();
						$json = $gpx->getJSON();

						return $json;
					}
				}

			}
			catch (Exception $e)
			{
				$app->enqueueMessage($e->getMessage(), 'warning');

				return false;
			}
		}

		return false;
	}

	public static function processGPX($gpxJson)
	{
		$app = JFactory::getApplication();

		if(!$gpxJson)
		{
			$app->enqueueMessage(JText::_('PLG_CONTENT_ZATRACKS_MESSAGE_DATA_PROCESSING_NO_SUCCESS'), 'error');

			return false;
		}

		$gpxData = new Joomla\Registry\Registry;
		$gpxData->loadString($gpxJson, 'JSON');
		$gpxData = $gpxData->toObject();

		$processedData        = new stdClass();
		$processedData->name      = $gpxData->journeys->journey0->name;
		$processedData->starttime = date("Y-m-d H:i:s", $gpxData->journeys->journey0->stats->start);
		$processedData->duration  = $gpxData->journeys->journey0->stats->journeyDuration;

		$dc                             = new Location\Polyline();
		$geoArray                       = [];
		$trackpoints                    = [];

		foreach ($gpxData->journeys->journey0->segments as $segment)
		{
			foreach ($segment->points as $trackpoint)
			{
				array_push($trackpoints, [$trackpoint->lat, $trackpoint->lon]);
				array_push($geoArray, [(float)$trackpoint->lon, (float)$trackpoint->lat, (float)$trackpoint->elevation]);
				$dc->addPoint(new Location\Coordinate($trackpoint->lat, $trackpoint->lon));
			}
		}

		$processedData->geojson   = PlgContentZatracksHelper::encodeGeoJson($geoArray);
		$processedData->polyline  = PlgContentZatracksHelper::encodePolyline($trackpoints);
		$processedData->distance  = $dc->getLength(new Location\Distance\Vincenty()) / 1000;
		$processedData->avs       = ($dc->getLength(new Location\Distance\Vincenty())/$processedData->duration)*3.6;

		return $processedData;
	}

	protected function encodeGeoJson($geoArray)
	{
		$geoString  = '{"name":"NewFeatureType","type":"FeatureCollection","features":[{"type":"Feature","geometry":{"type":"LineString","coordinates":';
		$geoString .= json_encode($geoArray);
		$geoString .= '},"properties":null}]}';

		return $geoString;
	}

	protected function encodePolyline($trackpoints)
	{
		$polylineEncoder = new PolylineEncoder;

		$encodedPolyline = $polylineEncoder->encode($trackpoints);

		return $encodedPolyline->points;	
	}

}