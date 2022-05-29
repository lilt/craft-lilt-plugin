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
use lilthq\craftliltplugin\exeptions\JobNotFoundException;
use lilthq\craftliltplugin\records\JobRecord;
use lilthq\craftliltplugin\services\repositories\JobRepository;
use RuntimeException;

class EditJobHandler
{
    /**
     * @var JobRepository
     */
    public $jobRepository;

    public function __invoke(EditJobCommand $command): Job
    {
        $job = $this->jobRepository->findOneById(
            $command->getJobId()
        );
        $jobRecord = JobRecord::findOne(['id' => $command->getJobId()]);

        if (!$job || !$jobRecord) {
            Craft::error(
                sprintf('Job with id %d not found', $command->getJobId())
            );

            throw new JobNotFoundException();
        }

        $job->title = $command->getTitle();
        $job->sourceSiteId = $command->getSourceSiteId();

        $job->sourceSiteLanguage = Craftliltplugin::getInstance()
            ->languageMapper
            ->getLanguageBySiteId(
                $command->getSourceSiteId()
            );

        if($command->getStatus()) {
            $job->status = $command->getStatus();
        }

        $job->targetSiteIds = $command->getTargetSitesIds();
        $job->elementIds = $command->getEntries();
        $job->translationWorkflow = $command->getTranslationWorkflow();
        $job->versions = $command->getVersions();

        $jobRecord->setAttributes($job->getAttributes(), false);

        $statusElement = Craft::$app->getElements()->saveElement(
            $job,
            true,
            true,
            true
        );

        $status = $jobRecord->save();

        if (!$status || !$statusElement) {
            throw new RuntimeException("Cant edit the job");
        }

        Craftliltplugin::getInstance()->jobLogsRepository->create(
            $jobRecord->id,
            Craft::$app->getUser()->getId(),
            'Job edited'
        );

        return $job;
    }
}
