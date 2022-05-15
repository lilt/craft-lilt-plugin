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

use benf\neo\Field;
use Craft;
use craft\base\Element;
use craft\base\Plugin;
use craft\events\RegisterElementDefaultTableAttributesEvent;
use craft\events\RegisterElementTableAttributesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use GuzzleHttp\Client;
use LiltConnectorSDK\Api\JobsApi;
use LiltConnectorSDK\Api\TranslationsApi;
use LiltConnectorSDK\Configuration;
use lilthq\craftliltplugin\assets\CraftLiltPluginAsset;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use lilthq\craftliltplugin\services\appliers\ElementTranslatableContentApplier;
use lilthq\craftliltplugin\services\appliers\field\BaseOptionFieldContentApplier;
use lilthq\craftliltplugin\services\appliers\field\FieldContentApplier;
use lilthq\craftliltplugin\services\appliers\field\MatrixFieldContentApplier;
use lilthq\craftliltplugin\services\appliers\field\NeoFieldContentApplier;
use lilthq\craftliltplugin\services\appliers\field\PlainTextContentApplier;
use lilthq\craftliltplugin\services\appliers\field\RedactorPluginFieldContentApplier;
use lilthq\craftliltplugin\services\appliers\field\TableContentApplier;
use lilthq\craftliltplugin\services\handlers\LoadI18NHandler;
use lilthq\craftliltplugin\services\handlers\PublishDraftHandler;
use lilthq\craftliltplugin\services\job\CreateJobHandler;
use lilthq\craftliltplugin\services\job\EditJobHandler;
use lilthq\craftliltplugin\services\job\lilt\SendJobToLiltConnectorHandler;
use lilthq\craftliltplugin\services\job\lilt\SyncJobFromLiltConnectorHandler;
use lilthq\craftliltplugin\services\mappers\LanguageMapper;
use lilthq\craftliltplugin\services\providers\ElementTranslatableContentProvider;
use lilthq\craftliltplugin\services\providers\ExpandedContentProvider;
use lilthq\craftliltplugin\services\providers\ConnectorConfigurationProvider;
use lilthq\craftliltplugin\services\providers\field\BaseOptionFieldContentProvider;
use lilthq\craftliltplugin\services\providers\field\FieldContentProvider;
use lilthq\craftliltplugin\services\providers\field\MatrixFieldContentProvider;
use lilthq\craftliltplugin\services\providers\field\NeoFieldContentProvider;
use lilthq\craftliltplugin\services\providers\field\PlainTextContentProvider;
use lilthq\craftliltplugin\services\providers\field\RedactorPluginFieldContentProvider;
use lilthq\craftliltplugin\services\providers\field\TableContentProvider;
use lilthq\craftliltplugin\services\repositories\external\ConnectorJobFileRepository;
use lilthq\craftliltplugin\services\repositories\external\ConnectorJobRepository;
use lilthq\craftliltplugin\services\repositories\external\ConnectorTranslationRepository;
use lilthq\craftliltplugin\services\repositories\I18NRepository;
use lilthq\craftliltplugin\services\repositories\JobRepository;
use lilthq\craftliltplugin\services\repositories\TranslationRepository;
use yii\base\Event;
use yii\base\InvalidConfigException;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Elements;
use craft\redactor\Field as RedactorPluginField;

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
 * @property ConnectorJobRepository $connectorJobRepository
 * @property ConnectorTranslationRepository $connectorTranslationRepository
 * @property ConnectorJobFileRepository $connectorJobsFileRepository
 * @property JobRepository $jobRepository
 * @property TranslationRepository $translationRepository
 * @property ConnectorConfigurationProvider $connectorConfigurationProvider
 * @property CreateJobHandler $createJobHandler
 * @property EditJobHandler $editJobHandler
 * @property SendJobToLiltConnectorHandler $sendJobToLiltConnectorHandler
 * @property SyncJobFromLiltConnectorHandler $syncJobFromLiltConnectorHandler
 * @property PublishDraftHandler $publishDraftsHandler
 * @property Configuration $connectorConfiguration
 * @property JobsApi $connectorJobsApi
 * @property TranslationsApi $connectorTranslationsApi
 * @property LanguageMapper $languageMapper
 * @property ElementTranslatableContentProvider $elementTranslatableContentProvider
 * @property FieldContentProvider $fieldContentProvider
 * @property ExpandedContentProvider $expandedContentProvider
 * @property ElementTranslatableContentApplier $elementTranslatableContentApplier
 * @property FieldContentApplier $fieldContentApplier
 * @property I18NRepository $i18NRepository
 * @property LoadI18NHandler $loadI18NHandler
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
    public $hasCpSettings = true;

    /*
    public function getSettingsResponse()
    {
        return \Craft::$app
            ->controller
            ->renderTemplate('my-plugin-handle/settings/template');
    }

    protected function createSettingsModel()
    {
        return new \mynamespace\models\Settings();
    }
        */
    /*protected function settingsHtml()
    {
        return \Craft::$app->getView()->renderTemplate(
            'craft-lilt-plugin/settings',
            [ 'settings' => $this->getSettings() ]
        );
    }*/

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
    public function init(): void
    {
        parent::init();

        //Queue::push(new FetchTranslationJob(['jobId' => 102]));

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
                $event->rules['GET craft-lilt-plugin/jobs'] = 'craft-lilt-plugin/jobs/index';
                $event->rules['craft-lilt-plugin'] = 'craft-lilt-plugin/job/get-translation-review/invoke';
            }
        );

        Event::on(
            Elements::class,
            Elements::EVENT_REGISTER_ELEMENT_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = Job::class;
            }
        );


        Event::on(
            Element::class,
            Element::EVENT_REGISTER_DEFAULT_TABLE_ATTRIBUTES,
            function (RegisterElementDefaultTableAttributesEvent $event) {
                $params = Craft::$app->getRequest()->getBodyParams();

                if ($params['elementType'] === 'lilthq\craftliltplugin\elements\TranslateEntry') {
                    $expiryDateKey = array_search('expiryDate', $event->tableAttributes);

                    if ($expiryDateKey !== false) {
                        unset($event->tableAttributes[$expiryDateKey]);
                    }

                    $event->tableAttributes = array_merge(['drafts'], $event->tableAttributes);
                }
            }
        );

        Event::on(
            Element::class,
            Element::EVENT_REGISTER_TABLE_ATTRIBUTES,
            function (RegisterElementTableAttributesEvent $event) {
                $params = Craft::$app->getRequest()->getBodyParams();

                if (!empty($params['elementType']) && isset($event->tableAttributes['drafts']['label']) && $params['elementType'] === 'lilthq\craftliltplugin\elements\TranslateEntry') {
                    $event->tableAttributes['drafts']['label'] = 'Version';
                }
            }
        );

        $this->loadI18NHandler->__invoke();

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
    public function getCpNavItem(): array
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
            'connectorConfigurationProvider' => ConnectorConfigurationProvider::class,
            'elementTranslatableContentProvider' => ElementTranslatableContentProvider::class,
            'expandedContentProvider' => ExpandedContentProvider::class,
            'languageMapper' => LanguageMapper::class,
            'jobRepository' => JobRepository::class,
            'translationRepository' => TranslationRepository::class,
            'i18NRepository' => I18NRepository::class,
        ]);

        $this->set(
            'connectorConfiguration',
            $this->connectorConfigurationProvider->provide()
        );

        $this->set(
            'connectorJobsApi',
            function () {
                return new JobsApi(
                    new Client(),
                    $this->connectorConfiguration
                );
            }
        );

        $this->set(
            'connectorTranslationsApi',
            function () {
                return new TranslationsApi(
                    new Client(),
                    $this->connectorConfiguration
                );
            }
        );

        $this->set(
            'connectorJobRepository',
            [
                'class' => ConnectorJobRepository::class,
                'apiInstance' => $this->connectorJobsApi,
            ]
        );

        $this->set(
            'publishDraftsHandler',
            [
                'class' => PublishDraftHandler::class,
                'draftRepository' => Craft::$app->getDrafts(),
            ]
        );

        $this->set(
            'connectorTranslationRepository',
            [
                'class' => ConnectorTranslationRepository::class,
                'apiInstance' => $this->connectorTranslationsApi,
            ]
        );

        $this->set(
            'connectorJobsFileRepository',
            [
                'class' => ConnectorJobFileRepository::class,
                'apiInstance' => $this->connectorJobsApi,
            ]
        );

        $this->set(
            'editJobHandler',
            [
                'class' => EditJobHandler::class,
                'jobRepository' => $this->jobRepository,
            ]
        );

        $getProvidersMap = function () {
            return [
                CraftliltpluginParameters::CRAFT_FIELDS_MATRIX => new MatrixFieldContentProvider($this->elementTranslatableContentProvider),
                CraftliltpluginParameters::CRAFT_FIELDS_PLAINTEXT => new PlainTextContentProvider(),
                CraftliltpluginParameters::CRAFT_REDACTOR_FIELD => new RedactorPluginFieldContentProvider(),
                CraftliltpluginParameters::CRAFT_FIELDS_TABLE => new TableContentProvider(),

                # Options
                CraftliltpluginParameters::CRAFT_FIELDS_RADIOBUTTONS => new BaseOptionFieldContentProvider(),
                CraftliltpluginParameters::CRAFT_FIELDS_DROPDOWN => new BaseOptionFieldContentProvider(),
                CraftliltpluginParameters::CRAFT_FIELDS_MULTISELECT => new BaseOptionFieldContentProvider(),
                CraftliltpluginParameters::CRAFT_FIELDS_CHECKBOXES => new BaseOptionFieldContentProvider(),

                #Neo Plugin
                CraftliltpluginParameters::BENF_NEO_FIELD => new NeoFieldContentProvider(),
            ];
        };

        $this->set(
            'fieldContentProvider',
            [
                'class' => FieldContentProvider::class,
                'providersMap' => $getProvidersMap(),
            ]
        );

        $getAppliersMap = static function () {
            return [
                CraftliltpluginParameters::CRAFT_FIELDS_MATRIX => new MatrixFieldContentApplier(),
                CraftliltpluginParameters::CRAFT_FIELDS_PLAINTEXT => new PlainTextContentApplier(),
                CraftliltpluginParameters::CRAFT_REDACTOR_FIELD => new RedactorPluginFieldContentApplier(),
                CraftliltpluginParameters::CRAFT_FIELDS_TABLE => new TableContentApplier(),

                # Options
                CraftliltpluginParameters::CRAFT_FIELDS_RADIOBUTTONS => new BaseOptionFieldContentApplier(),
                CraftliltpluginParameters::CRAFT_FIELDS_DROPDOWN => new BaseOptionFieldContentApplier(),
                CraftliltpluginParameters::CRAFT_FIELDS_MULTISELECT => new BaseOptionFieldContentApplier(),
                CraftliltpluginParameters::CRAFT_FIELDS_CHECKBOXES => new BaseOptionFieldContentApplier(),

                #Neo Plugin
                CraftliltpluginParameters::BENF_NEO_FIELD => new NeoFieldContentApplier(),
            ];
        };

        $this->set(
            'fieldContentApplier',
            [
                'class' => FieldContentApplier::class,
                'appliersMap' => $getAppliersMap(),
            ]
        );

        $this->set(
            'elementTranslatableContentApplier',
            [
                'class' => ElementTranslatableContentApplier::class,
                'draftRepository' => Craft::$app->getDrafts(),
                'fieldContentApplier' => $this->fieldContentApplier,
            ]
        );

        $this->set(
            'loadI18NHandler',
            function () {
                return new LoadI18NHandler(
                    Craft::$app->i18n
                );
            }
        );
    }
}
