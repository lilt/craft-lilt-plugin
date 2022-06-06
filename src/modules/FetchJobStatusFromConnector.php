<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\modules;

use Craft;
use craft\helpers\Queue;
use craft\queue\BaseJob;
use LiltConnectorSDK\Model\JobResponse;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\records\JobRecord;

class FetchJobStatusFromConnector extends BaseJob
{
    private const DELAY_IN_SECONDS = 10;
    /**
     * @var int $jobId
     */
    public $jobId;

    /**
     * @var int $liltJobId
     */
    public $liltJobId;

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        $liltJob = Craftliltplugin::getInstance()->connectorJobRepository->findOneById($this->liltJobId);
        $isTranslationFinished = $liltJob->getStatus() !== JobResponse::STATUS_PROCESSING
            && $liltJob->getStatus() !== JobResponse::STATUS_QUEUED;

        if (!$isTranslationFinished) {
            Queue::push(
                (new FetchJobStatusFromConnector(
                    [
                        'jobId' => $this->jobId,
                        'liltJobId' => $this->liltJobId,
                    ]
                )),
                null,
                self::DELAY_IN_SECONDS
            );

            return;
        }

        $jobRecord = JobRecord::findOne(['id' => $this->jobId]);

        if ($jobRecord->isVerifiedFlow()) {

            #LILT_TRANSLATION_WORKFLOW_VERIFIED

            $jobRecord->status = Job::STATUS_IN_PROGRESS;

            Queue::push(
                new FetchVerifiedJobTranslationsFromConnector(
                    [
                        'jobId' => $this->jobId,
                        'liltJobId' => $this->liltJobId,
                    ]
                ),
                null,
                self::DELAY_IN_SECONDS
            );
        }

        if ($jobRecord->isInstantFlow()) {

            #LILT_TRANSLATION_WORKFLOW_INSTANT

            if ($liltJob->getStatus() === JobResponse::STATUS_FAILED) {
                $jobRecord->status = Job::STATUS_FAILED;
            }

            Queue::push(
                new FetchInstantJobTranslationsFromConnector(
                    [
                        'jobId' => $this->jobId,
                        'liltJobId' => $this->liltJobId,
                    ]
                ),
                null,
                self::DELAY_IN_SECONDS
            );
        }

        $jobRecord->save();
        $this->markAsDone($queue);

        Craft::$app->elements->invalidateCachesForElementType(
            Job::class
        );
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): ?string
    {
        return Craft::t('app', 'Updating lilt job');
    }

    /**
     * @param $queue
     * @return void
     */
    private function markAsDone($queue): void
    {
        $this->setProgress(
            $queue,
            1,
            Craft::t(
                'app',
                'Fetching of status for jobId: {jobId} liltJobId: {liltJobId} is done',
                [
                    'jobId' => $this->jobId,
                    'liltJobId' => $this->liltJobId,
                ]
            )
        );
    }
}
