<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\handlers\field\copier;

use craft\base\ElementInterface;
use craft\base\FieldInterface;

interface FieldCopierInterface
{
    public function copy(
        FieldInterface $field,
        ElementInterface $from,
        ElementInterface $to
    ): bool;
}
