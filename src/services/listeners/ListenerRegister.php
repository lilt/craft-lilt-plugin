<?php

/**
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\listeners;

/**
 * Register plugin listeners
 */
class ListenerRegister
{
    /**
     * @var string[]
     */
    public $availableListeners;

    /**
     * @var ListenerInterface<string>
     */
    private $listeners = [];

    public function register(): void
    {
        foreach ($this->availableListeners as $availableListener) {
            $this->listeners[$availableListener] = new $availableListener();
            $this->listeners[$availableListener]->register();
        }
    }
}
