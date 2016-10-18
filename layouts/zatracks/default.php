<?php
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_SITE . '/plugins/content/zatracks/helpers/html');
$debug = $displayData["plg_params"]["debug_layout"];
?>
<dl>
    <dt><?php echo JText::_('PLG_CONTENT_ZATRACKS_FIELD_NAME_LBL');?></dt>
    <dd><?php echo $displayData['track']['name'] ;?></dd>
    <dt><?php echo JText::_('PLG_CONTENT_ZATRACKS_FIELD_STARTTIME_LBL');?></dt>
    <dd><?php echo JHtml::_('zatracks.humanizeDate', $displayData['track']['starttime']);?></dd>
    <dt><?php echo JText::_('PLG_CONTENT_ZATRACKS_FIELD_ACTIVITY_LBL');?></dt>
    <dd><?php echo JHtml::_('zatracks.humanizeActivity', $displayData['track']['activity']);?></dd>
    <dd><?php echo JHtml::_('zatracks.humanizeActivity', $displayData['track']['activity'], true);?></dd>
    <dt><?php echo JText::_('PLG_CONTENT_ZATRACKS_FIELD_DURATION_LBL');?></dt>
    <dd><?php echo JHtml::_('zatracks.humanizeDuration', $displayData['track']['duration']); ?></dd>
    <dt><?php echo JText::_('PLG_CONTENT_ZATRACKS_FIELD_DISTANCE_LBL');?></dt>
    <dd><?php echo $displayData['track']['distance'] ;?></dd>
    <dt><?php echo JText::_('PLG_CONTENT_ZATRACKS_FIELD_AVS_LBL');?></dt>
    <dd><?php echo $displayData['track']['avs']; ?></dd>
</dl>
<?php if ($displayData['plg_params']['show_map'] == 1) : ?>
    <?php if (!empty($displayData['track']['polyline'])) : ?>
        <?php $layout = new JLayoutFile('joomla.zatracks.map', $basePath = null, array('suffixes' => array('bs2', 'bs3'), 'debug' =>$debug));?>
        <?php echo $layout->render($displayData['track']['polyline']);?>
    <?php endif; ?>
<?php endif; ?>
