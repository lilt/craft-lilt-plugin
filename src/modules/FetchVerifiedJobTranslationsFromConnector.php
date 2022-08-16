<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\modules;

use Craft;
use craft\helpers\Queue;
use craft\queue\BaseJob;
use Exception;
use LiltConnectorSDK\Model\TranslationResponse;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\records\JobRecord;
use lilthq\craftliltplugin\records\TranslationRecord;

class FetchVerifiedJobTranslationsFromConnector extends BaseJob
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
        $job = Job::findOne(['id' => $this->jobId]);
        $jobRecord = JobRecord::findOne(['id' => $this->jobId]);

        if (!$jobRecord || !$job) {
            Craft::error(
                sprintf(
                    'Job record %d not found, looks like job was removed. Translation fetching aborted.',
                    $this->jobId
                )
            );

            $this->markAsDone($queue);

            return;
        }

        if ($job->isInstantFlow()) {
            $this->markAsDone($queue);

            return;
        }

        $unprocessedTranslations = Craftliltplugin::getInstance()
            ->translationRepository
            ->findUnprocessedByJobIdMapped($job->id);

        if (empty($unprocessedTranslations)) {
            $jobRecord->status = Job::STATUS_READY_FOR_REVIEW;
            $jobRecord->save();

            Craft::$app->elements->invalidateCachesForElementType(
                Job::class
            );

            $this->markAsDone($queue);
        }

        $translations = Craftliltplugin::getInstance()->connectorTranslationRepository->findByJobId(
            $job->liltJobId
        );

        $statuses = array_unique(
            array_map(
                function (TranslationResponse $translationResponse) use ($job, $unprocessedTranslations) {
                    if ($translationResponse->getStatus() === TranslationResponse::STATUS_EXPORT_COMPLETE) {
                        try {
                            Craftliltplugin::getInstance()->syncJobFromLiltConnectorHandler->processTranslation(
                                $translationResponse,
                                $job
                            );
                        } catch (Exception $ex) {
                            Craft::error([
                                'message' => "Can't fetch process translation!",
                                'exception_message' => $ex->getMessage(),
                                'exception_trace' => $ex->getTrace(),
                                'exception' => $ex,
                            ]);

                            Craftliltplugin::getInstance()->translationFailedHandler->__invoke(
                                $translationResponse,
                                $job,
                                $unprocessedTranslations
                            );

                            return TranslationRecord::STATUS_FAILED;
                        }

                        return TranslationRecord::STATUS_READY_FOR_REVIEW;
                    }

                    if (
                        $translationResponse->getStatus() === TranslationResponse::STATUS_IMPORT_FAILED
                        || $translationResponse->getStatus() === TranslationResponse::STATUS_EXPORT_FAILED
                    ) {
                        Craftliltplugin::getInstance()->translationFailedHandler->__invoke(
                            $translationResponse,
                            $job,
                            $unprocessedTranslations
                        );

                        return TranslationRecord::STATUS_FAILED;
                    }

                    return TranslationRecord::STATUS_IN_PROGRESS;
                },
                $translations->getResults()
            )
        );

        if (in_array(TranslationRecord::STATUS_IN_PROGRESS, $statuses, true)) {
            // One of translations still in progress, we are waiting till all of them are done
            Queue::push(
                (new FetchVerifiedJobTranslationsFromConnector(
                    [
                        'jobId' => $this->jobId,
                        'liltJobId' => $this->liltJobId,
                    ]
                )),
                null,
                self::DELAY_IN_SECONDS
            );
        }

        if ($statuses === ['failed', 'canceled']) {
            $jobRecord->status = Job::STATUS_FAILED;
            $jobRecord->save();
            $this->markAsDone($queue);
        } elseif (in_array('in-progress', $statuses, true)) {
            $jobRecord->status = Job::STATUS_IN_PROGRESS;
            $jobRecord->save();
            $this->markAsDone($queue);
        } else {
            //TODO: can't be default, we need to reach all translations to status ready for review!
            $jobRecord->status = Job::STATUS_READY_FOR_REVIEW;
            $jobRecord->save();
            $this->markAsDone($queue);
        }

        Craft::$app->elements->invalidateCachesForElement(
            $job
        );
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): ?string
    {
        return Craft::t('app', 'Lilt translations');
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
                'Fetching of translations for jobId: {jobId} liltJobId: {liltJobId} is done',
                [
                    'jobId' => $this->jobId,
                    'liltJobId' => $this->liltJobId,
                ]
            )
        );
    }
}
