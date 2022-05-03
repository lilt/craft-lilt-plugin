<?php
/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */


declare(strict_types=1);

namespace lilthq\craftliltplugin\datetime;

class DateTime extends \DateTime
{
    public function __toString(): string
    {
        return $this->format('c');
    }
}