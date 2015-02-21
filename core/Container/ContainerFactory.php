<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Container;

use DI\Container;
use DI\ContainerBuilder;
use DI\Definition\Source\ArrayDefinitionSource;
use Doctrine\Common\Cache\ArrayCache;
use Piwik\Config;
use Piwik\Development;
use Piwik\Plugin\Manager;

/**
 * Creates a configured DI container.
 */
class ContainerFactory
{
    /**
     * TODO
     *
     * @var string[]
     */
    private static $rootEntryKeys = array('Piwik\Config', 'Piwik\Plugin\Manager');

    /**
     * Optional environment config to load.
     *
     * @var string|null
     */
    private $environment;

    /**
     * TODO
     */
    private $hardcodedConfigFiles;

    /**
     * TODO
     */
    private $hardcodedConfigEntries;

    /**
     * @param string|null $environment Optional environment config to load.
     */
    public function __construct($environment = null)
    {
        $this->environment = $environment;

        $this->hardcodedConfigFiles = array(
            'global' => PIWIK_USER_PATH . '/config/global.php',
            'dev' => PIWIK_USER_PATH . '/config/environment/dev.php',
            'config' => PIWIK_USER_PATH . '/config/config.php'
        );

        $this->hardcodedConfigFiles[$environment] = sprintf('%s/config/environment/%s.php', PIWIK_USER_PATH, $environment);
    }

    /**
     * @link http://php-di.org/doc/container-configuration.html
     * @throws \Exception
     * @return Container
     */
    public function create()
    {
        $rootContainer = $this->createRootContainer();

        $builder = $this->createContainerBuilder();

        // root objects
        $builder->addDefinitions(new ContainerDefinitionSource($rootContainer));

        // INI config
        $config = $rootContainer->get('Piwik\Config');
        $builder->addDefinitions(new IniConfigDefinitionSource($config));

        // Global config
        $builder->addDefinitions($this->getNonRootContainerDefinitions('global'));

        // Plugin configs
        $pluginManager = $rootContainer->get('Piwik\Plugin\Manager');
        $this->addPluginConfigs($builder, $pluginManager);

        // Development config
        if (Development::isEnabled($config)) {
            $builder->addDefinitions($this->getNonRootContainerDefinitions('dev'));
        }

        // User config
        if (file_exists(PIWIK_USER_PATH . '/config/config.php')) {
            $builder->addDefinitions($this->getNonRootContainerDefinitions('config'));
        }

        // Environment config
        $this->addEnvironmentConfig($builder);

        return $builder->build();
    }

    private function addEnvironmentConfig(ContainerBuilder $builder)
    {
        if (!$this->environment) {
            return;
        }

        $builder->addDefinitions($this->getNonRootContainerDefinitions($this->environment));
    }

    private function addPluginConfigs(ContainerBuilder $builder, Manager $pluginManager)
    {
        $plugins = $pluginManager->getActivatedPluginsFromConfig();

        foreach ($plugins as $plugin) {
            $file = Manager::getPluginsDirectory() . $plugin . '/config/config.php';

            if (! file_exists($file)) {
                continue;
            }

            $builder->addDefinitions($file);
        }
    }

    private function createRootContainer()
    {
        $builder = $this->createContainerBuilder();

        // TODO: code redundancy w/ above
        // Global config
        $builder->addDefinitions($this->getRootContainerDefinitions('global'));

        // Development config
        if (false) {//Development::isEnabled()) {
            $builder->addDefinitions($this->getRootContainerDefinitions('dev'));
        }

        // User config
        if (file_exists(PIWIK_USER_PATH . '/config/config.php')) {
            $builder->addDefinitions($this->getRootContainerDefinitions('config'));
        }

        // Environment config
        if ($this->environment) {
            $builder->addDefinitions($this->getRootContainerDefinitions($this->environment));
        }

        return $builder->build();
    }

    private function createContainerBuilder()
    {
        $builder = new ContainerBuilder();

        $builder->useAnnotations(false);
        $builder->setDefinitionCache(new ArrayCache());

        return $builder;
    }

    private function getRootContainerDefinitions($key)
    {
        list($root, $rest) = $this->getContainerDefinitions($key);
        return new ArrayDefinitionSource($root);
    }

    private function getNonRootContainerDefinitions($key)
    {
        list($root, $rest) = $this->getContainerDefinitions($key);
        return new ArrayDefinitionSource($rest);
    }

    private function getContainerDefinitions($key)
    {
        if (empty($this->hardcodedConfigEntries[$key])) {
            $file = $this->hardcodedConfigFiles[$key];

            $entries = require $file;
            $rootEntries = $this->filterRootEntries($entries);

            $this->hardcodedConfigEntries[$key] = array($rootEntries, $entries);
        }

        return $this->hardcodedConfigEntries[$key];
    }

    private function filterRootEntries(&$entries)
    {
        $rootEntries = array();
        foreach ($entries as $key => $entry) {
            if (in_array($key, self::$rootEntryKeys)) {
                $rootEntries[$key] = $entry;
                unset($entries[$key]);
            }
        }
        return $rootEntries;
    }
}
