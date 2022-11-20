<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\utilities;

use Craft;
use craft\base\Utility;
use craft\helpers\UrlHelper;
use Exception;
use LiltConnectorSDK\Model\SettingsResponse;
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
        $liltConfigDisabled = false;
        $settingsResult = null;

        try {
            $settingsResult = Craftliltplugin::getInstance()->connectorSettingsApi->servicesApiSettingsGetSettings();
        } catch (Exception $ex) {
            Craft::error([
                'message' => "Can't fetch setting from connector api!",
                'exception_message' => $ex->getMessage(),
                'exception_trace' => $ex->getTrace(),
                'exception' => $ex,
            ]);

            $liltConfigDisabled = true;
        }

        if (!$settingsResult) {
            $workflowAllowableValues = [
                SettingsResponse::LILT_TRANSLATION_WORKFLOW_INSTANT,
                SettingsResponse::LILT_TRANSLATION_WORKFLOW_VERIFIED,
            ];

            $projectPrefix = null;
            $projectNameTemplate = null;
            $liltTranslationWorkflow = null;
        } else {
            $workflowAllowableValues = $settingsResult->getLiltTranslationWorkflowAllowableValues();
            $projectPrefix = $settingsResult->getProjectPrefix();
            $projectNameTemplate = $settingsResult->getProjectNameTemplate();
            $liltTranslationWorkflow = $settingsResult->getLiltTranslationWorkflow();
        }

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

        $enableEntriesForTargetSitesRecord = SettingRecord::findOne(['name' => 'enable_entries_for_target_sites']);
        $enableEntriesForTargetSites = (bool) ($enableEntriesForTargetSitesRecord->value
            ?? false);

        return Craft::$app->getView()->renderTemplate(
            'craft-lilt-plugin/_components/utilities/configuration.twig',
            [
                'projectPrefix' => $projectPrefix,
                'projectNameTemplate' => $projectNameTemplate,
                'liltTranslationWorkflow' => $liltTranslationWorkflow,
                'liltTranslationWorkflowAllowableValues' => $workflowAllowableOptions,
                'connectorApiKey' => $connectorApiKey,
                'connectorApiUrl' => $connectorApiUrl,
                'formActionUrl' => UrlHelper::cpUrl('craft-lilt-plugin/settings/lilt-configuration'),
                'liltConfigDisabled' => $liltConfigDisabled,
                'enableEntriesForTargetSites' => $enableEntriesForTargetSites,
            ]
        );
    }
}
