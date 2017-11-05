<?php
/**
 * 
 * @category   GPX Extension
 * @package    Joomla.Plugin
 * @subpackage Content.Zatracks
 * @author     Christian Hent <hent.dev@googlemail.com>
 * @copyright  Copyright(C) 2013 B Tasker (http://www.bentasker.co.uk)
 * @copyright  Copyright (C) 2017 Christian Hent
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://github.com/christianhent/plg_content_zatracks
 * 
 * @version    2.2.3
 * 
 */

defined('_JEXEC') or die;

class GPXProcessor
{
	private $file;
	private $xml;
	private $journey;
	private $tracks = array();
	private $totaltimes;
	private $ftimes;
	private $trackduration;
	
	private $processor_version = '2.2.0';

	function __construct()
	{
		
		$this->journey = new stdClass();
	}

	public function reset()
	{
		$this->journey = new stdClass();
		$this->tracks = array();
	}

	public function loadFile($file)
	{
		$this->xml = simplexml_load_file($file);
		if ($this->xml){
			return true;
		}else{
			$this->xml = false;
			return false;
		}
	}

	public function ingest()
	{
		if (!is_object($this->xml)){
			return false;
		}

		$this->journey = new stdClass();
		$this->journey->created = new stdClass();
		$this->journey->stats = new stdClass();
		$this->journey->stats->trackpoints = 0;
		$this->journey->stats->recordedDuration = 0;
		$this->journey->stats->segments = 0;
		$this->journey->stats->tracks = 0;
		$this->journey->created->creator = (string) $this->xml['creator'];
		$this->journey->created->version = (string) $this->xml['version'];
		$this->journey->created->format = 'GPX';

		if (isset($this->xml->time)){
			$this->journey->created->time = strtotime($this->xml->time);
		}

		$this->journey->timezone = date_default_timezone_get();

		$a = 0;

		foreach ($this->xml->trk as $trk)
		{
			$this->resetTrackStats();
			$b = 0;

			$jkey = $this->genTrackKey($a);
			$this->initTrack($jkey,$trk->name);

			foreach ($trk->trkseg as $trkseg)
			{
				$this->resetSegmentStats();
				$x = 0;
				$times = array();
				$lastele = false;
				$timemoving = 0;
				$timestationary = 0;
				$segkey = $this->genSegKey($b);
				$this->initSegment($jkey,$segkey);
				
				foreach ($trkseg->trkpt as $trkpt)
				{
					$key = "trackpt$x";
					$time = strtotime($trkpt->time);

					if (!isset($this->journey->journeys->$jkey->segments->$segkey->points))
					{
						$this->journey->journeys->$jkey->segments->$segkey->points = new stdClass();
					}
					if (!isset($this->journey->journeys->$jkey->segments->$segkey->points->$key))
					{
						$this->journey->journeys->$jkey->segments->$segkey->points->$key = new stdClass();
					}
					
					$this->journey->journeys->$jkey->segments->$segkey->points->$key->lat = (string) $trkpt['lat'];
					$this->journey->journeys->$jkey->segments->$segkey->points->$key->lon = (string) $trkpt['lon'];
					
					$ele = (string) $trkpt->ele;
					$this->journey->journeys->$jkey->segments->$segkey->points->$key->elevation = $ele;

					$change = 0;
					if ($lastele){
						$change = $ele - $lastele;
					}
					
					$this->jeles[] = $ele;
					$this->seles[] = $ele;
					$this->feles[] = $ele;

					$this->jeledevs[] = $change;
					$this->seledevs[] = $change;
					$this->feledevs[] = $change;
							
					$lastele = $ele;
					
					$this->journey->journeys->$jkey->segments->$segkey->points->$key->time = $time;
					$times[] = $time;
					$lasttime = $time; //smarttrack, old
					$this->lasttimestamp = $time;

					$x++;
				}

				$this->writeSegmentStats($jkey,$segkey,$times,$x,$timemoving,$timestationary);
				$b++;
			}

			$this->writeTrackStats($jkey);
		}

		$this->journey->stats->start = min($this->totaltimes);
		$this->journey->stats->end = max($this->totaltimes);
		$this->journey->journeys->$jkey->segments->$segkey->stats->elevation = new stdClass();
		$this->journey->journeys->$jkey->segments->$segkey->stats->elevation->max = max($this->jeles);
		$this->journey->journeys->$jkey->segments->$segkey->stats->elevation->min = min($this->jeles);
		$this->journey->journeys->$jkey->segments->$segkey->stats->elevation->avgChange = round(array_sum($this->jeledevs)/count($this->jeledevs),2);
		$this->journey->metadata = new stdClass();
		$this->journey->metadata->GPXProcessorVersion = $this->processor_version;
	}

	public function getJSON()
	{
		
		return json_encode($this->journey);
	}

	###

	private function genTrackKey($i)
	{
		
		return "journey$i";
	}

	private function genSegKey($i)
	{
		
		return "seg$i";
	}

	private function initSegment($jkey,$segkey)
	{
		$this->journey->journeys->$jkey->segments->$segkey = new stdClass();
		$this->lasttimestamp = false;
	}

	private function initTrack($jkey,$trk)
	{
		$this->journey = new stdClass();
		$this->journey->journeys = new stdClass();
		$this->journey->journeys->$jkey = new stdClass();
		$this->journey->journeys->$jkey->segments = new stdClass();
		$this->journey->journeys->$jkey->name = (string) $trk;
		$this->journey->journeys->$jkey->stats = new stdClass();
		$this->journey->journeys->$jkey->stats->journeyDuration = 0;
		$this->tracks[$jkey]['name'] = $this->journey->journeys->$jkey->name;
		$this->tracks[$jkey]['segments'] = array();
	}

	private function writeSegmentStats($jkey,$segkey,$times,$x,$timemoving,$timestationary)
	{
		$start = min($times);
		$end = max($times);
		$duration = $end - $start;
		$this->journey->journeys->$jkey->segments->$segkey->stats = new stdClass();
		$this->journey->journeys->$jkey->segments->$segkey->stats->start = $start;
		$this->journey->journeys->$jkey->segments->$segkey->stats->end = $end;
		$this->journey->journeys->$jkey->segments->$segkey->stats->journeyDuration = $duration;
		$this->journey->journeys->$jkey->stats->journeyDuration = $this->journey->journeys->$jkey->stats->journeyDuration + $duration;
		$this->trackduration = $this->trackduration + $this->journey->journeys->$jkey->stats->journeyDuration;
		$this->ftimes[] = $this->journey->journeys->$jkey->segments->$segkey->stats->start;
		$this->ftimes[] = $this->journey->journeys->$jkey->segments->$segkey->stats->end;
		$this->journey->journeys->$jkey->segments->$segkey->stats->elevation = new stdClass();
		$this->journey->journeys->$jkey->segments->$segkey->stats->elevation->max = max($this->seles);
		$this->journey->journeys->$jkey->segments->$segkey->stats->elevation->min = min($this->seles);
		$this->journey->journeys->$jkey->segments->$segkey->stats->elevation->avgChange = round(array_sum($this->seledevs)/count($this->seledevs),2);
		$this->tracks[$jkey]['segments'][$segkey] = $x++;
		$this->journey->stats = new stdClass();
		$this->journey->stats->segments = new stdClass();
		$this->journey->stats->segments++;
	}

	private function writeTrackStats($jkey)
	{
		$this->journey->journeys->$jkey->stats->start = min($this->ftimes);
		$this->journey->journeys->$jkey->stats->end = max($this->ftimes);
		$this->journey->journeys->$jkey->stats->recordedDuration = $this->trackduration;
		$this->totaltimes[] = $this->journey->journeys->$jkey->stats->start;
		$this->totaltimes[] = $this->journey->journeys->$jkey->stats->end;
		$this->journey->stats->recordedDuration = 0;
		$this->journey->stats->recordedDuration = $this->journey->stats->recordedDuration + $this->trackduration;
		$this->journey->journeys->$jkey->stats->elevation = new stdClass();
		$this->journey->journeys->$jkey->stats->elevation->max = max($this->feles);
		$this->journey->journeys->$jkey->stats->elevation->min = min($this->feles);
		$this->journey->journeys->$jkey->stats->elevation->avgChange = round(array_sum($this->feledevs)/count($this->feledevs),2);	
	}

	private function resetTrackStats()
	{
		$this->ftimes = array();
		$this->trackduration = 0;
		$this->feles = array();
		$this->feledevs = array();
	}

	private function resetSegmentStats()
	{
		$this->seles = array();
		$this->seledevs = array();
	}

}