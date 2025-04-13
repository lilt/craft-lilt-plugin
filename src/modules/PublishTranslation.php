<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2024 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\modules;

use Craft;
use craft\queue\BaseJob;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\services\handlers\commands\PublishDraftCommand;

class PublishTranslation extends AbstractRetryJob
{
    public const DELAY_IN_SECONDS = 1;
    public const PRIORITY = 512;
    public const TTR = 60 * 30;

    private const RETRY_COUNT = 3;

    /**
     * @var int
     */
    public $draftId;

    /**
     * @var int
     */
    public $targetSiteId;

    /**
     * @var int
     */
    public $jobId;

    /**
     * @var int
     */
    public $translationId;

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        $command = $this->getCommand();
        if (empty($command)) {
            return;
        }

        Craftliltplugin::getInstance()->publishDraftsHandler->__invoke(
            $this->getPublishDraftCommand()
        );

        Craftliltplugin::getInstance()->refreshJobStatusHandler->__invoke(
            $this->jobId
        );

        $this->markAsDone($queue);
        $this->release();
    }

    private function getPublishDraftCommand(): PublishDraftCommand
    {
        return new PublishDraftCommand(
            $this->draftId,
            $this->targetSiteId,
            $this->jobId,
            $this->translationId
        );
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): ?string
    {
        return Craft::t(
            'app',
            sprintf(
                'Publish translation draft: %d',
                $this->translationId
            )
        );
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
                'Sending translation for jobId: {jobId} to lilt platform done',
                [
                    'jobId' => $this->jobId,
                ]
            )
        );
    }

    public function canRetry(): bool
    {
        return $this->attempt < self::RETRY_COUNT;
    }

    public function getRetryJob(): BaseJob
    {
        return new self([
            'jobId' => $this->jobId,
            'translationId' => $this->translationId,
            'draftId' => $this->draftId,
            'targetSiteId' => $this->targetSiteId,
            'attempt' => $this->attempt + 1
        ]);
    }

    protected function getMutexKey(): string
    {
        return join('_', [
            __CLASS__,
            __FUNCTION__,
            $this->jobId,
            $this->translationId,
            $this->targetSiteId,
            $this->draftId,
            $this->attempt
        ]);
    }

    public static function getDelay(): int
    {
        $envDelay = getenv('CRAFT_LILT_PLUGIN_QUEUE_DELAY_IN_SECONDS');
        if (!empty($envDelay) || $envDelay === '0') {
            return (int)$envDelay;
        }

        return self::DELAY_IN_SECONDS;
    }
}
