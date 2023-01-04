<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\modules;

use Craft;
use craft\helpers\Queue;
use craft\queue\BaseJob;
use LiltConnectorSDK\Model\JobResponse;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\records\JobRecord;
use yii\queue\RetryableJobInterface;

class FetchJobStatusFromConnector extends BaseJob implements RetryableJobInterface
{
    public const DELAY_IN_SECONDS = 10;
    public const PRIORITY = null;
    public const TTR = 60 * 30;

    private const RETRY_COUNT = 3;

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
        $jobRecord = JobRecord::findOne(['id' => $this->jobId]);

        if (!$jobRecord) {
            // job was removed, we are done here
            return;
        }

        if (empty($this->liltJobId)) {
            //looks like job is
            Queue::push(
                new SendJobToConnector(['jobId' => $this->jobId]),
                SendJobToConnector::PRIORITY,
                SendJobToConnector::DELAY_IN_SECONDS
            );

            $this->markAsDone($queue);

            return;
        }

        $liltJob = Craftliltplugin::getInstance()->connectorJobRepository->findOneById($this->liltJobId);
        $isTranslationFinished = $liltJob->getStatus() !== JobResponse::STATUS_PROCESSING
            && $liltJob->getStatus() !== JobResponse::STATUS_QUEUED;

        $isTranslationFailed = $liltJob->getStatus() === JobResponse::STATUS_CANCELED
            || $liltJob->getStatus() === JobResponse::STATUS_FAILED;

        if ($isTranslationFailed) {
            $jobRecord->status = Job::STATUS_FAILED;

            Craftliltplugin::getInstance()->jobLogsRepository->create(
                $jobRecord->id,
                Craft::$app->getUser()->getId(),
                sprintf('Job failed, received status: %s', $liltJob->getStatus())
            );

            $jobRecord->save();
            $this->markAsDone($queue);
            return;
        }

        if (!$isTranslationFinished) {
            Queue::push(
                (new FetchJobStatusFromConnector(
                    [
                        'jobId' => $this->jobId,
                        'liltJobId' => $this->liltJobId,
                    ]
                )),
                self::DELAY_IN_SECONDS,
                self::DELAY_IN_SECONDS
            );

            return;
        }

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
                FetchVerifiedJobTranslationsFromConnector::PRIORITY,
                FetchVerifiedJobTranslationsFromConnector::DELAY_IN_SECONDS
            );
        }

        if ($jobRecord->isInstantFlow()) {
            #LILT_TRANSLATION_WORKFLOW_INSTANT

            if ($liltJob->getStatus() === JobResponse::STATUS_FAILED) {
                $jobRecord->status = Job::STATUS_FAILED;
            } elseif ($liltJob->getStatus() === JobResponse::STATUS_CANCELED) {
                $jobRecord->status = Job::STATUS_FAILED;
            }

            Queue::push(
                new FetchInstantJobTranslationsFromConnector(
                    [
                        'jobId' => $this->jobId,
                        'liltJobId' => $this->liltJobId,
                    ]
                ),
                FetchInstantJobTranslationsFromConnector::PRIORITY,
                FetchInstantJobTranslationsFromConnector::DELAY_IN_SECONDS
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

    public function getTtr(): int
    {
        return self::TTR;
    }

    public function canRetry($attempt, $error): bool
    {
        return $attempt < self::RETRY_COUNT;
    }
}
