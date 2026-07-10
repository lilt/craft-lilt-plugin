<?php

/**
 * craft-lilt-plugin plugin for Craft CMS 3.x
 *
 * The Lilt plugin makes it easy for you to send content to Lilt for translation right from within Craft CMS.
 *
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

use Codeception\Actor;
use Codeception\Lib\Friend;

/**
 * Inherited Methods
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method Friend haveFriend($name, $actorClass = null)
 *
 * @SuppressWarnings(PHPMD)
 *
 */
class IntegrationTester extends Actor
{
    use _generated\IntegrationTesterActions;

    /**
     * @var array A temporary store for original application services that are being mocked.
     */
    private array $originalServices = [];

    /**
     * Backs up a core application service before it's replaced by a mock or spy.
     *
     * @param string $id The service ID (e.g., 'logger').
     */
    public function backupService(string $id): void
    {
        if (Craft::$app->has($id) && !isset($this->originalServices[$id])) {
            $this->originalServices[$id] = Craft::$app->get($id);
        }
    }

    /**
     * Restores all backed-up services to their original state.
     */
    public function restoreOriginalServices(): void
    {
        foreach ($this->originalServices as $id => $service) {
            Craft::$app->set($id, $service);
        }
        $this->originalServices = []; // Clear the array for the next test
    }
}
