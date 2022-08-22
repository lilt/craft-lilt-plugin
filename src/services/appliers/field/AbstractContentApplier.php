<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\appliers\field;

use benf\neo\elements\Block;
use Craft;
use craft\base\FieldInterface;
use craft\elements\MatrixBlock;
use craft\errors\InvalidFieldException;
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

            return $success;
        }

        return null;
    }

    protected function getFieldKey(FieldInterface $field): string
    {
        return $field->handle;
    }

    /**
     * @throws InvalidFieldException
     *
     * @return mixed
     */
    protected function getOriginalFieldSerializedValue(ApplyContentCommand $command)
    {
        if (empty($command->getField()->handle)) {
            Craft::warning([
                'message' => 'Handle for field is empty, please check CraftCMS configuration',
                'field' => $command->getField()->toArray()
            ]);

            return [];
        }

        $fieldValue = $command->getElement()->getFieldValue(
            $command->getField()->handle
        );

        if (empty($fieldValue)) {
            Craft::warning([
                'message' => 'Field value is empty, please configure it for proper translation',
                'field' => $command->getField()->toArray(),
                'fieldValue' => $fieldValue,
                'element' => $command->getElement()->toArray(),
            ]);

            return [];
        }

        return $command->getField()->serializeValue(
            $fieldValue,
            $command->getElement()
        );
    }
}
