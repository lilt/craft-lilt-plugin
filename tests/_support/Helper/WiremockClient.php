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

use Codeception\Lib\ModuleContainer;
use Codeception\Module;
use PHPUnit\Framework\Assert;
use WireMock\Client\WireMock;

class WiremockClient extends Module
{
    private $wireMock = null;

    public function __construct(ModuleContainer $moduleContainer, $config = null)
    {
        parent::__construct($moduleContainer, $config);

        $this->wireMock = WireMock::create('wiremock', 80);
        $this->wireMock->reset();

        Assert::assertTrue($this->wireMock->isAlive());
    }

    public function expectJobCreateRequest(array $body, int $responseCode, array $responseBody): void {
        $this->wireMock->stubFor(
            WireMock::post(WireMock::urlEqualTo('/api/v1.0/jobs'))
                ->withRequestBody(
                    WireMock::equalToJson(
                        json_encode($body),
                        true,
                        false
                    )
                )
                ->willReturn(
                    WireMock::aResponse()
                        ->withStatus($responseCode)
                        ->withBody(json_encode($responseBody))
                )
        );
    }
}
