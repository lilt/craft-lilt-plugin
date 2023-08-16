<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers\field\copier;

use craft\base\ElementInterface;
use craft\base\FieldInterface;

class DefaultFieldCopier implements FieldCopierInterface
{
    public function copy(
        FieldInterface $field,
        ElementInterface $from,
        ElementInterface $to
    ): bool {
        $field->copyValue($from, $to);

        return true;
    }
}
