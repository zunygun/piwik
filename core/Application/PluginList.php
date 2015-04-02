<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Application;
use Piwik\Config\IniFileChain;
use Piwik\Config;

/**
 * TODO
 */
class PluginList
{
    private $iniFileChain = null;

    public function __construct()
    {
        $this->iniFileChain = new IniFileChain(array(Config::getGlobalConfigPath(), Config::getCommonConfigPath()), Config::getLocalConfigPath());
    }

    public function getActivatedPlugins()
    {
        $section = $this->iniFileChain->get('Plugins');
        return $section['Plugins'];
    }
}