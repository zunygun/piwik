<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Tests\Framework\Mock;

use Piwik\Tests\Framework\TestEnvironmentOverrides;

class Config extends \Piwik\Config
{
    public function __construct(TestEnvironmentOverrides $overrides, $pathGlobal = null, $pathLocal = null, $pathCommon = null, $allowSaving = false)
    {
        $pathGlobal = $pathGlobal ?: $overrides->configFileCommon;
        $pathLocal = $pathLocal ?: $overrides->configFileLocal;
        $pathCommon = $pathCommon ?: $overrides->configFileCommon;

        parent::__construct($pathGlobal, $pathLocal, $pathCommon);

        self::doSetTestEnvironment($this, $allowSaving);

        if (!$overrides->dontUseTestConfig) {
            $this->handleTestEnvironmentOverrides($overrides);
        }
    }

    public static function doSetTestEnvironment(\Piwik\Config $config, $allowSaving = false)
    {
        if (!$allowSaving) {
            $config->doNotWriteConfigInTests = true;
        }

        if (isset($config->configGlobal['database_tests'])
            || isset($config->configLocal['database_tests'])
        ) {
            $config->__get('database_tests');
            $config->configCache['database'] = $config->configCache['database_tests'];
        }

        // Ensure local mods do not affect tests
        if (empty($pathGlobal)) {
            $config->configCache['Debug'] = $config->configGlobal['Debug'];
            $config->configCache['mail'] = $config->configGlobal['mail'];
            $config->configCache['General'] = $config->configGlobal['General'];
            $config->configCache['Segments'] = $config->configGlobal['Segments'];
            $config->configCache['Tracker'] = $config->configGlobal['Tracker'];
            $config->configCache['Deletelogs'] = $config->configGlobal['Deletelogs'];
            $config->configCache['Deletereports'] = $config->configGlobal['Deletereports'];
            $config->configCache['Development'] = $config->configGlobal['Development'];
            $config->configCache['Plugins'] = $config->configGlobal['Plugins'];
        }

        // for unit tests, we set that no plugin is installed. This will force
        // the test initialization to create the plugins tables, execute ALTER queries, etc.
        $config->configCache['PluginsInstalled'] = array('PluginsInstalled' => array());
    }

    private function handleTestEnvironmentOverrides(TestEnvironmentOverrides $overrides)
    {
        if ($overrides->configFileLocal) {
            $this->General['session_save_handler'] = 'dbtable';
        }

        $this->log['log_writers'] = array('file');

        // TODO: replace this and below w/ configOverride use
        if ($overrides->tablesPrefix) {
            $this->database['tables_prefix'] = $overrides->tablesPrefix;
        }

        if ($overrides->dbName) {
            $this->database['dbname'] = $overrides->dbName;
        }

        if (!empty($overrides->configOverride)) {
            $this->configCache = $this->array_merge_recursive_distinct($this->configCache, $overrides->configOverride);
        }
    }
}