<?php
/**
 * craft-lilt-plugin plugin for Craft CMS 3.x
 *
 * The Lilt plugin makes it easy for you to send content to Lilt for translation right from within Craft CMS.
 *
 * @link      https://github.com/lilt
 * @copyright Copyright (c) 2022 Lilt Devs
 */

namespace Helper;

use Codeception\Module;

/**
 * Class Unit
 *
 * Here you can define custom actions.
 * All public methods declared in helper class will be available in $I
 *
 */
class Integration extends Module
{
    public function seeHeader(string $name, string $value): void
    {
        $response = \Craft::$app->getResponse();

        $this->assertTrue($response->headers->has($name));
        $this->assertSame($value, $response->headers->get($name));
    }
}
