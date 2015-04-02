<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Application;

use Piwik\Config;
use Piwik\Container\ContainerFactory;
use Piwik\Container\StaticContainer;
use Piwik\Plugin;

/**
 * Bootstraps the application.
 */
class Bootstrap
{
    /**
     * @var PluginList
     */
    private $pluginList;

    /**
     * @var string
     */
    private $environment;

    /**
     * @var array
     */
    private $extraDefinitions;

    public function __construct($environment = null)
    {
        $this->pluginList = new PluginList();
        $this->environment = $environment ?: StaticContainer::getEnvironment();
        $this->extraDefinitions = StaticContainer::getDefinitions() ?: array();
    }

    public function init()
    {
        $this->initContainer();
    }

    protected function initContainer()
    {
        $containerFactory = new ContainerFactory($this->pluginList, $this->environment, $this->extraDefinitions);
        $container = $containerFactory->create();

        StaticContainer::set($container);
    }
}