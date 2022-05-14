<?php

/**
 * craft-lilt-plugin plugin for Craft CMS 3.x
 *
 * The Lilt plugin makes it easy for you to send content to Lilt for translation right from within Craft CMS.
 *
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

namespace lilthq\craftliltplugin;

use Craft;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use GuzzleHttp\Client;
use LiltConnectorSDK\Api\JobsApi;
use LiltConnectorSDK\Api\TranslationsApi;
use LiltConnectorSDK\Configuration;
use lilthq\craftliltplugin\assets\CraftLiltPluginAsset;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use lilthq\craftliltplugin\services\job\CreateJobHandler;
use lilthq\craftliltplugin\services\job\EditJobHandler;
use lilthq\craftliltplugin\services\job\lilt\SendJobToLiltConnectorHandler;
use lilthq\craftliltplugin\services\job\lilt\SyncJobFromLiltConnectorHandler;
use lilthq\craftliltplugin\services\mappers\LanguageMapper;
use lilthq\craftliltplugin\services\providers\ElementTranslatableContentProvider;
use lilthq\craftliltplugin\services\providers\ExpandedContentProvider;
use lilthq\craftliltplugin\services\providers\LiltConfigurationProvider;
use lilthq\craftliltplugin\services\repositories\external\LiltFileRepository;
use lilthq\craftliltplugin\services\repositories\external\LiltJobRepository;
use lilthq\craftliltplugin\services\repositories\external\LiltTranslationRepository;
use lilthq\craftliltplugin\services\repositories\JobRepository;
use yii\base\Event;
use yii\base\InvalidConfigException;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Elements;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://docs.craftcms.com/v3/extend/
 *
 * @author    Lilt Devs
 * @package   Craftliltplugin
 * @since     1.0.0
 *
 * @property LiltJobRepository $liltJobRepository
 * @property LiltTranslationRepository $liltTranslationRepository
 * @property JobRepository $jobRepository
 * @property LiltConfigurationProvider $liltConfigurationProvider
 * @property LiltFileRepository $liltJobsFileRepository
 * @property CreateJobHandler $createJobHandler
 * @property EditJobHandler $editJobHandler
 * @property SendJobToLiltConnectorHandler $sendJobToLiltConnectorHandler
 * @property SyncJobFromLiltConnectorHandler $syncJobFromLiltConnectorHandler
 * @property Configuration $liltApiConfig
 * @property JobsApi $liltJobsApi
 * @property TranslationsApi $liltTranslationsApi
 * @property LanguageMapper $languageMapper
 * @property ElementTranslatableContentProvider $elementTranslatableContentProvider
 * @property ExpandedContentProvider $expandedContentProvider
 */
class Craftliltplugin extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * Craftliltplugin::$plugin
     *
     * @var Craftliltplugin
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public $schemaVersion = '1.0.0';

    /**
     * Set to `true` if the plugin should have a settings view in the control panel.
     *
     * @var bool
     */
    public $hasCpSettings = false;

    /**
     * Set to `true` if the plugin should have its own section (main nav item) in the control panel.
     *
     * @var bool
     */
    public $hasCpSection = true;

    /**
     * @var string|null
     */
    private $connectorKey = null;

    // Public Methods
    // =========================================================================

    /**
     * Set our $plugin static property to this class so that it can be accessed via
     * Craftliltplugin::$plugin
     *
     * Called after the plugin class is instantiated; do any one-time initialization
     * here such as hooks and events.
     *
     * If you have a '/vendor/autoload.php' file, it will be loaded for you automatically;
     * you do not need to load it in your init() method.
     *
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->connectorKey = getenv('CRAFT_LILT_PLUGIN_CONNECTOR_API_KEY');

        Craft::$app->getView()->registerAssetBundle(CraftLiltPluginAsset::class);
        $this->loadComponents();

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['POST ' . CraftliltpluginParameters::JOB_CREATE_PATH] = 'craft-lilt-plugin/job/post-create-job/invoke';
                $event->rules['GET craft-lilt-plugin/job/create'] = 'craft-lilt-plugin/job/get-job-create-form/invoke';
                $event->rules['GET ' . CraftliltpluginParameters::JOB_EDIT_PATH . '/<jobId:\d+>'] = 'craft-lilt-plugin/job/get-job-edit-form/invoke';
                $event->rules['POST ' . CraftliltpluginParameters::JOB_EDIT_PATH . '/<jobId:\d+>'] = 'craft-lilt-plugin/job/post-edit-job/invoke';
                $event->rules['GET ' . CraftliltpluginParameters::JOB_SEND_TO_LILT_PATH . '/<jobId:\d+>'] = 'craft-lilt-plugin/job/get-send-to-lilt/invoke';
                $event->rules['GET ' . CraftliltpluginParameters::JOB_SYNC_FROM_LILT_PATH . '/<jobId:\d+>'] = 'craft-lilt-plugin/job/get-sync-from-lilt/invoke';
                $event->rules['GET craft-lilt-plugin'] = 'craft-lilt-plugin/index/index';
            }
        );

        Event::on(
            Elements::class,
            Elements::EVENT_REGISTER_ELEMENT_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = Job::class;
            }
        );

        Craft::info(
            Craft::t(
                'craft-lilt-plugin',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    /**
     * @inheritdoc
     */
    public function getCpNavItem()
    {
        $navItem = parent::getCpNavItem();
        $navItem['subnav'] = [
            [
                'label' => 'Jobs',
                'url' => 'craft-lilt-plugin/jobs',
            ],
            [
                'label' => 'Settings',
                'url' => 'craft-lilt-plugin/settings',
            ]
        ];

        return $navItem;
    }

    /**
     * @return string|null
     */
    public function getConnectorKey(): ?string
    {
        return $this->connectorKey;
    }

    /**
     * @throws InvalidConfigException
     */
    private function loadComponents(): void
    {
        $this->setComponents([
            'createJobHandler' => CreateJobHandler::class,
            'sendJobToLiltConnectorHandler' => SendJobToLiltConnectorHandler::class,
            'syncJobFromLiltConnectorHandler' => SyncJobFromLiltConnectorHandler::class,
            'liltConfigurationProvider' => LiltConfigurationProvider::class,
            'elementTranslatableContentProvider' => ElementTranslatableContentProvider::class,
            'expandedContentProvider' => ExpandedContentProvider::class,
            'languageMapper' => LanguageMapper::class,
            'jobRepository' => JobRepository::class,
        ]);

        $this->set(
            'liltApiConfig',
            $this->liltConfigurationProvider->provide()
        );

        $this->set(
            'liltJobsApi',
            function () {
                return new JobsApi(
                    new Client(),
                    $this->liltApiConfig
                );
            }
        );

        $this->set(
            'liltTranslationsApi',
            function () {
                return new TranslationsApi(
                    new Client(),
                    $this->liltApiConfig
                );
            }
        );

        //TODO: proper naming
        $this->set(
            'liltJobRepository',
            [
                'class' => LiltJobRepository::class,
                'apiInstance' => $this->liltJobsApi,
            ]
        );

        $this->set(
            'liltTranslationRepository',
            [
                'class' => LiltTranslationRepository::class,
                'apiInstance' => $this->liltTranslationsApi,
            ]
        );

        $this->set(
            'liltJobsFileRepository',
            [
                'class' => LiltFileRepository::class,
                'apiInstance' => $this->liltJobsApi,
            ]
        );

        $this->set(
            'editJobHandler',
            [
                'class' => EditJobHandler::class,
                'jobRepository' => $this->jobRepository,
            ]
        );
    }
}
