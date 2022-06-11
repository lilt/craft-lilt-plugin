<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services;

use Craft;
use GuzzleHttp\Client;
use LiltConnectorSDK\Api\JobsApi;
use LiltConnectorSDK\Api\SettingsApi;
use LiltConnectorSDK\Api\TranslationsApi;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use lilthq\craftliltplugin\services\appliers\ElementTranslatableContentApplier;
use lilthq\craftliltplugin\services\appliers\field\BaseOptionFieldContentApplier;
use lilthq\craftliltplugin\services\appliers\field\ElementQueryContentApplier;
use lilthq\craftliltplugin\services\appliers\field\FieldContentApplier;
use lilthq\craftliltplugin\services\appliers\field\LightswitchContentApplier;
use lilthq\craftliltplugin\services\appliers\field\PlainTextContentApplier;
use lilthq\craftliltplugin\services\appliers\field\RedactorPluginFieldContentApplier;
use lilthq\craftliltplugin\services\appliers\field\TableContentApplier;
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
use lilthq\craftliltplugin\services\providers\field\BaseOptionFieldContentProvider;
use lilthq\craftliltplugin\services\providers\field\ElementQueryContentProvider;
use lilthq\craftliltplugin\services\providers\field\FieldContentProvider;
use lilthq\craftliltplugin\services\providers\field\LightswitchContentProvider;
use lilthq\craftliltplugin\services\providers\field\PlainTextContentProvider;
use lilthq\craftliltplugin\services\providers\field\RedactorPluginFieldContentProvider;
use lilthq\craftliltplugin\services\providers\field\TableContentProvider;
use lilthq\craftliltplugin\services\repositories\external\ConnectorJobFileRepository;
use lilthq\craftliltplugin\services\repositories\external\ConnectorJobRepository;
use lilthq\craftliltplugin\services\repositories\external\ConnectorTranslationRepository;
use lilthq\craftliltplugin\services\repositories\I18NRepository;
use lilthq\craftliltplugin\services\repositories\JobLogsRepository;
use lilthq\craftliltplugin\services\repositories\JobRepository;
use lilthq\craftliltplugin\services\repositories\TranslationRepository;
use yii\base\InvalidConfigException;

class ServiceInitializer
{
    /**
     * @throws InvalidConfigException
     */
    public function run(): void
    {
        $pluginInstance = Craftliltplugin::getInstance();

        $pluginInstance->setComponents([
            'createJobHandler' => CreateJobHandler::class,
            'sendJobToLiltConnectorHandler' => SendJobToLiltConnectorHandler::class,
            'syncJobFromLiltConnectorHandler' => SyncJobFromLiltConnectorHandler::class,
            'connectorConfigurationProvider' => ConnectorConfigurationProvider::class,
            'elementTranslatableContentProvider' => ElementTranslatableContentProvider::class,
            'languageMapper' => LanguageMapper::class,
            'jobRepository' => JobRepository::class,
            'translationRepository' => TranslationRepository::class,
            'i18NRepository' => I18NRepository::class,
            'jobLogsRepository' => JobLogsRepository::class,
            'translationFailedHandler' => TranslationFailedHandler::class,
            'createTranslationsHandler' => CreateTranslationsHandler::class,
            'refreshJobStatusHandler' => RefreshJobStatusHandler::class,
            'listenerRegister' => [
                'class' => ListenerRegister::class,
                'availableListeners' => CraftliltpluginParameters::LISTENERS,
            ],
        ]);

        $pluginInstance->setComponents([
            'connectorConfiguration' => $pluginInstance->connectorConfigurationProvider->provide(),
        ]);

        $pluginInstance->setComponents([
            'connectorTranslationsApi' =>
                function () use ($pluginInstance) {
                    return new TranslationsApi(
                        new Client(),
                        $pluginInstance->connectorConfiguration
                    );
                },
            'connectorSettingsApi' =>
                function () use ($pluginInstance) {
                    return new SettingsApi(
                        new Client(),
                        $pluginInstance->connectorConfiguration
                    );
                },
            'loadI18NHandler' =>
                function () {
                    return new LoadI18NHandler(
                        Craft::$app->i18n
                    );
                }
        ]);

        $pluginInstance->setComponents([
            'connectorJobsApi' => function () use ($pluginInstance) {
                return new JobsApi(
                    new Client(),
                    $pluginInstance->connectorConfiguration
                );
            }
        ]);

        $getProvidersMap = static function () use ($pluginInstance) {
            return [
                CraftliltpluginParameters::CRAFT_FIELDS_PLAINTEXT => new PlainTextContentProvider(),
                CraftliltpluginParameters::CRAFT_REDACTOR_FIELD => new RedactorPluginFieldContentProvider(),
                CraftliltpluginParameters::CRAFT_FIELDS_TABLE => new TableContentProvider(),
                CraftliltpluginParameters::CRAFT_FIELDS_LIGHTSWITCH => new LightswitchContentProvider(),

                # Options
                CraftliltpluginParameters::CRAFT_FIELDS_RADIOBUTTONS => new BaseOptionFieldContentProvider(),
                CraftliltpluginParameters::CRAFT_FIELDS_DROPDOWN => new BaseOptionFieldContentProvider(),
                CraftliltpluginParameters::CRAFT_FIELDS_MULTISELECT => new BaseOptionFieldContentProvider(),
                CraftliltpluginParameters::CRAFT_FIELDS_CHECKBOXES => new BaseOptionFieldContentProvider(),

                ### ELEMENT QUERY PROVIDERS

                #Matrix
                CraftliltpluginParameters::CRAFT_FIELDS_MATRIX => new ElementQueryContentProvider(),

                #Neo Plugin
                CraftliltpluginParameters::BENF_NEO_FIELD => new ElementQueryContentProvider(),

                #SuperTable Plugin
                CraftliltpluginParameters::CRAFT_FIELDS_SUPER_TABLE => new ElementQueryContentProvider(),
            ];
        };

        $pluginInstance->set(
            'fieldContentProvider',
            [
                'class' => FieldContentProvider::class,
                'providersMap' => $getProvidersMap(),
                'fieldsTranslatableMap' => [
                    'craft\fields\Assets' => ['translatable' => false,],
                    'craft\fields\Categories' => ['translatable' => false,],
                    'craft\fields\Color' => ['translatable' => false,],
                    'craft\fields\Date' => ['translatable' => false,],
                    'craft\fields\Email' => ['translatable' => false,],
                    'craft\fields\Entries' => ['translatable' => true,],
                    'craft\fields\Lightswitch' => ['translatable' => true,],
                    'craft\fields\Number' => ['translatable' => false,],
                    'craft\fields\Tags' => ['translatable' => false,],
                    'craft\fields\Time' => ['translatable' => false,],
                    'craft\fields\Url' => ['translatable' => false,],
                    'craft\fields\Users' => ['translatable' => false,],
                ],
            ]
        );

        $getAppliersMap = static function () {
            return [
                CraftliltpluginParameters::CRAFT_FIELDS_PLAINTEXT => new PlainTextContentApplier(),
                CraftliltpluginParameters::CRAFT_REDACTOR_FIELD => new RedactorPluginFieldContentApplier(),
                CraftliltpluginParameters::CRAFT_FIELDS_TABLE => new TableContentApplier(),
                CraftliltpluginParameters::CRAFT_FIELDS_LIGHTSWITCH => new LightswitchContentApplier(),

                ### Options
                CraftliltpluginParameters::CRAFT_FIELDS_RADIOBUTTONS => new BaseOptionFieldContentApplier(),
                CraftliltpluginParameters::CRAFT_FIELDS_DROPDOWN => new BaseOptionFieldContentApplier(),
                CraftliltpluginParameters::CRAFT_FIELDS_MULTISELECT => new BaseOptionFieldContentApplier(),
                CraftliltpluginParameters::CRAFT_FIELDS_CHECKBOXES => new BaseOptionFieldContentApplier(),

                ### ELEMENT QUERY APPLIERS

                # Matrix
                CraftliltpluginParameters::CRAFT_FIELDS_MATRIX => new ElementQueryContentApplier(),

                #Neo Plugin
                CraftliltpluginParameters::BENF_NEO_FIELD => new ElementQueryContentApplier(),

                #SuperTable Plugin
                CraftliltpluginParameters::CRAFT_FIELDS_SUPER_TABLE => new ElementQueryContentApplier(),
            ];
        };

        $pluginInstance->set(
            'fieldContentApplier',
            [
                'class' => FieldContentApplier::class,
                'appliersMap' => $getAppliersMap(),
            ]
        );

        $pluginInstance->setComponents([
            'connectorJobRepository' =>
                [
                    'class' => ConnectorJobRepository::class,
                    'apiInstance' => $pluginInstance->connectorJobsApi,
                ],
            'publishDraftsHandler' =>
                [
                    'class' => PublishDraftHandler::class,
                    'draftRepository' => Craft::$app->getDrafts(),
                ],
            'connectorTranslationRepository' =>
                [
                    'class' => ConnectorTranslationRepository::class,
                    'apiInstance' => $pluginInstance->connectorTranslationsApi,
                ],
            'connectorJobsFileRepository' =>
                [
                    'class' => ConnectorJobFileRepository::class,
                    'apiInstance' => $pluginInstance->connectorJobsApi,
                ],
            'editJobHandler' =>
                [
                    'class' => EditJobHandler::class,
                    'jobRepository' => $pluginInstance->jobRepository,
                ],
            'elementTranslatableContentApplier' =>
                [
                    'class' => ElementTranslatableContentApplier::class,
                    'draftRepository' => Craft::$app->getDrafts(),
                    'fieldContentApplier' => $pluginInstance->fieldContentApplier,
                ],
        ]);

        $pluginInstance->listenerRegister->register();
        $pluginInstance->loadI18NHandler->__invoke();
    }
}
