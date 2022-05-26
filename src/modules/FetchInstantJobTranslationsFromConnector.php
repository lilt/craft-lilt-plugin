<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\modules;

use Craft;
use craft\errors\InvalidFieldException;
use craft\helpers\Queue;
use craft\queue\BaseJob;
use LiltConnectorSDK\ApiException;
use LiltConnectorSDK\Model\JobResponse;
use LiltConnectorSDK\Model\JobResponse1;
use LiltConnectorSDK\Model\SettingsResponse;
use LiltConnectorSDK\Model\TranslationResponse;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\datetime\DateTime;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\records\JobRecord;
use lilthq\craftliltplugin\records\TranslationRecord;
use Throwable;

class FetchInstantJobTranslationsFromConnector extends BaseJob
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
     *
     * @throws ApiException
     * @throws Throwable
     * @throws InvalidFieldException
     */
    public function execute($queue): void
    {
        $job = Job::findOne(['id' => $this->jobId]);
        if (!$job->isInstantFlow()) {
            $this->markAsDone($queue);

            return;
        }

        //TODO: pass from previous job maybe?
        $liltJob = Craftliltplugin::getInstance()->connectorJobRepository->findOneById($this->liltJobId);
        #$jobRecord = JobRecord::findOne(['id' => $this->jobId]);

        if ($liltJob->getStatus() === JobResponse::STATUS_COMPLETE) {
            #$jobRecord->status = Job::STATUS_READY_FOR_REVIEW;

            #$jobRecord->save();

            Craft::$app->elements->invalidateCachesForElementType(
                Job::class
            );
        }

        Craftliltplugin::$plugin->syncJobFromLiltConnectorHandler->__invoke($job);

        $this->markAsDone($queue);
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
