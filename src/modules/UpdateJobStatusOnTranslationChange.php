<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\modules;

use Craft;
use craft\queue\BaseJob;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\elements\Job;
use lilthq\craftliltplugin\records\JobRecord;
use lilthq\craftliltplugin\records\TranslationRecord;

class UpdateJobStatusOnTranslationChange extends BaseJob
{
    /**
     * @var int $jobId
     */
    public $jobId;

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        $jobRecord = JobRecord::findOne(['id' => $this->jobId]);

        if (!$jobRecord) {
            $this->markAsDone($queue);
            return;
        }

        $readyToPublish = true;

        $translations = Craftliltplugin::getInstance()->translationRepository->findByJobId($this->jobId);
        foreach ($translations as $translation) {
            if ($translation->status !== TranslationRecord::STATUS_READY_TO_PUBLISH) {
                $readyToPublish = false;
                break;
            }
        }

        if ($readyToPublish) {
            $jobRecord->status = Job::STATUS_READY_TO_PUBLISH;
            $jobRecord->save();

            Craft::$app->elements->invalidateCachesForElementType(
                Job::class
            );
        }

        $this->markAsDone($queue);
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
                'Updating status for jobId: {jobId} is done',
                [
                    'jobId' => $this->jobId,
                ]
            )
        );
    }
}
