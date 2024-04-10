<?php

declare(strict_types=1);

namespace lilthq\craftliltplugintests\integration\controllers;

use Codeception\Exception\ModuleException;
use Craft;
use craft\helpers\Db;
use IntegrationTester;
use LiltConnectorSDK\Model\SettingsResponse;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use lilthq\craftliltplugin\records\SettingRecord;
use lilthq\craftliltplugin\services\repositories\SettingsRepository;
use lilthq\craftliltplugintests\integration\AbstractIntegrationCest;
use PHPUnit\Framework\Assert;

class PostConfigurationControllerCest extends AbstractIntegrationCest
{
    /**
     * @throws ModuleException
     */
    public function testSuccessOn(IntegrationTester $I): void
    {
        $I->amLoggedInAs(
            Craft::$app->getUsers()->getUserById(1)
        );

        $I->expectSettingsGetRequest(
            '/api/v1.0/this-is-connector-api-url/settings',
            'this-is-connector-api-key',
            [],
            200
        );

        $I->expectSettingsUpdateRequest(
            '/api/v1.0/this-is-connector-api-url/settings',
            [
                'project_prefix' => 'this-is-connector-project-prefix',
                'project_name_template' => 'this-is-connector-project-name-template',
                'lilt_translation_workflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT,
            ],
            200
        );

        $I->sendAjaxPostRequest(
            sprintf(
                '?p=admin/%s',
                CraftliltpluginParameters::POST_CONFIGURATION_PATH
            ),
            [
                'connectorApiKey' => 'this-is-connector-api-key',
                'connectorApiUrl' => 'http://wiremock/api/v1.0/this-is-connector-api-url',
                'projectPrefix' => 'this-is-connector-project-prefix',
                'projectNameTemplate' => 'this-is-connector-project-name-template',
                'liltTranslationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT,
                'enableEntriesForTargetSites' => true,
                'copyEntriesSlugFromSourceToTarget' => true,
                'queueEachTranslationFileSeparately' => true,
                'queueDisableAutomaticSync' => true,
            ]
        );

        $connectorApiKeyRecord = SettingRecord::findOne(['name' => 'connector_api_key']);
        $connectorApiUrlRecord = SettingRecord::findOne(['name' => 'connector_api_url']);
        $queueEachTranslationFileSeparately = SettingRecord::findOne(['name' => SettingsRepository::QUEUE_EACH_TRANSLATION_FILE_SEPARATELY]);
        $enableEntriesForTargetSites = SettingRecord::findOne(['name' => SettingsRepository::ENABLE_ENTRIES_FOR_TARGET_SITES]);
        $copyEntriesSlugFromSourceToTarget = SettingRecord::findOne(['name' => SettingsRepository::COPY_ENTRIES_SLUG_FROM_SOURCE_TO_TARGET]);
        $queueDisableAutomaticSync = SettingRecord::findOne(['name' => SettingsRepository::QUEUE_DISABLE_AUTOMATIC_SYNC]);

        Assert::assertSame('this-is-connector-api-key', $connectorApiKeyRecord->value);
        Assert::assertSame('http://wiremock/api/v1.0/this-is-connector-api-url', $connectorApiUrlRecord->value);
        Assert::assertSame('1', $queueEachTranslationFileSeparately->value);
        Assert::assertSame('1', $enableEntriesForTargetSites->value);
        Assert::assertSame('1', $copyEntriesSlugFromSourceToTarget->value);
        Assert::assertSame('1', $queueDisableAutomaticSync->value);
    }

    /**
     * @throws ModuleException
     */
    public function testSuccessOff(IntegrationTester $I): void
    {
        $I->amLoggedInAs(
            Craft::$app->getUsers()->getUserById(1)
        );

        $I->expectSettingsGetRequest(
            '/api/v1.0/this-is-connector-api-url/settings',
            'this-is-connector-api-key',
            [],
            200
        );

        $I->expectSettingsUpdateRequest(
            '/api/v1.0/this-is-connector-api-url/settings',
            [
                'project_prefix' => 'this-is-connector-project-prefix',
                'project_name_template' => 'this-is-connector-project-name-template',
                'lilt_translation_workflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT,
            ],
            200
        );

        $I->sendAjaxPostRequest(
            sprintf(
                '?p=admin/%s',
                CraftliltpluginParameters::POST_CONFIGURATION_PATH
            ),
            [
                'connectorApiKey' => 'this-is-connector-api-key',
                'connectorApiUrl' => 'http://wiremock/api/v1.0/this-is-connector-api-url',
                'projectPrefix' => 'this-is-connector-project-prefix',
                'projectNameTemplate' => 'this-is-connector-project-name-template',
                'liltTranslationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT,
                'enableEntriesForTargetSites' => false,
                'copyEntriesSlugFromSourceToTarget' => false,
                'queueEachTranslationFileSeparately' => false,
                'queueDisableAutomaticSync' => false,
            ]
        );

        $connectorApiKeyRecord = SettingRecord::findOne(['name' => 'connector_api_key']);
        $connectorApiUrlRecord = SettingRecord::findOne(['name' => 'connector_api_url']);
        $queueEachTranslationFileSeparately = SettingRecord::findOne(['name' => SettingsRepository::QUEUE_EACH_TRANSLATION_FILE_SEPARATELY]);
        $enableEntriesForTargetSites = SettingRecord::findOne(['name' => SettingsRepository::ENABLE_ENTRIES_FOR_TARGET_SITES]);
        $copyEntriesSlugFromSourceToTarget = SettingRecord::findOne(['name' => SettingsRepository::COPY_ENTRIES_SLUG_FROM_SOURCE_TO_TARGET]);
        $queueDisableAutomaticSync = SettingRecord::findOne(['name' => SettingsRepository::QUEUE_DISABLE_AUTOMATIC_SYNC]);

        Assert::assertSame('this-is-connector-api-key', $connectorApiKeyRecord->value);
        Assert::assertSame('http://wiremock/api/v1.0/this-is-connector-api-url', $connectorApiUrlRecord->value);
        Assert::assertSame('', $enableEntriesForTargetSites->value);
        Assert::assertSame('', $copyEntriesSlugFromSourceToTarget->value);
        Assert::assertSame('0', $queueEachTranslationFileSeparately->value);
        Assert::assertSame('0', $queueDisableAutomaticSync->value);
    }

    public function _after(IntegrationTester $I): void
    {
        parent::_after($I);

        Db::truncateTable(CraftliltpluginParameters::SETTINGS_TABLE_NAME);
    }
}
