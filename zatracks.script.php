<?php
defined('_JEXEC') or die('Restricted access');


class PlgContentZatracksInstallerScript
{
 
    public function preflight($type)
    {
        if ($type != "discover_install" && $type != "install")
        {
            return true;
        }

        $version = new JVersion;

        if (version_compare($version->getShortVersion(), "3", 'lt'))
        {
            Jerror::raiseWarning(null, JText::_('PLG_CONTENT_ZATRACKS_INSTALL_NOJ2_ERROR'));

            return false;
        }

        return true;
    }

    public function install($parent)
    {
        
        JFactory::getApplication()->enqueueMessage(JText::_('PLG_CONTENT_ZATRACKS_INSTALL_NOTICE'), 'notice');
    }
}