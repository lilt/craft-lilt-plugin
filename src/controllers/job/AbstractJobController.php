<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\controllers\job;

use Craft;
use craft\helpers\DateTimeHelper;
use craft\web\Controller;
use Exception;
use lilthq\craftliltplugin\assets\JobFormAsset;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use yii\base\InvalidConfigException;
use yii\web\Response;

class AbstractJobController extends Controller
{
    /**
     * @throws InvalidConfigException
     * @throws Exception
     */
    protected function getJobModel(): Job
    {
        $bodyParams = $this->request->getBodyParams();

        $job = new Job();
        $job->id = (int) ($bodyParams['jobId'] ?? null);
        $job->title = $bodyParams['title'];
        $job->sourceSiteId = (int)$bodyParams['sourceSite'];
        $job->versions = $bodyParams['versions'] ?? [];
        $job->elementIds = json_decode($bodyParams['entries'], false) ?? [];
        $job->dueDate = DateTimeHelper::toDateTime($this->request->getBodyParam('dueDate')) ?: null;
        $job->targetSiteIds = $bodyParams['targetSiteIds'] === '*' ?
            Craftliltplugin::getInstance()->languageMapper->getLanguageToSiteId()
            : $bodyParams['targetSiteIds'];

        return $job;
    }

    /**
     * @throws InvalidConfigException
     */
    protected function renderJobForm(Job $job, array $variablesToAdd = [], string $template = 'craft-lilt-plugin/job/create.twig'): Response
    {
        Craft::$app->getView()->registerAssetBundle(JobFormAsset::class);

        $variables = [
            'availableSites' => Craftliltplugin::getInstance()->languageMapper->getAvailableSitesForFormField(),
            'targetSites' =>  Craftliltplugin::getInstance()->languageMapper->getSiteIdToLanguage(),
            'element' => $job,
            'showLiltTranslateButton' => false,
            'isUnpublishedDraft' => true,
            'permissionSuffix' => ':edit-lilt-jobs',
            'authorOptionCriteria' => [
                'can' => 'editEntries:edit-lilt-jobs'
            ],
            'author' => Craft::$app->getUser()->getIdentity(),
        ];

        return $this->renderTemplate(
            $template,
            array_merge($variables, $variablesToAdd)
        );
    }
}
