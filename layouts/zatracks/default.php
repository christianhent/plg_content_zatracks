<?php
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_SITE . '/plugins/content/zatracks/helpers/html');

$track = $displayData['track'];
$debug = $displayData["plg_params"]["debug_layout"];
?>
<dl>
    <dt><?php echo JText::_('PLG_CONTENT_ZATRACKS_FIELD_NAME_LBL');?></dt>
    <dd><?php echo $track['name'] ;?></dd>
    <dt><?php echo JText::_('PLG_CONTENT_ZATRACKS_FIELD_STARTTIME_LBL');?></dt>
    <dd><?php echo JHtml::_('zatracks.humanizeDate', $track['starttime']);?></dd>
    <dt><?php echo JText::_('PLG_CONTENT_ZATRACKS_FIELD_ACTIVITY_LBL');?></dt>
    <dd><?php echo JHtml::_('zatracks.humanizeActivity', $track['activity']);?></dd>
    <dd><?php echo JHtml::_('zatracks.humanizeActivity', $track['activity'], true);?></dd>
    <dt><?php echo JText::_('PLG_CONTENT_ZATRACKS_FIELD_DURATION_LBL');?></dt>
    <dd><?php echo JHtml::_('zatracks.humanizeDuration', $track['duration']); ?></dd>
    <dt><?php echo JText::_('PLG_CONTENT_ZATRACKS_FIELD_DISTANCE_LBL');?></dt>
    <dd><?php echo $track['distance'] ;?></dd>
    <dt><?php echo JText::_('PLG_CONTENT_ZATRACKS_FIELD_AVS_LBL');?></dt>
    <dd><?php echo $track['avs']; ?></dd>
</dl>
<?php if ($displayData['plg_params']['show_map'] == 1) : ?>
    <?php if (!empty($track['polyline'])) : ?>
        <?php $layout = new JLayoutFile('joomla.zatracks.map', $basePath = null, array('suffixes' => array('bs2', 'bs3'), 'debug' => (bool)$debug));?>
        <?php echo $layout->render($track['polyline']);?>
    <?php endif; ?>
<?php endif; ?>
