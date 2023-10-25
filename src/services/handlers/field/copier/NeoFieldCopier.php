<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers\field\copier;

use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\errors\ElementNotFoundException;
use craft\errors\InvalidFieldException;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;
use yii\base\Exception;
use yii\base\InvalidConfigException;

class NeoFieldCopier implements FieldCopierInterface
{
    /**
     * @throws \Throwable
     * @throws ElementNotFoundException
     * @throws InvalidConfigException
     * @throws InvalidFieldException
     * @throws Exception
     */
    public function copy(
        FieldInterface $field,
        ElementInterface $from,
        ElementInterface $to
    ): bool {

        // Check if the field is of Neo type and the required classes and methods are available
        if (
            get_class($field) !== CraftliltpluginParameters::BENF_NEO_FIELD
            || !class_exists('benf\neo\Plugin')
            || !method_exists('benf\neo\Plugin', 'getInstance')
        ) {
            return false;
        }

        $serializedValue = $field->serializeValue($from->getFieldValue($field->handle), $from);

        $prepared = [];
        $i = 1;
        foreach ($serializedValue as $item) {
            $prepared[sprintf('new%d', $i++)] = $item;
        }

        $to->setFieldValues([$field->handle => $prepared]);

        return true;
    }
}
