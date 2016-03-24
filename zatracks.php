<?php
defined('JPATH_BASE') or die;

use Joomla\Utilities\ArrayHelper;
use Joomla\String\StringHelper;

jimport('joomla.plugin.plugin');

require_once __DIR__ . '/helper.php';

class PlgContentZatracks extends JPlugin
{
	protected $app;
	protected $libPath;
	protected $tmpFile;
	protected $processedData = NULL;
	protected $layoutPath;
	protected $trackLayout;
	protected $placeholder = 'zatracks';
	protected $helper;

	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		$this->app         = JFactory::getApplication();
		$this->libPath     = JPath::clean(JPATH_ROOT .'/plugins/content/zatracks/lib/');
		$this->helper      = new PlgContentZatracksHelper();
		$this->tmpFile     = 'tmp.gpx';
		$this->regexpr     = '/{'.$this->placeholder.'.*?}/i';
		$this->layoutPath  = JPath::clean(JPATH_ROOT .'/layouts/joomla/zatracks/');

		$this->trackLayout = new JLayoutFile('joomla.zatracks.default', $basePath = null, array(
			'suffixes' => array('bs2', 'bs3'),
			'debug' => (bool)$this->params->get('debug_layout')
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
			$data = $this->helper->loadTrack($data);
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
			if(!$this->helper->loadGPXLib($this->libPath))
			{
				$this->app->enqueueMessage(JText::_('PLG_CONTENT_ZATRACKS_MESSAGE_LIB_NOT_FOUND'), 'error');

				return true;
			}

			if (isset($file['name']) && $file['name'] !='') 
			{
				if($this->helper->uploadFile($file, $this->params->get('maxsize'), $this->tmpFile ))
				{
					$gpxJson = $this->helper->ingestGPX($this->tmpFile);

					if(!$gpxJson)
					{
						$this->app->enqueueMessage(JText::_('PLG_CONTENT_ZATRACKS_MESSAGE_INGEST_ERROR'), 'error');
					}

					$this->processedData = $this->helper->processGPX($gpxJson);

					if(is_object($this->processedData))
					{
						$this->app->enqueueMessage(JText::_('PLG_CONTENT_ZATRACKS_MESSAGE_DATA_PROCESSING_SUCCESS'), 'message');
					}

					if( $this->helper->deleteFile($this->tmpFile) )
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

		$this->helper->saveTrack($content_id, $context, $this->processedData, $editableFormData);

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
			if ( StringHelper::strpos( $row->text, '{'.$this->placeholder ) === false )
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
			$row = $this->helper->loadTrack($row);
		}

		if(!isset($row->track))
		{
			return false;
		}

		if ($this->params->get('output') == 3) 
		{

			if ( StringHelper::strpos( $row->text, '{'.$this->placeholder ) === false )
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

		return $this->helper->renderTrack($row, $this->params, $this->trackLayout);
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

		return $this->helper->renderTrack($row, $this->params, $this->trackLayout);
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

		return $this->helper->renderTrack($row, $this->params, $this->trackLayout);
	}

	public function onAfterDispatch()
	{

		JLayoutHelper::$defaultBasePath = "";
	}
	
}