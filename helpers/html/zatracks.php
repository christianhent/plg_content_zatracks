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
 * @version    2.2.1
 * 
 */
defined('_JEXEC') or die;

abstract class JHtmlZatracks
{
    public static function humanizeDate($date)
	{
		$date = strtotime($date);
		
		$diff = time() - (int)$date;

    	if ($diff == 0){
            return 'now';
        }

    	$intervals = array
    	(
        	1                   => array('year',    31556926),
        	$diff < 31556926    => array('month',   2628000),
        	$diff < 2629744     => array('week',    604800),
        	$diff < 604800      => array('day',     86400),
        	$diff < 86400       => array('hour',    3600),
        	$diff < 3600        => array('minute',  60),
        	$diff < 60          => array('second',  1)
    	);

     	$val = floor($diff/$intervals[1][1]);
     	
     	return $val.' '.$intervals[1][0].($val > 1 ? 's' : '').' ago';
	}

	public static function humanizeDuration($duration)
	{
		$sec = $duration; 
		$h   = floor($sec /3600);
		$m   = floor(($sec - $h *3600) / 60);
		$s   = $sec % 60;
        
        printf("%02d:%02d:%02d", $h, $m, $s);
	}

    public static function humanizeActivity($activity, $verb = false)
	{
        if($verb === true)
        {
            $arrayActivities = array(
                0 => JText::_('PLG_CONTENT_ZATRACKS_ACTIVITY_WALK_PAST_VERB'),
                1 => JText::_('PLG_CONTENT_ZATRACKS_ACTIVITY_BIKE_PAST_VERB'),
                2 => JText::_('PLG_CONTENT_ZATRACKS_ACTIVITY_RUN_PAST_VERB'),
                3 => JText::_('PLG_CONTENT_ZATRACKS_ACTIVITY_CANOE_PAST_VERB')
            );
        }
        else
        {
            $arrayActivities = array(
                0 => JText::_('PLG_CONTENT_ZATRACKS_ACTIVITY_WALK'),
                1 => JText::_('PLG_CONTENT_ZATRACKS_ACTIVITY_BIKE'),
                2 => JText::_('PLG_CONTENT_ZATRACKS_ACTIVITY_RUN'),
                3 => JText::_('PLG_CONTENT_ZATRACKS_ACTIVITY_CANOE')
            );
        }

		return $arrayActivities[$activity];
	}
}