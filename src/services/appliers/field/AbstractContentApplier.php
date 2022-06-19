<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\appliers\field;

use benf\neo\elements\Block;
use Craft;
use craft\base\FieldInterface;
use craft\elements\MatrixBlock;
use lilthq\craftliltplugin\records\I18NRecord;
use verbb\supertable\elements\SuperTableBlockElement;

abstract class AbstractContentApplier
{
    protected function isForceToSave(ApplyContentCommand $command): bool
    {
        return $command->getElement() instanceof MatrixBlock
            || $command->getElement() instanceof Block
            || $command->getElement() instanceof SuperTableBlockElement;
    }

    protected function forceSave(ApplyContentCommand $command): ?bool
    {
        if ($this->isForceToSave($command)) {
            //TODO: check this, seems to be overcomplicated
            if (method_exists($command->getElement(), 'setIsFresh')) {
                // TODO: It was added because of: Calling unknown method: craft\elements\MatrixBlock::setIsFresh()
                // @since In craft only from 3.7.14
                $command->getElement()->setIsFresh();
            }
            $success = Craft::$app->elements->saveElement(
                $command->getElement()
            );

            Craft::$app->elements->invalidateCachesForElement($command->getElement());

            $element = Craft::$app->elements->getElementById(
                $command->getElement()->id,
                null,
                $command->getTargetSiteId()
            );
            if (method_exists($element, 'setIsFresh')) {
                // TODO: It was added because of: Calling unknown method: craft\elements\MatrixBlock::setIsFresh()
                // @since In craft only from 3.7.14
                $element->setIsFresh();
            }

            $command->setElement(
                Craft::$app->elements->getElementById(
                    $command->getElement()->id,
                    null,
                    $command->getTargetSiteId()
                )
            );

            return $success;
        }

        return null;
    }

    protected function getFieldKey(FieldInterface $field): string
    {
        return $field->handle;
    }

    protected function createI18NRecord(array $data): I18NRecord
    {
        $record = new I18NRecord();

        $record->target = $data['target'];
        $record->source = $data['source'];
        $record->sourceSiteId = $data['sourceSiteId'];
        $record->targetSiteId = $data['targetSiteId'];
        $record->hash = $data['hash'];

        return $record;
    }
}
