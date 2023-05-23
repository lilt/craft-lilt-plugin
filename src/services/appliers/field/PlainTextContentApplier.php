<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\appliers\field;

use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use lilthq\craftliltplugin\records\TranslationNotificationsRecord;

class PlainTextContentApplier extends AbstractContentApplier implements ApplierInterface
{
    public function apply(ApplyContentCommand $command): ApplyContentResult
    {
        $fieldKey = $this->getFieldKey($command->getField());
        $content = $command->getContent();

        if (!isset($content[$fieldKey])) {
            return ApplyContentResult::fail();
        }

        $fieldSettings = $command->getField()->getSettings();

        if (isset($fieldSettings['charLimit'])) {
            $maxLength = (int)$fieldSettings['charLimit'];

            if ($maxLength < strlen($content[$fieldKey])) {
                $translationNotification = new TranslationNotificationsRecord();

                $translationNotification->translationId = $command->getTranslationRecord()->id;
                $translationNotification->jobId = $command->getJob()->id;

                $translationNotification->fieldId = $command->getField()->id;
                $translationNotification->fieldUID = $command->getField()->uid;
                $translationNotification->fieldHandle = $command->getField()->handle;

                $translationNotification->reason = "reached_characters_limit";
                $translationNotification->level = "error";

                $sourceContent = $command->getElement()->getFieldValue($command->getField()->handle);
                if (strlen($sourceContent) > 64) {
                    $sourceContent = substr(
                        $command->getElement()->getFieldValue($command->getField()->handle),
                        0,
                        61
                    ) . '...';
                }

                $targetContent = $content[$fieldKey];
                if (strlen($targetContent) > 64) {
                    $targetContent = (substr($content[$fieldKey], 0, 61) . '...');
                }

                $translationNotification->sourceContent = mb_convert_encoding($sourceContent, "UTF-8");
                $translationNotification->targetContent = mb_convert_encoding($targetContent, "UTF-8");

                $translationNotification->save();

                return ApplyContentResult::fail();
            }
        }

        $command->getElement()->setFieldValue($command->getField()->handle, $content[$fieldKey]);

        $this->forceSave($command);

        return ApplyContentResult::applied([], $command->getElement()->getFieldValue($command->getField()->handle));
    }

    public function support(ApplyContentCommand $command): bool
    {
        return get_class($command->getField()) === CraftliltpluginParameters::CRAFT_FIELDS_PLAINTEXT
            && $command->getField()->getIsTranslatable($command->getElement());
    }
}
