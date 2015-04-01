<?php

namespace Piwik\Config;

use Piwik\Config;
use Piwik\Piwik;
use Piwik\SettingsServer;

class IniFileChainFactory
{
    private $pathGlobal;
    private $pathCommon;
    private $pathLocal;

    public function __construct($pathGlobal = null, $pathLocal = null, $pathCommon = null)
    {
        $this->pathGlobal = $pathGlobal ?: Config::getGlobalConfigPath();
        $this->pathCommon = $pathCommon ?: Config::getCommonConfigPath();
        $this->pathLocal = $pathLocal ?: Config::getLocalConfigPath();
    }

    /**
     * Reloads config data from disk.
     *
     * @throws \Exception if the global config file is not found and this is a tracker request
     */
    public function create()
    {
        $inTrackerRequest = SettingsServer::isTrackerApiRequest();

        // read defaults from global.ini.php
        if (!is_readable($this->pathGlobal) && $inTrackerRequest) {
            // TODO why throw the exception only if in tracker request (since the exception will be thrown later anyway)?
            throw new \Exception(Piwik::translate('General_ExceptionConfigurationFileNotFound', array($this->pathGlobal)));
        }

        if (is_readable($this->pathLocal)) {
            $pathLocal = $this->pathLocal;
        } else {
            // No exception thrown here since we want to be able to create the container and load the plugins
            $pathLocal = null;
        }

        $iniFileChain = new IniFileChain(array($this->pathGlobal, $this->pathCommon), $pathLocal);

        // decode section data
        Config::decodeValues($iniFileChain->getAll());

        return $iniFileChain;
    }
}
