<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\appliers\field;

use Craft;
use craft\fields\data\MultiOptionsFieldData;
use craft\fields\data\SingleOptionFieldData;
use lilthq\craftliltplugin\Craftliltplugin;
use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;

class BaseOptionFieldContentApplier extends AbstractContentApplier implements ApplierInterface
{
    public function apply(ApplyContentCommand $command): ApplyContentResult
    {
        $field = $command->getField();

        $i18NRecords = [];
        $fieldKey = $this->getFieldKey($field);
        $content = $command->getContent();

        if (!isset($content[$fieldKey])) {
            return ApplyContentResult::fail();
        }

        /**
         * @var SingleOptionFieldData|MultiOptionsFieldData $value
         */
        $value = $command->getElement()->getFieldValue($field->handle);
        $options = $value->getOptions();

        $optionsTranslated = $content[$field->handle];

        foreach ($options as $option) {
            if (empty($option->label) || empty($optionsTranslated[$option->value])) {
                continue;
            }

            $i18NRecord = Craftliltplugin::getInstance()->i18NRepository->new(
                $command->getSourceSiteId(),
                $command->getTargetSiteId(),
                $option->label,
                $optionsTranslated[$option->value]
            );

            $i18NRecords[$i18NRecord->generateHash()] = $i18NRecord;
        }

        return ApplyContentResult::applied($i18NRecords);
    }

    public function support(ApplyContentCommand $command): bool
    {
        return get_parent_class($command->getField()) === CraftliltpluginParameters::CRAFT_FIELDS_BASEOPTIONSFIELD
            && $command->getField()->getIsTranslatable($command->getElement());
    }
}
