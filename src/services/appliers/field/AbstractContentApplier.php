<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\appliers\field;

use benf\neo\elements\Block;
use Craft;
use craft\base\FieldInterface;
use craft\elements\MatrixBlock;
use lilthq\craftliltplugin\records\I18NRecord;

abstract class AbstractContentApplier
{
    protected function isForceToSave(ApplyContentCommand $command): bool
    {
        return $command->getElement() instanceof MatrixBlock
            || $command->getElement() instanceof Block;
    }

    protected function forceSave(ApplyContentCommand $command): ?bool
    {
        if($this->isForceToSave($command)) {
            return Craft::$app->elements->saveElement(
                $command->getElement()
            );
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