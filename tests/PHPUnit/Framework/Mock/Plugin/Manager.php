<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Tests\Framework\Mock\Plugin;

use Piwik\Config;
use Piwik\Tests\Framework\TestEnvironmentOverrides;

/**
 * TODO
 *
 * TODO: this + mock config aren't really mocks, they are overrides for the testing environment. not sure what to call them.
 */
class Manager extends \Piwik\Plugin\Manager
{
    public function __construct(Config $config, TestEnvironmentOverrides $overrides)
    {
        parent::__construct($config);

        $this->setPluginsToLoadDuringTests($config, $overrides);
    }

    public function getCoreAndSupportedPluginsToLoadDuringTests()
    {
        $disabledPlugins = $this->getCorePluginsDisabledByDefault();
        $disabledPlugins[] = 'LoginHttpAuth';
        $disabledPlugins[] = 'ExampleVisualization';

        $disabledPlugins = array_diff($disabledPlugins, array(
            'DBStats', 'ExampleUI', 'ExampleCommand', 'ExampleSettingsPlugin'
        ));

        $self = $this;
        $plugins = array_filter($this->readPluginsDirectory(), function ($pluginName) use ($disabledPlugins, $self) {
            if (in_array($pluginName, $disabledPlugins)) {
                return false;
            }

            return $self->isPluginBundledWithCore($pluginName)
                || $self->isPluginOfficialAndNotBundledWithCore($pluginName);
        });

        sort($plugins);

        return $plugins;
    }

    private function setPluginsToLoadDuringTests(Config $config, TestEnvironmentOverrides $overrides)
    {
        $pluginsToLoad = $this->getCoreAndSupportedPluginsToLoadDuringTests();
        if (!empty($pluginsToLoad)) {
            $pluginsToLoad = array_unique(array_merge($pluginsToLoad, $overrides->pluginsToLoad));
        }

        sort($pluginsToLoad);

        $config->Plugins = array('Plugins' => $pluginsToLoad);

        $this->unloadPlugins();
    }
}