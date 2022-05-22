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
use lilthq\craftliltplugin\datetime\DateTime;
use lilthq\craftliltplugin\elements\Job;
use yii\base\InvalidConfigException;
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

        if (!$request->getIsGet()) {
            return (new Response())->setStatusCode(405);
        }

        $job = (new Job());
        $job->versions = [];

        $elementIds = $request->getQueryParam('elementIds');

        if ($elementIds) {
            $job->elementIds = $elementIds;
        }

        $sourceSiteId = $request->getQueryParam('sourceSiteId');

        if ($sourceSiteId) {
            $job->sourceSiteId = $sourceSiteId;
        }

        /**
         * [{"label":"Create and continue editing","redirect":"8b4635887d078088430ed1ef32a141d02be90d9017624c9a2268adae0f3b0ab1{cpEditUrl}","shortcut":true,"retainScroll":true,"eventData":{"autosave":false}},{"label":"Create and add another","action":"entry-revisions\/publish-draft","redirect":"10b438699a9a92c9a2791885ea037a1d65357e1496087ef66d4bd0676f444d5eentries\/blog\/new?site=default","shortcut":true,"shift":true,"eventData":{"autosave":false}},{"label":"Save draft","action":"entry-revisions\/save-draft","redirect":"3142e9b53d10551091aadf3761447a4078d5946b38456518ac9fde16adf90e21entries\/blog#","eventData":{"autosave":false}},{"destructive":true,"label":"Delete draft","action":"entry-revisions\/delete-draft","redirect":"3142e9b53d10551091aadf3761447a4078d5946b38456518ac9fde16adf90e21entries\/blog#","confirm":"Are you sure you want to delete this draft?"}]
         */

        return $this->renderJobForm(
            $job,
            [
                /*'formActions' => [
                    [
                        "label" => "Create and continue editing",
                        "redirect" => "{cpEditUrl}",
                        "shortcut" => true,
                        "retainScroll" => true,
                        "eventData" => [
                            "autosave" => false
                        ]
                    ]
                ],*/
                'crumbs' => [
                    [
                        'label' => 'Lilt Plugin',
                        'url' => UrlHelper::cpUrl('admin/craft-lilt-plugin')
                    ],
                    [
                        'label' => 'Jobs',
                        'url' => UrlHelper::cpUrl('admin/craft-lilt-plugin/jobs')
                    ],
                ]
            ]
        );
    }
}
