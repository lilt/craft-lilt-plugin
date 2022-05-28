<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\listeners;

class ListenerRegister
{
    /**
     * @var string[]
     */
    public $availableListeners;

    /**
     * @var ListenerInterface<string>
     */
    private $_listeners = [];

    public function register(): void
    {
        foreach ($this->availableListeners as $availableListener) {
            $this->_listeners[$availableListener] = new $availableListener;
            $this->_listeners[$availableListener]->register();
        }
    }
}