<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2023 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\modules;

use Craft;
use craft\db\Table;
use craft\helpers\Db;
use craft\queue\BaseJob;
use craft\queue\Queue;
use craft\helpers\Queue as CraftHelpersQueue;
use Exception;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\records\JobRecord;
use lilthq\craftliltplugin\records\TranslationRecord;

class ManualJobSync extends BaseJob
{
    public const DELAY_IN_SECONDS = 0;
    public const PRIORITY = 512;

    private const SUPPORTED_JOBS = [
        FetchJobStatusFromConnector::class,
        FetchInstantJobTranslationsFromConnector::class,
        FetchVerifiedJobTranslationsFromConnector::class,
        FetchTranslationFromConnector::class,
        SendJobToConnector::class,
    ];

    /**
     * @var array
     */
    public $jobIds;

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        $jobRecords = JobRecord::findAll(['id' => $this->jobIds]);

        if (count($jobRecords) === 0) {
            // job was removed, we are done here
            return;
        }

        $jobsInfo = $queue->getJobInfo();

        $totalJobsCount = count($jobsInfo);
        $current = 1;
        $jobsInProgress = [];

        // Release all previously queued jobs for lilt plugin jobs
        foreach ($jobsInfo as $jobInfo) {
            $jobDetails = Craft::$app->getQueue()->getJobDetails((string)$jobInfo['id']);

            if (!in_array(get_class($jobDetails['job']), self::SUPPORTED_JOBS)) {
                continue;
            }

            /**
             * @var AbstractRetryJob $queueJob
             */
            $queueJob = $jobDetails['job'];
            if (!in_array($queueJob->jobId, $this->jobIds)) {
                //don't need to do anything, not in the list
                continue;
            }

            if ($jobDetails['status'] === Queue::STATUS_RESERVED) {
                //we don't need to do anything with job in progress
                $jobsInProgress[$queueJob->jobId] = $queueJob;

                continue;
            }

            if ($jobDetails['status'] === Queue::STATUS_WAITING) {
                $jobsInProgress[$queueJob->jobId] = $queueJob;

                try {
                    //we want to sync it faster, let's remove delay for it
                    Db::update(Table::QUEUE, [
                        'delay' => 0,
                    ], [
                        'id' => $jobInfo['id'],
                    ], [], false);
                } catch (Exception $ex) {
                    Craft::error(
                        sprintf(
                            "Can't update delay for job: %d. Due to issue: %s",
                            $jobInfo['id'],
                            $ex->getMessage()
                        )
                    );

                    Craftliltplugin::getInstance()->jobLogsRepository->create(
                        $queueJob->jobId,
                        Craft::$app->getUser()->getId(),
                        sprintf(
                            "Can't sync job manually: %d. Due to issue: %s",
                            $jobInfo['id'],
                            $ex->getMessage()
                        )
                    );
                }
            }

            if ($jobDetails['status'] === Queue::STATUS_FAILED) {
                try {
                    Db::update(Table::QUEUE, [
                        'delay' => 0,
                    ], [
                        'id' => $jobInfo['id'],
                    ], [], false);

                    $queue->retry((string)$jobInfo['id']);
                    $jobsInProgress[$queueJob->jobId] = $queueJob;
                } catch (Exception $ex) {
                    Craft::error(
                        sprintf(
                            "Can't retry job: %d. Due to issue: %s",
                            $jobInfo['id'],
                            $ex->getMessage()
                        )
                    );

                    Craftliltplugin::getInstance()->jobLogsRepository->create(
                        $queueJob->jobId,
                        Craft::$app->getUser()->getId(),
                        sprintf(
                            "Can't retry job manually: %d. Due to issue: %s",
                            $jobInfo['id'],
                            $ex->getMessage()
                        )
                    );
                }
            }

            $this->setProgress(
                $queue,
                ($current / $totalJobsCount) / 2,
                Craft::t(
                    'app',
                    'Checking queue. {current}/{totalJobsCount} jobs are checked',
                    [
                        'current' => $current,
                        'totalJobsCount' => $totalJobsCount,
                    ]
                )
            );

            $current++;
        }

        foreach ($jobRecords as $jobRecord) {
            if (isset($jobsInProgress[$jobRecord->id])) {
                continue;
            }

            if (
                $jobRecord->status === Job::STATUS_NEW
                || $jobRecord->status === Job::STATUS_DRAFT
                || $jobRecord->status === Job::STATUS_COMPLETE
            ) {
                continue;
            }

            if (!empty($jobRecord->liltJobId)) {
                $translationRecords = TranslationRecord::findAll(['jobId' => $jobRecord->id]);
                $connectorTranslationIds = array_map(
                    static function (TranslationRecord $translationRecord) {
                        return $translationRecord->connectorTranslationId;
                    },
                    $translationRecords
                );

                $translationStatuses = array_map(
                    static function (TranslationRecord $translationRecord) {
                        return $translationRecord->status;
                    },
                    $translationRecords
                );

                if (
                    !in_array(null, $connectorTranslationIds)
                    && !in_array(TranslationRecord::STATUS_FAILED, $translationStatuses)
                ) {
                    // job is already on lilt side, we just need to fetch status again
                    CraftHelpersQueue::push(
                        (new FetchJobStatusFromConnector(
                            [
                                'jobId' => $jobRecord->id,
                                'liltJobId' => $jobRecord->liltJobId,
                            ]
                        )),
                        FetchJobStatusFromConnector::PRIORITY,
                        0
                    );

                    continue;
                }

                $allDone = true;
                foreach ($translationRecords as $translationRecord) {
                    if(!empty($translationRecord->sourceContent)){
                        continue;
                    }

                    $allDone = false;

                    CraftHelpersQueue::push(
                        new SendTranslationToConnector([
                            'jobId' => $translationRecord->jobId,
                            'translationId' => $translationRecord->id,
                            'elementId' => $translationRecord->elementId,
                            'versionId' => $translationRecord->versionId,
                            'targetSiteId' => $translationRecord->targetSiteId,
                        ]),
                        SendTranslationToConnector::PRIORITY,
                        SendTranslationToConnector::getDelay(),
                        SendTranslationToConnector::TTR
                    );
                }

                if($allDone) {
                    CraftHelpersQueue::push(
                        (new FetchJobStatusFromConnector(
                            [
                                'jobId' => $jobRecord->id,
                                'liltJobId' => $jobRecord->liltJobId,
                            ]
                        )),
                        FetchJobStatusFromConnector::PRIORITY,
                        0
                    );
                }

                continue;
            }

            //Sending job to lilt
            CraftHelpersQueue::push(
                new SendJobToConnector(['jobId' => $jobRecord->id]),
                SendJobToConnector::PRIORITY,
                0
            );
        }

        $this->setProgress(
            $queue,
            1,
            Craft::t(
                'app',
                'Syncing of jobIds: {jobIds} is done',
                [
                    'jobIds' => json_encode($this->jobIds),
                ]
            )
        );
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): ?string
    {
        return Craft::t('app', 'Syncing job(s)');
    }
}
