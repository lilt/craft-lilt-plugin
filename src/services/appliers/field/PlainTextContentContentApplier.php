<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\appliers\field;

class PlainTextContentContentApplier extends AbstractContentApplier
{
   public function apply(): bool
   {
       return true;
   }
}