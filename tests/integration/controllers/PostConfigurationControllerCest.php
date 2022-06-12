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
use lilthq\craftliltplugintests\integration\AbstractIntegrationCest;
use PHPUnit\Framework\Assert;

class PostConfigurationControllerCest extends AbstractIntegrationCest
{
    /**
     * @throws ModuleException
     */
    public function testSuccess(IntegrationTester $I): void
    {
        $I->amLoggedInAs(
            Craft::$app->getUsers()->getUserById(1)
        );

        $I->expectSettingsUpdateRequest(
            '/api/v1.0/settings',
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
                'connectorApiUrl' => 'this-is-connector-api-url',
                'projectPrefix' => 'this-is-connector-project-prefix',
                'projectNameTemplate' => 'this-is-connector-project-name-template',
                'liltTranslationWorkflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT,
            ]
        );

        $connectorApiKeyRecord = SettingRecord::findOne(['name' => 'connector_api_key']);
        $connectorApiUrlRecord = SettingRecord::findOne(['name' => 'connector_api_url']);

        Assert::assertSame('this-is-connector-api-key', $connectorApiKeyRecord->value);
        Assert::assertSame('this-is-connector-api-url', $connectorApiUrlRecord->value);
    }

    public function _after(IntegrationTester $I): void
    {
        parent::_after($I);

        Db::truncateTable(CraftliltpluginParameters::SETTINGS_TABLE_NAME);
    }
}
