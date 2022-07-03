<?php

/**
 * craft-lilt-plugin plugin for Craft CMS 3.x
 *
 * The Lilt plugin makes it easy for you to send content to Lilt for translation right from within Craft CMS.
 *
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin;

use Craft;
use craft\base\Plugin;
use craft\controllers\EntriesController;
use craft\helpers\UrlHelper;
use LiltConnectorSDK\Api\JobsApi;
use LiltConnectorSDK\Api\SettingsApi;
use LiltConnectorSDK\Api\TranslationsApi;
use LiltConnectorSDK\Configuration;
use lilthq\craftliltplugin\assets\CraftLiltPluginAsset;
use lilthq\craftliltplugin\assets\EditEntryAsset;
use lilthq\craftliltplugin\assets\JobsAsset;
use lilthq\craftliltplugin\models\TranslationModel;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use lilthq\craftliltplugin\records\SettingRecord;
use lilthq\craftliltplugin\services\appliers\ElementTranslatableContentApplier;
use lilthq\craftliltplugin\services\appliers\field\FieldContentApplier;
use lilthq\craftliltplugin\services\handlers\CreateJobHandler;
use lilthq\craftliltplugin\services\handlers\CreateTranslationsHandler;
use lilthq\craftliltplugin\services\handlers\EditJobHandler;
use lilthq\craftliltplugin\services\handlers\LoadI18NHandler;
use lilthq\craftliltplugin\services\handlers\PublishDraftHandler;
use lilthq\craftliltplugin\services\handlers\RefreshJobStatusHandler;
use lilthq\craftliltplugin\services\handlers\SendJobToLiltConnectorHandler;
use lilthq\craftliltplugin\services\handlers\SyncJobFromLiltConnectorHandler;
use lilthq\craftliltplugin\services\handlers\TranslationFailedHandler;
use lilthq\craftliltplugin\services\listeners\ListenerRegister;
use lilthq\craftliltplugin\services\mappers\LanguageMapper;
use lilthq\craftliltplugin\services\providers\ConnectorConfigurationProvider;
use lilthq\craftliltplugin\services\providers\ElementTranslatableContentProvider;
use lilthq\craftliltplugin\services\providers\field\FieldContentProvider;
use lilthq\craftliltplugin\services\repositories\external\ConnectorJobFileRepository;
use lilthq\craftliltplugin\services\repositories\external\ConnectorJobRepository;
use lilthq\craftliltplugin\services\repositories\external\ConnectorTranslationRepository;
use lilthq\craftliltplugin\services\repositories\I18NRepository;
use lilthq\craftliltplugin\services\repositories\JobLogsRepository;
use lilthq\craftliltplugin\services\repositories\JobRepository;
use lilthq\craftliltplugin\services\repositories\TranslationRepository;
use lilthq\craftliltplugin\services\ServiceInitializer;
use yii\base\ActionEvent;
use yii\base\Controller;
use yii\base\Event;
use yii\base\InvalidConfigException;
use yii\web\Response;

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
 * @property SettingsApi $connectorSettingsApi
 * @property LanguageMapper $languageMapper
 * @property ElementTranslatableContentProvider $elementTranslatableContentProvider
 * @property FieldContentProvider $fieldContentProvider
 * @property ElementTranslatableContentApplier $elementTranslatableContentApplier
 * @property FieldContentApplier $fieldContentApplier
 * @property I18NRepository $i18NRepository
 * @property LoadI18NHandler $loadI18NHandler
 * @property ListenerRegister $listenerRegister
 * @property JobLogsRepository $jobLogsRepository
 * @property TranslationFailedHandler $translationFailedHandler
 * @property CreateTranslationsHandler $createTranslationsHandler
 * @property RefreshJobStatusHandler $refreshJobStatusHandler
 * @property ServiceInitializer $serviceInitializer
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

    public function getSettingsResponse(): Response
    {
        return \Craft::$app
            ->controller
            ->redirect('craft-lilt-plugin/settings/lilt-configuration');
    }

    /*
        protected function createSettingsModel()
        {
            return new \mynamespace\models\Settings();
        }

        protected function settingsHtml()
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

    protected function afterInstall(): void
    {
        parent::afterInstall();

        $request = Craft::$app->getRequest();
        if (!$request->isCpRequest) {
            return;
        }

        Craft::$app
            ->getResponse()
            ->redirect(
                UrlHelper::cpUrl(
                    'craft-lilt-plugin/settings/lilt-system-report'
                )
            )
            ->send();
    }

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

        self::$plugin = $this;

        $connectorKey = getenv('CRAFT_LILT_PLUGIN_CONNECTOR_API_KEY');

        $tableSchema = Craft::$app->db->schema->getTableSchema(CraftliltpluginParameters::SETTINGS_TABLE_NAME);
        if (empty($connectorKey) && $tableSchema !== null) {
            $connectorApiKeyRecord = SettingRecord::findOne(['name' => 'connector_api_key']);
            if ($connectorApiKeyRecord) {
                $connectorKey = $connectorApiKeyRecord->value;
            }
        }

        if ($connectorKey) {
            $this->connectorKey = $connectorKey;
        }

        Craft::$app->getView()->registerAssetBundle(CraftLiltPluginAsset::class);

        $this->setComponents([
            'serviceInitializer' => ServiceInitializer::class
        ]);
        $this->serviceInitializer->run();

        Craft::info(
            Craft::t(
                'craft-lilt-plugin',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );

        Event::on(
            EntriesController::class,
            Controller::EVENT_BEFORE_ACTION,
            static function (ActionEvent $event) {
                if ($event->action->id !== 'edit-entry') {
                    return $event;
                }

                $params = Craft::$app->request->resolve();
                if (!isset($params[1]['entryId'])) {
                    return $event;
                }

                $entryId = (int)$params[1]['entryId'];

                $translations = Craftliltplugin::getInstance()->translationRepository->findInProgressByElementId(
                    $entryId
                );

                if (!empty($translations)) {
                    Craft::$app->getView()->registerAssetBundle(EditEntryAsset::class);

                    $jobIds = array_map(static function (TranslationModel $translation) {
                        return $translation->jobId;
                    }, $translations);

                    Craft::$app->view->registerJs(
                        sprintf(
                            'new CraftliltPlugin.EntryEditWarning(%s);',
                            json_encode([
                                'translationInProgress' => true,
                                'entry' => $entryId,
                                'jobs' => array_unique($jobIds)
                            ], 4194304)
                        )
                    );
                }

                return $event;
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function getCpNavItem(): array
    {
        $navItem = parent::getCpNavItem();
        $navItem['subnav'] = [
            'jobs' => [
                'label' => 'Jobs',
                'url' => 'craft-lilt-plugin/jobs',
            ],
            'settings' => [
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

    public static function getInstance(): Craftliltplugin
    {
        return parent::getInstance();
    }
}
