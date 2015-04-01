<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Application;

use Piwik\Config\IniFileChain;
use Piwik\Config\IniFileChainFactory;
use Piwik\Container\ContainerFactory;
use Piwik\Container\StaticContainer;
use Piwik\Plugin;
use Piwik\Tests\Framework\Fixture;
use Piwik_TestingEnvironment;

/**
 * Bootstraps the application.
 */
class Bootstrap
{
    /**
     * @var string|null
     */
    private $environment;

    /**
     * @var bool
     */
    private $loadTrackerPlugins;

    /**
     * @var IniFileChain|null
     */
    private $iniFileChain;

    /**
     * @param string|null $environment The environment config to load (cli, test, dev)
     * @param bool $loadTrackerPlugins Should the plugin manager load tracker plugins only?
     * @param IniFileChain|null $iniFileChain Optionally pass an ini file chain if you want to bootstrap with an existing configuration
     */
    public function __construct($environment = null, $loadTrackerPlugins = false, IniFileChain $iniFileChain = null)
    {
        $this->environment = $environment;
        $this->loadTrackerPlugins = $loadTrackerPlugins;
        $this->iniFileChain = $iniFileChain;
    }

    public function init()
    {
        // TODO replace with something cleaner later
        if (class_exists('Piwik_TestingEnvironment', false)) {
            $testingEnvironment = new Piwik_TestingEnvironment();
        } else {
            $testingEnvironment = null;
        }

        if (!$this->iniFileChain) {
            $this->iniFileChain = $this->createIniFileChain($testingEnvironment);
        }

        $pluginManager = $this->initPlugins();
        $this->initContainer($testingEnvironment);

        if (!$this->loadTrackerPlugins) {
            // TODO move translation loading in a proper place later
            // We need to load it early because some exception messages are translated
            $pluginManager->loadPluginTranslations();
        }
    }

    /**
     * @return IniFileChain
     */
    private function createIniFileChain(Piwik_TestingEnvironment $testingEnvironment = null)
    {
        // TODO replace with something cleaner later
        if ($testingEnvironment && $testingEnvironment->dontUseTestConfig) {
            $factory = new IniFileChainFactory(
                $testingEnvironment->configFileGlobal,
                $testingEnvironment->configFileLocal,
                $testingEnvironment->configFileCommon
            );
        } else {
            $factory = new IniFileChainFactory();
        }

        return $factory->create();
    }

    private function initPlugins()
    {
        $pluginManager = new Plugin\Manager($this->iniFileChain);

        if ($this->loadTrackerPlugins) {
            $pluginManager->loadTrackerPlugins();
        } else {
            $pluginManager->loadActivatedPlugins();
        }

        return $pluginManager;
    }

    private function initContainer(Piwik_TestingEnvironment $testingEnvironment = null)
    {
        $definitions = array();

        // Apply DI config from the test fixture
        // TODO replace with something cleaner later
        if ($testingEnvironment && $testingEnvironment->fixtureClass) {
            $fixtureClass = $testingEnvironment->fixtureClass;
            if (class_exists($fixtureClass)) {
                /** @var Fixture $fixture */
                $fixture = new $fixtureClass;
                $diConfig = $fixture->provideContainerConfig();
                if (!empty($diConfig)) {
                    $definitions = $diConfig;
                }
            }
        }

        $containerFactory = new ContainerFactory($this->iniFileChain, $this->environment, $definitions);

        $container = $containerFactory->create();

        $container->set('Piwik\Config\IniFileChain', $this->iniFileChain);
        $container->set('Piwik\Plugin\Manager', Plugin\Manager::getInstance());

        StaticContainer::set($container);
    }
}
