<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\controllers\job;

use Craft;
use craft\web\Controller;
use Exception;
use lilthq\craftliltplugin\assets\JobFormAsset;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use yii\base\InvalidConfigException;
use yii\web\Response;

class AbstractJobController extends Controller
{
    /**
     * @throws InvalidConfigException
     * @throws Exception
     */
    protected function convertRequestToJobModel(): Job
    {
        $bodyParams = $this->request->getBodyParams();

        $job = new Job();
        $job->id = (int)($bodyParams['jobId'] ?? null);
        $job->title = $bodyParams['title'];
        $job->sourceSiteId = (int)$bodyParams['sourceSite'];
        $job->versions = $bodyParams['versions'] ?? [];
        $job->authorId = !empty($bodyParams['author'][0]) ? (int)$bodyParams['author'][0] : null;
        $job->translationWorkflow = $bodyParams['translationWorkflow'];
        $job->elementIds = json_decode($bodyParams['entries'], false) ?? [];

        //TODO: due date not using right now
        //$job->dueDate = DateTimeHelper::toDateTime($this->request->getBodyParam('dueDate')) ?: null;

        if (empty($bodyParams['targetSiteIds'])) {
            $job->targetSiteIds = [];
        } else {
            $job->targetSiteIds = $bodyParams['targetSiteIds'] === '*' ?
                Craftliltplugin::getInstance()->languageMapper->getLanguageToSiteId()
                : $bodyParams['targetSiteIds'];
        }

        return $job;
    }

    /**
     * @throws InvalidConfigException
     */
    protected function renderJobForm(
        Job $job,
        array $variablesToAdd = [],
        string $template = 'craft-lilt-plugin/job/create.twig'
    ): Response {
        Craft::$app->getView()->registerAssetBundle(JobFormAsset::class);

        $translationWorkflow = CraftliltpluginParameters::TRANSLATION_WORKFLOW_VERIFIED;

        if (
            $job->status === Job::STATUS_DRAFT ||
            $job->status === Job::STATUS_NEW
        ) {
            try {
                $settingsResult = Craftliltplugin::getInstance()->connectorSettingsApi->servicesApiSettingsGetSettings(
                );
                $translationWorkflow = strtolower($settingsResult->getLiltTranslationWorkflow());
            } catch (Exception $ex) {
                Craft::error([
                    'message' => "Can't fetch translation workflow",
                    'exception_message' => $ex->getMessage(),
                    'exception_trace' => $ex->getTrace(),
                    'exception' => $ex,
                ]);
            }
        }

        $variables = [
            'defaultTranslationWorkflow' => $translationWorkflow,
            'translationWorkflowsOptions' => CraftliltpluginParameters::getTranslationWorkflows(),
            'availableSites' => Craftliltplugin::getInstance()->languageMapper->getAvailableSitesForFormField(),
            'targetSites' => Craftliltplugin::getInstance()->languageMapper->getSiteIdToLanguage(),
            'element' => $job,
            'showLiltTranslateButton' => false,
            'isUnpublishedDraft' => true,
            'permissionSuffix' => ':edit-lilt-jobs',
            'authorOptionCriteria' => [
                'can' => 'editEntries:edit-lilt-jobs'
            ],
            'author' => (!empty($job->authorId)) ? Craft::$app->users->getUserById($job->authorId) : null,
            'sites' => Craft::$app->sites->getAllSites()
        ];

        return $this->renderTemplate(
            $template,
            array_merge($variables, $variablesToAdd)
        );
    }
}
