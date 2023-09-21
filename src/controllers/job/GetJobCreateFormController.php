<?php

/**
 * craft-lilt-plugin plugin for Craft CMS 3.x
 *
 * The Lilt plugin makes it easy for you to send content to Lilt for translation right from within Craft CMS.
 *
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\controllers\job;

use Craft;
use craft\helpers\UrlHelper;
use lilthq\craftliltplugin\elements\Job;
use RuntimeException;
use yii\base\InvalidConfigException;
use yii\web\IdentityInterface;
use yii\web\Response;

class GetJobCreateFormController extends AbstractJobController
{
    protected $allowAnonymous = false;

    /**
     * @throws InvalidConfigException
     */
    public function actionInvoke(): Response
    {
        $request = Craft::$app->getRequest();

        $job = (new Job());
        $job->versions = [];
        $job->status = Job::STATUS_DRAFT;

        $elementIds = $request->getQueryParam('elementIds');

        if ($elementIds) {
            $job->elementIds = $elementIds;
        }

        $sourceSiteId = $request->getQueryParam('sourceSiteId');

        if ($sourceSiteId) {
            $job->sourceSiteId = (int) $sourceSiteId;
        }

        $userIdentity = Craft::$app->getUser()->getIdentity();
        if (!$userIdentity instanceof IdentityInterface || empty($userIdentity->getId())) {
            throw new RuntimeException("can't create job: author id is empty");
        }

        $job->authorId = (int)$userIdentity->getId();

        return $this->renderJobForm(
            $job,
            [
                'crumbs' => [
                    [
                        'label' => 'Lilt Plugin',
                        'url' => UrlHelper::cpUrl('admin/craft-lilt-plugin')
                    ],
                    [
                        'label' => 'Jobs',
                        'url' => UrlHelper::cpUrl('admin/craft-lilt-plugin/jobs')
                    ],
                ],
            ]
        );
    }
}
