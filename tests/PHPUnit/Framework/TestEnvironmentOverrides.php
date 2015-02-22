<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Tests\Framework;

use Exception;
use Piwik\Common;
use Piwik\DbHelper;
use Piwik\Option;
use Piwik\Piwik;

class Piwik_MockAccess // TODO: move this or remove it?
{
    private $access;

    public function __construct($access)
    {
        $this->access = $access;
        $access->setSuperUserAccess(true);
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array(array($this->access, $name), $arguments);
    }

    public function reloadAccess($auth = null)
    {
        return true;
    }

    public function getLogin()
    {
        return 'superUserLogin';
    }
}

/**
 * TODO
 */
class TestEnvironmentOverrides
{
    /**
     * TODO
     *
     * @var string
     */
    private $pathToProperties;

    /**
     * TODO
     *
     * @var string[]
     */
    public $queryParamOverride = array();

    /**
     * TODO
     *
     * @var string[]
     */
    public $globalsOverride = array();

    /**
     * TODO
     *
     * @var string
     */
    public $hostOverride = null;

    /**
     * TODO
     *
     * @var bool
     */
    public $useXhprof = false;

    /**
     * TODO
     *
     * @var bool
     */
    public $testUseRegularAuth = false;

    /**
     * TODO
     *
     * @var string
     */
    public $configFileLocal = null;

    /**
     * TODO
     *
     * @var string
     */
    public $configFileGlobal = null;

    /**
     * TODO
     *
     * @var string
     */
    public $configFileCommon;

    /**
     * TODO
     *
     * @var bool
     */
    public $dontUseTestConfig = false;// TODO: is this necessary?

    /**
     * TODO
     *
     * @var string
     * @deprecated since version 2.11 use configOverride property instead
     */
    public $tablesPrefix = false;

    /**
     * TODO
     *
     * @var string
     * @deprecated since version 2.11 use configOverride property instead
     */
    public $dbName = false;

    /**
     * TODO
     *
     * @var array
     */
    public $configOverride = array();

    /**
     * TODO
     *
     * @var array
     */
    public $optionsOverride = array();

    /**
     * TODO
     *
     * @var bool
     */
    public $deleteArchiveTables = false;

    /**
     * TODO
     *
     * @var string[]
     */
    public $pluginsToLoad = array();

    /**
     * TODO
     * TODO: otherProperties is a bad name. setting these vars are like IPC for PHP => JS or other process. ie, we set in PHP,
     *       and other processes read them. so what do we call this?
     *
     * @var string[]
     */
    public $otherProperties = null;

    public function __construct($pathToProperties = null)
    {
        $this->pathToProperties = $pathToProperties ?: self::getDefaultPathToProperties();

        $this->loadPropertiesFromFile($pathToProperties);

        $this->setUpPiwikEnvironmentForTests();

        $this->handleQueryParamOverride();
        $this->handleGlobalsOverride();
        $this->handleHostOverride();
        $this->handleUseXhprof();

        $this->addExtraHooks();
    }

    private function loadPropertiesFromFile()
    {
        if (!file_exists($this->pathToProperties)) {
            return;
        }

        $data = json_decode(file_get_contents($this->pathToProperties), true);
        foreach ($data as $key => $value) {
            if (!property_exists($this, $key)) {
                throw new \RuntimeException("Invalid property '$key' found in test environment overrides property file.");
            }

            $this->$key = $value;
        }
    }

    public function save($data = null)
    {
        @mkdir(PIWIK_INCLUDE_PATH . '/tmp');

        $properties = $data ?: get_object_vars($this);
        file_put_contents($this->pathToProperties, json_encode($properties));
    }

    public function delete()
    {
        $this->save($data = array());
    }

    public static function getDefaultPathToProperties()
    {
        return PIWIK_INCLUDE_PATH . '/tmp/testEnvironmentOverrides.json';
    }

    private function handleQueryParamOverride()
    {
        foreach ($this->queryParamOverride as $key => $value) {
            $_GET[$key] = $value;
        }
    }

    private function handleGlobalsOverride()
    {
        foreach ($this->globalsOverride as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }

    private function handleHostOverride()
    {
        if (!empty($this->hostOverride)) {
            \Piwik\Url::setHost($this->hostOverride);
        }
    }

    private function handleUseXhprof()
    {
        if ($this->useXhprof) {
            \Piwik\Profiler::setupProfilerXHProf($mainRun = false, $setupDuringTracking = true);
        }
    }

    private function setUpPiwikEnvironmentForTests()
    {
        if (!defined('PIWIK_TEST_MODE')) {
            define('PIWIK_TEST_MODE', true);
        }

        // TODO: what is this for?
        \Piwik\Cache\Backend\File::$invalidateOpCacheBeforeRead = true;
    }

    private function addExtraHooks()
    {
        $self = $this;

        Piwik::addAction('Access.createAccessSingleton', function($access) use ($self) {
            if (!$self->testUseRegularAuth) {
                $access = new Piwik_MockAccess($access);
                \Piwik\Access::setSingletonInstance($access);
            }
        });

        Piwik::addAction('Request.dispatch', function() use ($self) {
            if (empty($_GET['ignoreClearAllViewDataTableParameters'])) { // TODO: should use testingEnvironment variable, not query param
                \Piwik\ViewDataTable\Manager::clearAllViewDataTableParameters();
            }

            if (!empty($self->optionsOverride)) {
                foreach ($self->optionsOverride as $name => $value) {
                    Option::set($name, $value);
                }
            }

            \Piwik\Plugins\CoreVisualizations\Visualizations\Cloud::$debugDisableShuffle = true;
            \Piwik\Visualization\Sparkline::$enableSparklineImages = false;
            \Piwik\Plugins\ExampleUI\API::$disableRandomness = true;
        });
        Piwik::addAction('AssetManager.getStylesheetFiles', function(&$stylesheets) {
            $stylesheets[] = 'tests/resources/screenshot-override/override.css';
        });
        Piwik::addAction('AssetManager.getJavaScriptFiles', function(&$jsFiles) {
            $jsFiles[] = 'tests/resources/screenshot-override/override.js';
        });
        self::addSendMailHook();
        Piwik::addAction('Updater.checkForUpdates', function () {
            try {
                @\Piwik\Filesystem::deleteAllCacheOnUpdate();
            } catch (Exception $ex) {
                // pass
            }
        });
        Piwik::addAction('Platform.initialized', function () use ($self) {
            $self->executeSetupTestEnvHook();

            static $archivingTablesDeleted = false;

            if ($self->deleteArchiveTables
                && !$archivingTablesDeleted
            ) {
                $archivingTablesDeleted = true;
                DbHelper::deleteArchiveTables();
            }
        });
    }

    /**
     * for plugins that need to inject special testing logic
     */
    public function executeSetupTestEnvHook()
    {
        Piwik::postEvent("TestingEnvironment.addHooks", array($this), $pending = true);
    }

    public static function addSendMailHook()
    {
        Piwik::addAction('Test.Mail.send', function($mail) {
            $outputFile = PIWIK_INCLUDE_PATH . '/tmp/' . Common::getRequestVar('module', '') . '.' . Common::getRequestVar('action', '') . '.mail.json';

            $outputContent = str_replace("=\n", "", $mail->getBodyText($textOnly = true));
            $outputContent = str_replace("=0A", "\n", $outputContent);
            $outputContent = str_replace("=3D", "=", $outputContent);

            $outputContents = array(
                'from'     => $mail->getFrom(),
                'to'       => $mail->getRecipients(),
                'subject'  => $mail->getSubject(),
                'contents' => $outputContent
            );

            file_put_contents($outputFile, json_encode($outputContents));
        });
    }
}