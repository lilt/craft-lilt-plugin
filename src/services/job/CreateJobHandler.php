<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\job;

use Craft;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\records\JobRecord;
use RuntimeException;

class CreateJobHandler
{
    public function __invoke(CreateJobCommand $command, bool $asDraft = false): Job
    {
        $element = new Job();
        $element->authorId = $command->getAuthorId();
        $element->title = $command->getTitle();
        $element->liltJobId = null;
        $element->status = $asDraft ? Job::STATUS_DRAFT : Job::STATUS_NEW;
        $element->sourceSiteId = $command->getSourceSiteId();

        $element->sourceSiteLanguage = Craftliltplugin::getInstance()
            ->languageMapper
            ->getLanguageBySiteId(
                $command->getSourceSiteId()
            );

        $element->targetSiteIds = $command->getTargetSitesIds();
        $element->elementIds = $command->getEntries();
        $element->versions = $command->getVersions();
        $element->translationWorkflow = $command->getTranslationWorkflow();
        $element->draftId = null;
        $element->revisionId = null;
        $jobRecord = new JobRecord();
        $jobRecord->setAttributes($element->getAttributes(), false);

        $statusElement = Craft::$app->getElements()->saveElement(
            $element,
            true,
            true,
            true
        );

        #TODO: rethink this, what if we use separate id for elements table and separate for job record
        $jobRecord->id = $element->id;

        $status = $jobRecord->save();


        if (!$status || !$statusElement) {
            throw new RuntimeException("Cant create the job");
        }

        Craftliltplugin::getInstance()->jobLogsRepository->create(
            $jobRecord->id,
            Craft::$app->getUser()->getId(),
            'Job created'
        );

        return $element;
    }
}
