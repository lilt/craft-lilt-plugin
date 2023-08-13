<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers\field;

use craft\base\ElementInterface;
use lilthq\craftliltplugin\services\handlers\field\copier\FieldCopierInterface;

class CopyFieldsHandler
{
    public const DEFAULT_FIELD_COPIER = 'default';

    /**
     * @var FieldCopierInterface[]
     */
    private $fieldCopiers;

    public function __construct(array $fieldCopiers)
    {
        $this->fieldCopiers = $fieldCopiers;
    }

    /**
     * @param ElementInterface $from
     * @param ElementInterface|null $to
     *
     * @return bool
     */
    public function copy(
        ElementInterface $from,
        ElementInterface $to
    ): bool {
        // copy title
        $to->title = $from->title;

        $fieldLayout = $from->getFieldLayout();
        $fields = $fieldLayout ? $fieldLayout->getFields() : [];

        $result = [];

        foreach ($fields as $field) {
            $fieldClass = get_class($field);
            if (isset($this->fieldCopiers[$fieldClass])) {
                $result[$from->id . '-' . $field->handle] = $this->fieldCopiers[$fieldClass]->copy(
                    $field,
                    $from,
                    $to
                );

                continue;
            }

            $result[$from->id . '-' . $field->handle] = $this->fieldCopiers[self::DEFAULT_FIELD_COPIER]->copy(
                $field,
                $from,
                $to
            );
        }

        return !in_array(false, $result);
    }
}
