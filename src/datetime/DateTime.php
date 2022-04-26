<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\datetime;

class DateTime extends \DateTime
{
    public function __toString(): string
    {
        return $this->format('c');
    }
}