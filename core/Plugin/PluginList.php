<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugin;

use Piwik\Config\IniFileChain;

/**
 * Contains the list of installed and activated plugins.
 */
class PluginList
{
    /**
     * @var IniFileChain
     */
    private $iniFileChain;

    public function __construct(IniFileChain $iniFileChain)
    {
        $this->iniFileChain = $iniFileChain;
    }

    /**
     * @return string[]
     */
    public function getActivatedPlugins()
    {
        $section = $this->iniFileChain->get('Plugins');
        $plugins = $section['Plugins'];

        return $this->sortPlugins($plugins);
    }

    /**
     * Sort a list of plugin names in the same order as the global config.
     *
     * @param string[] $plugins
     * @return string[]
     */
    private function sortPlugins(array $plugins)
    {
        $plugins = array_unique($plugins);
        if ($this->doLoadAlwaysActivatedPlugins) {
            $plugins = array_merge($plugins, $this->pluginToAlwaysActivate);
        }
        $plugins = array_unique($plugins);
        $plugins = $this->sortPluginsSameOrderAsGlobalConfig($plugins);
        return $plugins;
    }
}
