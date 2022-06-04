<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\utilities;

use Craft;
use craft\base\Utility;
use craft\helpers\UrlHelper;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\records\SettingRecord;

class Configuration extends Utility
{
    public static function displayName(): string
    {
        return Craft::t('app', 'Configuration');
    }

    public static function iconPath()
    {
        return Craft::getAlias('@appicons/tools.svg');
    }

    public static function id(): string
    {
        return 'lilt-configuration';
    }

    public static function contentHtml(): string
    {
        //TODO: move to service settings logic

        $settingsResult = Craftliltplugin::getInstance()->connectorSettingsApi->servicesApiSettingsGetSettings();


        $workflowAllowableValues = $settingsResult->getLiltTranslationWorkflowAllowableValues();
        $workflowAllowableOptions = [];
        foreach ($workflowAllowableValues as $workflowAllowableValue) {
            $label = ucfirst(strtolower($workflowAllowableValue));
            $workflowAllowableOptions[$workflowAllowableValue] = $label;
        }

        $connectorApiKeyRecord = SettingRecord::findOne(['name' => 'connector_api_key']);
        $connectorApiKey = $connectorApiKeyRecord->value ?? getenv('CRAFT_LILT_PLUGIN_CONNECTOR_API_KEY');

        $connectorApiUrlRecord = SettingRecord::findOne(['name' => 'connector_api_url']);
        $connectorApiUrl = $connectorApiUrlRecord->value
            ?? \LiltConnectorSDK\Configuration::getDefaultConfiguration()->getHost();

        return Craft::$app->getView()->renderTemplate(
            'craft-lilt-plugin/_components/utilities/configuration.twig',
            [
                'projectPrefix' => $settingsResult->getProjectPrefix(),
                'projectNameTemplate' => $settingsResult->getProjectNameTemplate(),
                'liltTranslationWorkflow' => $settingsResult->getLiltTranslationWorkflow(),
                'liltTranslationWorkflowAllowableValues' => $workflowAllowableOptions,
                'connectorApiKey' => $connectorApiKey,
                'connectorApiUrl' => $connectorApiUrl,
                'formActionUrl' => UrlHelper::cpUrl('craft-lilt-plugin/settings/lilt-configuration'),
            ]
        );
    }
}
