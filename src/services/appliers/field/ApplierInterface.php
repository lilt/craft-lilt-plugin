<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\appliers\field;

interface ApplierInterface
{
    public function apply(ApplyContentCommand $command): ApplyContentResult;
    public function support(ApplyContentCommand $command): bool;
}
