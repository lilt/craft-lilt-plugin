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

use Codeception\Exception\ModuleException;
use Codeception\Module;
use Codeception\Lib\JsonArray;

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

    /**
     * @throws ModuleException
     */
    public function seeResponseIsJson(): void
    {
        $response = $this->getModule('Yii2')->_getResponseContent();
        $this->assertJson($response, 'Response is not valid JSON');
    }

    /**
     * @throws ModuleException
     */
    public function seeResponseContainsJson(array $subset): void
    {
        $response = $this->getModule('Yii2')->_getResponseContent();
        $this->assertJson($response, 'Response is not valid JSON');
        $actual = json_decode($response, true);
        $this->assertArraySubset($subset, $actual, 'The JSON response does not contain the expected subset.');
    }

    protected function assertArraySubset(array $subset, array $array, string $message = ''): void
    {
        foreach ($subset as $key => $value) {
            $this->assertArrayHasKey($key, $array, $message);

            if (is_array($value)) {
                $this->assertIsArray($array[$key], $message);
                $this->assertArraySubset($value, $array[$key], $message);
            } else {
                $this->assertEquals($value, $array[$key], $message);
            }
        }
    }
}
