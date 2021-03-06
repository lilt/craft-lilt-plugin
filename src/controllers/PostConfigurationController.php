<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\controllers;

use Craft;
use craft\helpers\UrlHelper;
use lilthq\craftliltplugin\controllers\job\AbstractJobController;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\records\SettingRecord;
use lilthq\craftliltplugin\utilities\Configuration;
use Throwable;
use yii\web\Response;
use LiltConnectorSDK\Model\SettingsResponse1 as SettingsRequest;

class PostConfigurationController extends AbstractJobController
{
    protected $allowAnonymous = false;

    /**
     * @throws Throwable
     */
    public function actionInvoke(): Response
    {
        $request = $this->request;

        $connectorApiKey = $request->getBodyParam('connectorApiKey');
        $connectorApiUrl = $request->getBodyParam('connectorApiUrl');

        # connectorApiKey
        $connectorApiKeyRecord = SettingRecord::findOne(['name' => 'connector_api_key']);
        if (!$connectorApiKeyRecord) {
            $connectorApiKeyRecord = new SettingRecord(['name' => 'connector_api_key']);
        }

        $connectorApiKeyRecord->value = $request->getBodyParam('connectorApiKey');
        $connectorApiKeyRecord->save();

        # connectorApiUrl
        $connectorApiUrlRecord = SettingRecord::findOne(['name' => 'connector_api_url']);
        if (!$connectorApiUrlRecord) {
            $connectorApiUrlRecord = new SettingRecord(['name' => 'connector_api_url']);
        }
        $connectorApiUrlRecord->value = $request->getBodyParam('connectorApiUrl');
        $connectorApiUrlRecord->save();

        $liltConfigDisabled = true;
        if (!empty($connectorApiKey) && !empty($connectorApiUrl)) {
            //is token valid

            $settingsResult = null;

            Craftliltplugin::getInstance()->connectorConfiguration->setAccessToken($connectorApiKey);
            Craftliltplugin::getInstance()->connectorConfiguration->setHost($connectorApiUrl);

            try {
                $settingsResult = Craftliltplugin::getInstance()
                    ->connectorSettingsApi
                    ->servicesApiSettingsGetSettings();

                $liltConfigDisabled = false;
            } catch (\Exception $ex) {
                Craft::$app->getSession()->setFlash(
                    'cp-error',
                    'Cant connect to Lilt. Looks like API Key or API URL is wrong'
                );
            }
        }

        if (!$liltConfigDisabled) {
            $settingsRequest = new SettingsRequest();
            $settingsRequest->setProjectPrefix(
                $request->getBodyParam('projectPrefix')
            );
            $settingsRequest->setProjectNameTemplate(
                $request->getBodyParam('projectNameTemplate')
            );
            $settingsRequest->setLiltTranslationWorkflow(
                $request->getBodyParam('liltTranslationWorkflow')
            );
            Craftliltplugin::getInstance()->connectorSettingsApi->servicesApiSettingsUpdateSettings(
                $settingsRequest
            );

            Craft::$app->getSession()->setFlash(
                'cp-notice',
                'Configuration options saved successfully'
            );
        }

        return $this->redirect(
            UrlHelper::cpUrl(sprintf('craft-lilt-plugin/settings/%s', Configuration::id()))
        );
    }
}
