<?php

declare(strict_types=1);

namespace lilthq\craftliltplugintests\functional\controllers;

use Codeception\Exception\ModuleException;
use Craft;
use craft\helpers\Db;
use FunctionalTester;
use LiltConnectorSDK\Model\SettingsResponse;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use lilthq\craftliltplugin\records\SettingRecord;
use WireMock\Client\WireMock;
use yii\db\Exception;

class GetSettingsFormControllerCest
{
    /**
     * @throws ModuleException
     */
    public function testSuccess(FunctionalTester $I): void
    {
        $user = Craft::$app->getUsers()->getUserById(1);
        $I->amLoggedInAs($user);

        $I->expectSettingsGetRequest(
            '/api/v1.0/this-is-connector-api-url/settings',
            'SECURE_API_KEY_FOR_LILT_CONNECTOR',
            [
                'project_prefix' => 'this-is-connector-project-prefix',
                'project_name_template' => 'this-is-connector-project-name-template',
                'lilt_translation_workflow' => SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT,
            ],
            200
        );

        # connectorApiKey
        $connectorApiKeyRecord = new SettingRecord(['name' => 'connector_api_key']);
        $connectorApiKeyRecord->value = 'this-is-connector-api-key';
        $connectorApiKeyRecord->save();

        # connectorApiUrl
        $connectorApiUrlRecord = new SettingRecord(['name' => 'connector_api_url']);
        $connectorApiUrlRecord->value = 'http://wiremock/api/v1.0/this-is-connector-api-url';
        $connectorApiUrlRecord->save();

        $I->amOnPage('?p=admin/craft-lilt-plugin/settings');

        $I->seeElement('input#connectorApiKey', ['value' => 'this-is-connector-api-key']);
        $I->seeElement('input#connectorApiUrl', ['value' => 'http://wiremock/api/v1.0/this-is-connector-api-url']);
        $I->seeElement('input#projectPrefix', ['value' => 'this-is-connector-project-prefix']);
        $I->seeElement('select#liltTranslationWorkflow option:selected', ['value' => 'INSTANT']);
        $I->seeElement('select#liltTranslationWorkflow option:not(:selected)', ['value' => 'VERIFIED']);
    }

    /**
     * @throws Exception
     */
    public function _after(FunctionalTester $I): void
    {
        Db::truncateTable(CraftliltpluginParameters::SETTINGS_TABLE_NAME);
        $I->expectAllRequestsAreMatched();
    }
    public function _before(FunctionalTester $I): void {
        WireMock::create('wiremock', 80)->reset();
    }
}
