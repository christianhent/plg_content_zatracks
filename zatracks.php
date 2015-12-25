<?php
defined('JPATH_BASE') or die;

use Joomla\Utilities\ArrayHelper;
use Joomla\String\String;

jimport('joomla.plugin.plugin');

class PlgContentZatracks extends JPlugin
{
	protected $app;
	protected $libPath;
	protected $tmpFile;
	protected $processedData;
    protected $layoutPath;
    protected $trackLayout;
	protected $placeholder = 'zatracks';

    public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		$this->app         = JFactory::getApplication();
		$this->libPath     = JPath::clean(JPATH_ROOT .'/plugins/content/zatracks/lib/');
		$this->tmpFile     = 'tmp.gpx';
		$this->regexpr     = '/{'.$this->placeholder.'.*?}/i';
        $this->layoutPath  = JPath::clean(JPATH_ROOT .'/layouts/joomla/zatracks/');

        $this->trackLayout = new JLayoutFile('joomla.zatracks.default', $basePath = null, array(
        	'suffixes' => array('bs2', 'bs3'),
        	'debug' => $this->params->get('debug_layout')
        ));

		$this->loadLanguage();
	}

	public function onContentPrepareForm($form, $data)
	{
		if (!$this->app->isAdmin())
		{
			return true;
		}

		if (!($form instanceof JForm))
		{
			$this->_subject->setError('JERROR_NOT_A_FORM');

			return false;
		}

		$name = $form->getName();

		if (!in_array($name, array('com_content.article')))
		{
			return true;
		}

		$include_categories = $this->params->get('include_categories');

		if (empty($include_categories))
		{
			return true;
		}

		if (empty($data))
		{
			$input = JFactory::getApplication()->input;
			$data  = (object) $input->post->get('jform', array(), 'array');
		}

		if (is_array($data))
		{
			$data = ArrayHelper::toObject($data);
		}

		if (empty($data->catid))
		{
			return true;
		}

		if (!in_array($data->catid, $include_categories))
		{
			return true;
		}

		JForm::addFormPath(__DIR__ . '/forms');
		$form->loadFile('zatracks');

		if (!empty($data->id))
		{
			$data = $this->_loadTrack($data);
		}

		JLayoutHelper::$defaultBasePath = __DIR__ . '/layouts';

		return true;
	}

	public function onContentBeforeSave($context, $data, $isNew)
	{
		if (!$this->app->isAdmin())
		{
			return true;
		}

		if (!in_array($context, array('com_content.article')))
		{
			return true;
		}

		$include_categories = $this->params->get('include_categories');

		if (empty($include_categories))
		{
			return true;
		}

		if (!in_array($data->catid, $include_categories))
		{
			return true;
		}

		$input = JFactory::getApplication()->input;
		$form  = $input->post->get('jform', null, 'array');
		
        $files = $input->files->get('jform');
        $file  = $files['track']['upload'];

        if (is_array($form))
		{
        	if(!$this->_loadGPXLib())
        	{
        		$this->app->enqueueMessage(JText::_('PLG_CONTENT_ZATRACKS_MESSAGE_LIB_NOT_FOUND'), 'error');
        			
        		return true;
        	}

        	if (isset($file['name']) && $file['name'] !='') 
        	{
        		if($this->_uploadFile($file))
        		{
        			$gpxJson = $this->_ingestGPX($this->tmpFile);

        			if(!$gpxJson)
        			{
        				$this->app->enqueueMessage(JText::_('PLG_CONTENT_ZATRACKS_MESSAGE_INGEST_ERROR'), 'error');
        			}

        			if($this->_processGPX($gpxJson))
        			{
        				$this->app->enqueueMessage(JText::_('PLG_CONTENT_ZATRACKS_MESSAGE_DATA_PROCESSING_SUCCESS'), 'message');
        			}

					if( $this->_deleteFile($this->tmpFile) )
					{
						$this->app->enqueueMessage(JText::_('PLG_CONTENT_ZATRACKS_MESSAGE_UPLOAD_FILE_DELETED'), 'message');
					}	
        		}
        		else
        		{
        			$this->app->enqueueMessage(JText::_('PLG_CONTENT_ZATRACKS_MESSAGE_NO_DATA_PROCESSING'), 'error');
        		}
			}
		}

		return true;
	}

	public function onContentAfterSave($context, $item, $isNew)
	{
		if (!$this->app->isAdmin())
		{
			return true;
		}

		if (!in_array($context, array('com_content.article')))
		{
			return true;
		}

		$include_categories = $this->params->get('include_categories');

		if (empty($include_categories))
		{
			return true;
		}

		if (!in_array($item->catid, $include_categories))
		{
			return true;
		}

		$input     = JFactory::getApplication()->input;
		$formData  = (object) $input->post->get('jform', null, 'array');

		if (is_array($formData->track))
		{
			$formData->track = ArrayHelper::toObject($formData->track);
		}

		if (is_object($formData->track))
		{
            $editableFormData = new stdClass();
            $editableFormData->name     = $formData->track->name;
			$editableFormData->activity = $formData->track->activity;
			$editableFormData->avs      = $formData->track->avs;
			$editableFormData->distance = $formData->track->distance;
		}
		else
		{

            return true;
		}

		$content_id = $item->id;
		
		$this->_saveTrack($content_id, $context, $editableFormData);

		return true;
	}

	public function onContentBeforeDelete($context, $item)
	{
		if (!in_array($context, array('com_content.article')))
		{
			return true;
		}

		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$conditions = array(
			$db->quoteName('content_id') . ' = ' . $db->quote($item->id)
		);
		$query->delete($db->quoteName('#__zatracks'));
		$query->where($conditions);
		$db->setQuery($query);
		$db->execute();

        return true;
	}

	public function onContentPrepare($context, &$row, &$params, $page = 0)
	{
		if (in_array($context, array('com_content.category','com_content.featured','com_content.archive')))
		{
            if ( String::strpos( $row->text, '{'.$this->placeholder ) === false )
			{
                return true;
			}

			preg_match_all( $this->regexpr, $row->text, $matches );

			if ( !count( $matches[0] ) ) 
			{
				return true;
			}

			$row->text = preg_replace( $this->regexpr, '', $row->text);
		}

		if (!in_array($context, array('com_content.article')))
		{
			return true;
		}

		$include_categories = $this->params->get('include_categories');

		if (empty($include_categories))
		{
			return false;
		}

		if (!in_array($row->catid, $include_categories))
		{
			return false;
		}

		if (!empty($row->id))
		{
			$row = $this->_loadTrack($row);
		}

        if(!isset($row->track))
        {
            return false;
        }

		if ($this->params->get('output') == 3) 
		{
			
			if ( String::strpos( $row->text, '{'.$this->placeholder ) === false )
			{
				return true;
			}

			preg_match_all( $this->regexpr, $row->text, $matches );

			if ( !count( $matches[0] ) ) 
			{
				return true;
			}

            $track = ArrayHelper::fromObject($row->track);

			$data = array('track' => $track, 'plg_params' => $this->params->toArray());

			$row->text = preg_replace( $this->regexpr, $this->trackLayout->render($data), $row->text );
		}
		else
		{
			$row->text = preg_replace( $this->regexpr, '', $row->text );
		}

        return true;
	}

	public function onContentAfterTitle($context, &$row, &$params, $page = 0)
	{
        if (!in_array($context, array('com_content.article')))
		{
			return false;
		}

        if ($this->params->get('output') != 0)
        {
            return false;
        }

        return $this->_renderTrack($row);
	}

	public function onContentBeforeDisplay($context, &$row, &$params, $page = 0)
	{
        if (!in_array($context, array('com_content.article')))
		{
			return false;
		}

        if ($this->params->get('output') != 1)
        {
            return false;
        }

        return $this->_renderTrack($row);
	}

	public function onContentAfterDisplay($context, &$row, &$params, $page = 0)
	{
		if (!in_array($context, array('com_content.article')))
		{
			return false;
		}

        if ($this->params->get('output') != 2)
        {
            return false;
        }

        return $this->_renderTrack($row);
	}

    public function onAfterDispatch()
    {
        JLayoutHelper::$defaultBasePath = "";
    }

	protected function _saveTrack($content_id, $context, $editableFormData)
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

		if (isset($this->processedData))
		{
			$data->name      = $this->processedData->name;
			$data->starttime = $this->processedData->starttime;
			$data->duration  = $this->processedData->duration;
			$data->geojson   = $this->processedData->geojson;
			$data->polyline  = $this->processedData->polyline;
			$data->avs       = $this->processedData->avs;
			$data->distance  = $this->processedData->distance;	
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

	protected function _loadTrack($data)
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

    protected function _renderTrack($row)
    {
        if(!isset($row->track))
        {
            return false;
        }

        $include_categories = $this->params->get('include_categories');

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

            $data = array('track' => $track, 'plg_params' => $this->params->toArray());

            return $this->trackLayout->render($data);
        }

        return false;
    }

	protected function _loadGPXLib()
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

		JLoader::register('GPXProcessor', $this->libPath.'processor.php');
		JLoader::register('PolylineEncoder', $this->libPath.'polylineEncoder.php');
		JLoader::register('Location\Coordinate', $this->libPath.'Location/Coordinate.php');
		JLoader::register('Location\Polyline', $this->libPath.'Location/Polyline.php');
		JLoader::register('Location\Ellipsoid', $this->libPath.'Location/Ellipsoid.php');
		JLoader::register('Location\Distance\DistanceInterface', $this->libPath.'Location/Distance/DistanceInterface.php');
		JLoader::register('Location\Distance\Vincenty', $this->libPath.'Location/Distance/Vincenty.php');

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

	protected function _uploadFile($file)
	{
		jimport('joomla.filesystem.file');

		$max_filesize = (int) ($this->params->get('max_filesize', 0) * 1024 * 1024);

		$filename = JFile::makeSafe($file['name']);

        $src = $file['tmp_name'];
        
        $dest = JPath::clean( JPATH_ROOT . '/tmp/' . $this->tmpFile );

        if (isset($file['name']) && $file['name'] !='')
        {
            if ( strtolower(JFile::getExt($filename) ) == 'gpx')
            {
                if($max_filesize > 0 && (int) $file['size'] > $max_filesize) 
                {
                	$this->app->enqueueMessage(JText::_('PLG_CONTENT_ZATRACKS_MESSAGE_FILE_UPLOAD_NO_SUCCESS_FILESIZE_LIMIT'), 'notice');
                    
                    return false;
                }

                if ( JFile::upload($src, $dest) )
                {
                    $this->app->enqueueMessage(JText::_('PLG_CONTENT_ZATRACKS_MESSAGE_FILE_UPLOAD_SUCCESS'), 'message');
                    
                    return true;
                } 
                else
                {
                    $this->app->enqueueMessage(JText::_('PLG_CONTENT_ZATRACKS_MESSAGE_FILE_UPLOAD_NO_SUCCESS'), 'error');
                    
                    return false;
                }
            }
            else
            {
                $this->app->enqueueMessage(JText::_('PLG_CONTENT_ZATRACKS_MESSAGE_FILE_UPLOAD_NO_SUCCESS_WRONG_EXT'), 'error');
                
                return false;
            }
        }

        return false;
	}

	protected function _deleteFile($file)
	{
        $filepath = JPath::clean( JPATH_ROOT . '/tmp/' . $file );

        if( JFile::delete($filepath) )
        {
        	return true;
        }

        return false;
	}

	protected function _ingestGPX($file)
	{
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
					$this->app->enqueueMessage(JText::_('PLG_CONTENT_ZATRACKS_MESSAGE_FILE_LOAD_NO_SUCCESS'), 'error');

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
						$this->app->enqueueMessage(JText::_('PLG_CONTENT_ZATRACKS_MESSAGE_INVALID_GPX_DATA'), 'error');

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
                $this->app->enqueueMessage($e->getMessage(), 'warning');

                return false;
			}
		}

        return false;
	}

	protected function _processGPX($gpxJson)
	{
		if(!$gpxJson)
		{
			$this->app->enqueueMessage(JText::_('PLG_CONTENT_ZATRACKS_MESSAGE_DATA_PROCESSING_NO_SUCCESS'), 'error');
			
			return false;
		}

        $gpxData = new Joomla\Registry\Registry;
		$gpxData->loadString($gpxJson, 'JSON');
		$gpxData = $gpxData->toObject();

		if (!isset($this->processedData))
		{
			$this->processedData        = new stdClass();
		}
		
		$this->processedData->name      = $gpxData->journeys->journey0->name;
		$this->processedData->starttime = date("Y-m-d H:i:s", $gpxData->journeys->journey0->stats->start);
		$this->processedData->duration  = $gpxData->journeys->journey0->stats->journeyDuration;

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

        $this->processedData->geojson   = $this->_buildGeoJson($geoArray);
		$this->processedData->polyline  = $this->_buildPolyline($trackpoints);
		$this->processedData->distance  = $dc->getLength(new Location\Distance\Vincenty()) / 1000;
		$this->processedData->avs       = ($dc->getLength(new Location\Distance\Vincenty())/$this->processedData->duration)*3.6;

		return true;
	}

	protected function _buildPolyline($trackpoints)
	{
		$polylineEncoder = new PolylineEncoder;
		
        $encodedPolyline = $polylineEncoder->encode($trackpoints);

        return $encodedPolyline->points;	
	}

	protected function _buildGeoJson($geoArray)
	{
		$geoString  = '{"name":"NewFeatureType","type":"FeatureCollection","features":[{"type":"Feature","geometry":{"type":"LineString","coordinates":';
		$geoString .= json_encode($geoArray);
		$geoString .= '},"properties":null}]}';

		return $geoString;
	}
}