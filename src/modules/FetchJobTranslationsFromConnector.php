<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\modules;

use Craft;
use craft\helpers\Queue;
use craft\queue\BaseJob;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;

class FetchJobTranslationsFromConnector extends BaseJob
{
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

        Craftliltplugin::$plugin->syncJobFromLiltConnectorHandler->__invoke($job);

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
