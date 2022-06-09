<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\appliers\field;

use lilthq\craftliltplugin\parameters\CraftliltpluginParameters;

class NeoFieldContentApplier extends AbstractElementQueryContentApplier implements ApplierInterface
{
    public function support(ApplyContentCommand $command): bool
    {
        return get_class($command->getField()) === CraftliltpluginParameters::BENF_NEO_FIELD;
    }
}
