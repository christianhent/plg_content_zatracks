<?php
/**
 * 
 * @category   GPX Extension
 * @package    Joomla.Plugin
 * @subpackage Content.Zatracks
 * @author     Christian Hent <hent.dev@googlemail.com>
 * @author     Matthias Bauer <matthias@pulpmedia.at>
 * @copyright  Pulpmedia Medientechnik und -design GmbH
 * @copyright  Copyright (C) 2017 Christian Hent
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://github.com/christianhent/plg_content_zatracks
 * @see 	   http://facstaff.unca.edu/mcmcclur/GoogleMaps/EncodePolyline/
 * 
 * @version    2.2.2
 * 
 */

defined('JPATH_PLATFORM') or die;

use Joomla\Registry\Registry;

class PolylineEncoder
{
	protected $numLevels;
	protected $zoomFactor;
	protected $verySmall;
	protected $forceEndpoints;
	protected $zoomLevelBreaks;


	public function __construct(Registry $numLevels = null, $zoomFactor = null, $verySmall = null, $forceEndpoints = null, $zoomLevelBreaks = null)
	{
		$this->numLevels = isset($numLevels) ? $numLevels : 18;
		$this->zoomFactor  = isset($zoomFactor) ? $zoomFactor : 2;
		$this->verySmall = isset($verySmall) ? $verySmall : 0.00001;
		$this->forceEndpoints = isset($forceEndpoints) ? $forceEndpoints : true;
		

		for($i = 0; $i < $this->numLevels; $i++) 
		{
			$this->zoomLevelBreaks[$i] = $this->verySmall*pow($this->zoomFactor, $this->numLevels-$i-1);
		}

	}

	public function encode($points) 
	{
		$absMaxDist = 0;

		if(count($points) > 2) 
		{
			$stack[] = array(0, count($points)-1);
			while(count($stack) > 0) 
			{
				$current = array_pop($stack);
				$maxDist = 0;
				for($i = $current[0]+1; $i < $current[1]; $i++)
				{
					$temp = $this->distance($points[$i], $points[$current[0]], $points[$current[1]]);
					if($temp > $maxDist)
					{
						$maxDist = $temp;
						$maxLoc = $i;
						if($maxDist > $absMaxDist) 
						{
							$absMaxDist = $maxDist;
						}
					}
				}
				if($maxDist > $this->verySmall)
				{
					$dists[$maxLoc] = $maxDist;
					array_push($stack, array($current[0], $maxLoc));
					array_push($stack, array($maxLoc, $current[1]));
				}
			}
		}

		$polyline = new stdClass();
		$polyline->rawPoints = $this->createEncodings($points, $dists);
		$polyline->levels = $this->encodeLevels($points, $dists, $absMaxDist);
		$polyline->points = str_replace("\\","\\\\", $polyline->rawPoints);
		$polyline->numLevels = $this->numLevels;
		$polyline->zoomFactor = $this->zoomFactor;

		return $polyline;

	}

	private function computeLevel($dd)
	{
		if($dd > $this->verySmall)
	    {
	        $lev = 0;
	        while($dd < $this->zoomLevelBreaks[$lev])
		{
		    $lev++;
		}
	    }
	    return $lev;
	}
	
	private function distance($p0, $p1, $p2)
	{
	    if($p1[0] == $p2[0] && $p1[1] == $p2[1])
	    {
	        $out = sqrt(pow($p2[0]-$p0[0],2) + pow($p2[1]-$p0[1],2));
	    }
	    else
	    {
	        $u = (($p0[0]-$p1[0])*($p2[0]-$p1[0]) + ($p0[1]-$p1[1]) * ($p2[1]-$p1[1])) / (pow($p2[0]-$p1[0],2) + pow($p2[1]-$p1[1],2));
	        if($u <= 0)
	        {
	            $out = sqrt(pow($p0[0] - $p1[0],2) + pow($p0[1] - $p1[1],2));
	        }
	        if($u >= 1) 
		{
	            $out = sqrt(pow($p0[0] - $p2[0],2) + pow($p0[1] - $p2[1],2));
	        }
	        if(0 < $u && $u < 1) 
		{
	            $out = sqrt(pow($p0[0]-$p1[0]-$u*($p2[0]-$p1[0]),2) + pow($p0[1]-$p1[1]-$u*($p2[1]-$p1[1]),2));
	        }
	    }
	    return $out;
	}
	
	private function encodeSignedNumber($num)
	{
	   $sgn_num = $num << 1;
	   if ($num < 0) 
	   {
	       $sgn_num = ~($sgn_num);
	   }
	   return $this->encodeNumber($sgn_num);
	}
	
	private function createEncodings($points, $dists)
	{
	    $plat = 0; $plng = 0; $encoded_points = '';
		
	    for($i=0; $i<count($points); $i++)
	    {
	        if(isset($dists[$i]) || $i == 0 || $i == count($points)-1) 
		{
		    $point = $points[$i];
		    $lat = $point[0];
		    $lng = $point[1];
		    $late5 = floor($lat * 1e5);
		    $lnge5 = floor($lng * 1e5);
		    $dlat = $late5 - $plat;
		    $dlng = $lnge5 - $plng;
		    $plat = $late5;
		    $plng = $lnge5;
		    $encoded_points .= $this->encodeSignedNumber($dlat) . $this->encodeSignedNumber($dlng);
		}
	    }
	    return $encoded_points;
	}
	
	private function encodeLevels($points, $dists, $absMaxDist) 
	{
		$encoded_levels = '';

	    if($this->forceEndpoints)
	    {
	        $encoded_levels .= $this->encodeNumber($this->numLevels-1);
	    }
	    else
	    {
	        $encoded_levels .= $this->encodeNumber($this->numLevels-$this->computeLevel($absMaxDist)-1);    
	    }
	    for($i=1; $i<count($points)-1; $i++)
	    {
	        if(isset($dists[$i]))
		{
		    $encoded_levels .= $this->encodeNumber($this->numLevels-$this->computeLevel($dists[$i])-1);
		}
	    }
	    if($this->forceEndpoints)
	    {
	        $encoded_levels .= $this->encodeNumber($this->numLevels -1);
	    }
	    else
	    {
	        $encoded_levels .= $this->encodeNumber($this->numLevels-$this->computeLevel($absMaxDist)-1);
	    }
	    return $encoded_levels;

	}
	
	private function encodeNumber($num)
	{
	    $encodeString = '';

	    while($num >= 0x20) 
	    {
	        $nextValue = (0x20 | ($num & 0x1f)) + 63;
	        $encodeString .= chr($nextValue);
		$num >>= 5;
	    }
	    $finalValue = $num + 63;
	    $encodeString .= chr($finalValue);
	    return $encodeString;

	}
}