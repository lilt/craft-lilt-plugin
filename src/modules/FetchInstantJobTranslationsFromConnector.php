<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\modules;

use Craft;
use craft\errors\InvalidFieldException;
use craft\queue\BaseJob;
use LiltConnectorSDK\ApiException;
use LiltConnectorSDK\Model\JobResponse;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use Throwable;
use yii\queue\RetryableJobInterface;

class FetchInstantJobTranslationsFromConnector extends BaseJob implements RetryableJobInterface
{
    public const DELAY_IN_SECONDS = 10;
    public const PRIORITY = null;
    public const TTR = 60 * 5; // 10 minutes

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
     *
     * @throws ApiException
     * @throws Throwable
     * @throws InvalidFieldException
     */
    public function execute($queue): void
    {
        $job = Job::findOne(['id' => $this->jobId]);
        if (!$job || !$job->isInstantFlow()) {
            $this->markAsDone($queue);

            return;
        }

        $mutex = Craft::$app->getMutex();
        $mutexKey = __CLASS__ . '_' . __FUNCTION__ . '_' . $this->jobId;
        if (!$mutex->acquire($mutexKey)) {
            Craft::error(sprintf('Job %s is already processing job %d', __CLASS__, $this->jobId));

            return;
        }

        Craftliltplugin::$plugin->syncJobFromLiltConnectorHandler->__invoke($job);

        $liltJob = Craftliltplugin::getInstance()->connectorJobRepository->findOneById($this->liltJobId);
        if ($liltJob->getStatus() === JobResponse::STATUS_COMPLETE) {
            Craft::$app->elements->invalidateCachesForElementType(
                Job::class
            );
        }

        $this->markAsDone($queue);
        $mutex->release($mutexKey);
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

    public function getTtr(): int
    {
        return self::TTR;
    }

    public function canRetry($attempt, $error): bool
    {
        return $attempt < self::RETRY_COUNT;
    }
}
