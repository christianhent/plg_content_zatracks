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

JHtml::addIncludePath(JPATH_SITE . '/plugins/content/zatracks/helpers/html');

$dbg = (int)$displayData["plg_params"]["debug_layout"];
$trc = $displayData['track'];
?>
<dl>
    <dt><?php echo JText::_('PLG_CONTENT_ZATRACKS_FIELD_NAME_LBL');?></dt>
    <dd><?php echo $trc['name'] ;?></dd>
    <dt><?php echo JText::_('PLG_CONTENT_ZATRACKS_FIELD_STARTTIME_LBL');?></dt>
    <dd><?php echo JHtml::_('zatracks.humanizeDate', $trc['starttime']);?></dd>
    <dt><?php echo JText::_('PLG_CONTENT_ZATRACKS_FIELD_ACTIVITY_LBL');?></dt>
    <dd><?php echo JHtml::_('zatracks.humanizeActivity', $trc['activity']);?></dd>
    <dd><?php echo JHtml::_('zatracks.humanizeActivity', $trc['activity'], true);?></dd>
    <dt><?php echo JText::_('PLG_CONTENT_ZATRACKS_FIELD_DURATION_LBL');?></dt>
    <dd><?php echo JHtml::_('zatracks.humanizeDuration', $trc['duration']); ?></dd>
    <dt><?php echo JText::_('PLG_CONTENT_ZATRACKS_FIELD_DISTANCE_LBL');?></dt>
    <dd><?php echo $trc['distance'] ;?></dd>
    <dt><?php echo JText::_('PLG_CONTENT_ZATRACKS_FIELD_AVS_LBL');?></dt>
    <dd><?php echo $trc['avs']; ?></dd>
</dl>

<?php if ($displayData['plg_params']['show_map'] == 1 && !empty($trc['geojson'])  ) : ?>
    <?php $layout = new JLayoutFile('joomla.zatracks.map', $basePath = null, array('suffixes' => array(),'debug' =>(bool)$dbg));?>
    <?php echo $layout->render($trc['geojson']);?>
<?php endif; ?>

<!-- or use instead the encoded polyline -->

<?php //if ($displayData['plg_params']['show_map'] == 1 && !empty($trc['polyline'])  ) : ?>
    <?php //$layout = new JLayoutFile('joomla.zatracks.map', $basePath = null, array('suffixes' => array('polyline'),'debug' =>(bool)$dbg));?>
    <?php //echo $layout->render($trc['polyline']);?>
<?php //endif; ?>
