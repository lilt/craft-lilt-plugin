<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\controllers;

use Craft;
use craft\helpers\UrlHelper;
use Exception;
use GuzzleHttp\Client;
use LiltConnectorSDK\Api\SettingsApi;
use lilthq\craftliltplugin\controllers\job\AbstractJobController;
use lilthq\craftliltplugin\Craftliltplugin;
use LiltConnectorSDK\Configuration as LiltConnectorConfiguration;
use lilthq\craftliltplugin\services\repositories\SettingsRepository;
use lilthq\craftliltplugin\utilities\Configuration;
use Throwable;
use yii\web\Response;
use LiltConnectorSDK\Model\SettingsResponse1 as SettingsRequest;

class PostConfigurationController extends AbstractJobController
{
    protected array|int|bool $allowAnonymous = false;

    /**
     * @throws Throwable
     * TODO: move to handler/command
     */
    public function actionInvoke(): Response
    {
        $request = $this->request;

        $connectorApiKey = $request->getBodyParam('connectorApiKey');
        $connectorApiUrl = $request->getBodyParam('connectorApiUrl');

        $newSettingsApi = new SettingsApi(
            new Client(),
            LiltConnectorConfiguration::getDefaultConfiguration()
                ->setAccessToken($connectorApiKey)
                ->setHost($connectorApiUrl)
                ->setUserAgent(
                    Craftliltplugin::getInstance()->getUserAgent()
                )
        );

        try {
            $newSettingsApi
                ->servicesApiSettingsGetSettings();

            Craftliltplugin::getInstance()->settingsRepository->saveLiltApiConnectionConfiguration(
                $connectorApiUrl,
                $connectorApiKey
            );
        } catch (Exception $ex) {
            Craft::error([
                'message' => "Can't connect to Lilt",
                'exception_message' => $ex->getMessage(),
                'exception_trace' => $ex->getTrace(),
                'exception' => $ex,
            ]);

            Craft::$app->getSession()->setFlash(
                'cp-error',
                "Can't update configuration, connection to Lilt is failed." .
                " Looks like API Key or API URL is wrong."
            );

            return $this->redirect(
                UrlHelper::cpUrl(sprintf('craft-lilt-plugin/settings/%s', Configuration::id()))
            );
        }

        $liltConfigDisabled = (bool)$request->getBodyParam('liltConfigDisabled');

        if ($liltConfigDisabled) {
            Craft::$app->getSession()->setFlash(
                'cp-notice',
                'Configuration options saved successfully'
            );

            return $this->redirect(
                UrlHelper::cpUrl(sprintf('craft-lilt-plugin/settings/%s', Configuration::id()))
            );
        }

        Craftliltplugin::getInstance()->settingsRepository->save(
            SettingsRepository::ENABLE_ENTRIES_FOR_TARGET_SITES,
            $request->getBodyParam('enableEntriesForTargetSites') ?? '0'
        );

        Craftliltplugin::getInstance()->settingsRepository->save(
            SettingsRepository::COPY_ENTRIES_SLUG_FROM_SOURCE_TO_TARGET,
            $request->getBodyParam('copyEntriesSlugFromSourceToTarget') ?? '0'
        );

        $queueEachTranslationFileSeparately = $request->getBodyParam('queueEachTranslationFileSeparately');
        if (empty($queueEachTranslationFileSeparately)) {
            $queueEachTranslationFileSeparately = 0;
        }

        Craftliltplugin::getInstance()->settingsRepository->save(
            SettingsRepository::QUEUE_EACH_TRANSLATION_FILE_SEPARATELY,
            (string)$queueEachTranslationFileSeparately
        );

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

        try {
            $newSettingsApi->servicesApiSettingsUpdateSettings(
                $settingsRequest
            );
        } catch (Exception $ex) {
            Craft::error([
                'message' => "Can't connect to Lilt",
                'exception_message' => $ex->getMessage(),
                'exception_trace' => $ex->getTrace(),
                'exception' => $ex,
            ]);

            Craft::$app->getSession()->setFlash(
                'cp-error',
                "Can't update configuration, connection to Lilt is failed. Looks like API Key or API URL is wrong"
            );

            return $this->redirect(
                UrlHelper::cpUrl(sprintf('craft-lilt-plugin/settings/%s', Configuration::id()))
            );
        }

        Craft::$app->getSession()->setFlash(
            'cp-notice',
            'Configuration options saved successfully'
        );

        return $this->redirect(
            UrlHelper::cpUrl(sprintf('craft-lilt-plugin/settings/%s', Configuration::id()))
        );
    }
}
