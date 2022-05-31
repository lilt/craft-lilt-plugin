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

        $connectorApiKeyRecord = SettingRecord::findOne(['name' => 'connector_api_key']);
        if (!$connectorApiKeyRecord) {
            $connectorApiKeyRecord = new SettingRecord(['name' => 'connector_api_key']);
        }

        $connectorApiKeyRecord->value = $request->getBodyParam('connectorApiKey');
        $connectorApiKeyRecord->save();

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

        return $this->redirect(
            UrlHelper::cpUrl(sprintf('craft-lilt-plugin/settings/%s', Configuration::id()))
        );
    }
}
