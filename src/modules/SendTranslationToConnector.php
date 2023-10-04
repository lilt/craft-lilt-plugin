<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\modules;

use Craft;
use craft\errors\InvalidFieldException;
use craft\helpers\Queue;
use craft\queue\BaseJob;
use LiltConnectorSDK\ApiException;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\models\TranslationModel;
use lilthq\craftliltplugin\records\TranslationRecord;
use lilthq\craftliltplugin\services\handlers\commands\SendTranslationCommand;
use Throwable;

class SendTranslationToConnector extends AbstractRetryJob
{
    public const DELAY_IN_SECONDS = 0;
    public const PRIORITY = 1024;
    public const TTR = 60 * 30;

    private const RETRY_COUNT = 3;

    /**
     * @var int
     */
    public $elementId;

    /**
     * @var int
     */
    public $versionId;

    /**
     * @var int
     */
    public $targetSiteId;

    /**
     * @var int|null
     */
    public $translationId;

    /**
     * @inheritdoc
     *
     * @throws ApiException
     * @throws Throwable
     * @throws InvalidFieldException
     */
    public function execute($queue): void
    {
        $command = $this->getCommand();
        if (empty($command)) {
            return;
        }

        if (!$command->getJob()->isVerifiedFlow() && !$command->getJob()->isInstantFlow()) {
            Craft::error(
                sprintf(
                    "Job can't be proceed, incorrect flow %s: %d",
                    $command->getJob()->translationWorkflow,
                    $command->getJob()->id
                )
            );

            return;
        }

        if (empty($command->getJob()->liltJobId)) {
            Craft::error(
                sprintf(
                    "Job can't be proceed, empty lilt id [%s]: %d",
                    $command->getJob()->translationWorkflow,
                    $command->getJob()->id
                )
            );

            return;
        }

        $element = Craft::$app->elements->getElementById($this->versionId, null, $command->getJob()->sourceSiteId);

        $translationRecord = TranslationRecord::findOne(['id' => $this->translationId]);

        Craftliltplugin::getInstance()
            ->sendTranslationToLiltConnectorHandler
            ->send(
                new SendTranslationCommand(
                    $this->elementId,
                    $this->versionId,
                    $this->targetSiteId,
                    $element,
                    $command->getJob()->liltJobId,
                    $command->getJob(),
                    $translationRecord
                )
            );

        $translations = Craftliltplugin::getInstance()
            ->translationRepository
            ->findByJobId($this->jobId);

        $sourceContents = array_map(
            function (TranslationModel $translationModel) {
                return $translationModel->sourceContent;
            },
            $translations
        );

        if (!in_array(null, $sourceContents)) {
            // All translations downloaded, let's start the job
            Craftliltplugin::getInstance()->connectorJobRepository->start(
                $command->getJob()->liltJobId
            );

            Craftliltplugin::getInstance()->jobLogsRepository->create(
                $this->jobId,
                Craft::$app->getUser()->getId(),
                'Job uploaded to Lilt Platform'
            );

            Queue::push(
                (new FetchJobStatusFromConnector([
                    'jobId' => $command->getJob()->id,
                    'liltJobId' => $command->getJob()->liltJobId,
                ])),
                FetchJobStatusFromConnector::PRIORITY,
                10
            );
        }

        $this->markAsDone($queue);
        $this->release();
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): ?string
    {
        return Craft::t('app', 'Sending translation to lilt');
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

    public static function getDelay(): int
    {
        $envDelay = getenv('CRAFT_LILT_PLUGIN_QUEUE_DELAY_IN_SECONDS');
        if (!empty($envDelay) || $envDelay === '0') {
            return (int)$envDelay;
        }

        return self::DELAY_IN_SECONDS;
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
            'elementId' => $this->elementId,
            'versionId' => $this->versionId,
            'targetSiteId' => $this->targetSiteId,
            'attempt' => $this->attempt + 1
        ]);
    }
}
