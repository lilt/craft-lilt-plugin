<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2023 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers;

use Craft;
use craft\helpers\Queue;
use lilthq\craftliltplugin\elements\Translation;
use lilthq\craftliltplugin\modules\PublishTranslation;
use lilthq\craftliltplugin\records\TranslationRecord;
use lilthq\craftliltplugin\services\handlers\commands\PublishDraftCommand;
use lilthq\craftliltplugin\services\repositories\TranslationRepository;

class PublishDraftAsyncHandler implements PublishDraftHandlerInterface
{
    /**
     * @var TranslationRepository
     */
    public $translationRepository;

    public function __invoke(PublishDraftCommand $command): void
    {
        Queue::push(
            (new PublishTranslation(
                [
                    'jobId' => $command->getJobId(),
                    'translationId' => $command->getTranslationId(),
                    'targetSiteId' => $command->getTargetSiteId(),
                    'draftId' => $command->getDraftId(),
                ]
            )),
            PublishTranslation::PRIORITY,
            PublishTranslation::DELAY_IN_SECONDS
        );


        $this->translationRepository->updateTranslationStatusById(
            $command->getTranslationId(),
            TranslationRecord::STATUS_PUBLISHING
        );

        Craft::$app->getElements()->invalidateCachesForElementType(Translation::class);
    }
}
