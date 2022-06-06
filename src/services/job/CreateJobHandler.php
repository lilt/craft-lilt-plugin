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
        $job = new Job();
        $job->authorId = $command->getAuthorId();
        $job->title = $command->getTitle();
        $job->liltJobId = null;
        $job->status = $asDraft ? Job::STATUS_DRAFT : Job::STATUS_NEW;
        $job->sourceSiteId = $command->getSourceSiteId();

        $job->sourceSiteLanguage = Craftliltplugin::getInstance()
            ->languageMapper
            ->getLanguageBySiteId(
                $command->getSourceSiteId()
            );

        $job->targetSiteIds = $command->getTargetSitesIds();
        $job->elementIds = $command->getEntries();
        $job->versions = $command->getVersions();
        $job->translationWorkflow = $command->getTranslationWorkflow();
        $job->draftId = null;
        $job->revisionId = null;

        $jobRecord = new JobRecord();
        $jobRecord->setAttributes($job->getAttributes(), false);

        $statusElement = Craft::$app->getElements()->saveElement(
            $job,
            true,
            true,
            true
        );

        #TODO: rethink this, what if we use separate id for elements table and separate for job record
        $jobRecord->id = $job->id;

        $status = $jobRecord->save();


        if (!$status || !$statusElement) {
            throw new RuntimeException("Cant create the job");
        }

        Craftliltplugin::getInstance()->jobLogsRepository->create(
            $jobRecord->id,
            Craft::$app->getUser()->getId(),
            'Job created'
        );

        return $job;
    }
}
