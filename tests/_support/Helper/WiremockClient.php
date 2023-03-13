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
use JsonException;
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

    public function expectJobTranslationsRequest(string $expectedUrl, array $expectedBody, int $statusCode): void
    {
        $this->wireMock->stubFor(
            WireMock::post(
                WireMock::urlEqualTo(
                    $expectedUrl
                )
            )
                //                ->withRequestBody(
                //                    WireMock::equalToJson(
                //                        json_encode($expectedBody),
                //                        true,
                //                        false
                //                    )
                //                )
                ->willReturn(
                    WireMock::aResponse()
                        ->withStatus($statusCode)
                )
        );
    }

    /**
     * @throws JsonException
     */
    public function expectSettingsUpdateRequest(string $expectedUrl, array $expectedBody, int $statusCode): void
    {
        $this->wireMock->stubFor(
            WireMock::put(
                WireMock::urlEqualTo(
                    $expectedUrl
                )
            )->withRequestBody(
                WireMock::equalToJson(
                    json_encode($expectedBody, 4194304),
                    true,
                    false
                )
            )->willReturn(
                WireMock::aResponse()
                    ->withStatus($statusCode)
            )
        );
    }

    /**
     * @throws JsonException
     */
    public function expectSettingsGetRequest(
        string $expectedUrl,
        string $apiKey,
        array $responseBody,
        int $responseStatusCode
    ): void {
        $this->wireMock->stubFor(
            WireMock::get(
                WireMock::urlEqualTo(
                    $expectedUrl
                )
            )->withHeader(
                'Authorization',
                WireMock::equalTo('Bearer ' . $apiKey)
            )->willReturn(
                WireMock::aResponse()
                    ->withStatus($responseStatusCode)
                    ->withBody(
                        json_encode($responseBody, 4194304)
                    )
            )
        );
    }

    /**
     * @throws JsonException
     */
    public function expectPackagistRequest(string $expectedUrl, array $responseBody, int $responseStatusCode): void
    {
        $this->wireMock->stubFor(
            WireMock::get(
                WireMock::urlEqualTo(
                    $expectedUrl
                )
            )->willReturn(
                WireMock::aResponse()
                    ->withStatus($responseStatusCode)
                    ->withBody(
                        json_encode($responseBody, 4194304)
                    )
            )
        );
    }

    public function expectJobCreateRequest(array $body, int $responseCode, array $responseBody): void
    {
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

    public function expectJobStartRequest(int $liltJobId, int $responseCode): void
    {
        $this->wireMock->stubFor(
            WireMock::post(
                WireMock::urlEqualTo(
                    sprintf('/api/v1.0/jobs/%d/start', $liltJobId)
                )
            )->willReturn(
                WireMock::aResponse()
                    ->withStatus($responseCode)
            )
        );
    }

    public function expectJobGetRequest(int $liltJobId, int $responseCode, ?array $responseBody = null): void
    {
        $response = WireMock::aResponse()
            ->withStatus($responseCode);

        if ($responseBody !== null) {
            $response->withBody(json_encode($responseBody));
        }

        $this->wireMock->stubFor(
            WireMock::get(
                WireMock::urlEqualTo(
                    sprintf('/api/v1.0/jobs/%d', $liltJobId)
                )
            )->willReturn(
                $response
            )
        );
    }

    public function expectTranslationsGetRequest(
        int $liltJobId,
        int $start,
        int $limit,
        int $responseCode,
        ?array $responseBody = null
    ): void {
        $response = WireMock::aResponse()
            ->withStatus($responseCode);

        if ($responseBody !== null) {
            $response->withBody(json_encode($responseBody));
        }

        $realStartValue = $start === 0 ? '00' : (string)$start;

        $this->wireMock->stubFor(
            WireMock::get(
                WireMock::urlEqualTo(
                    sprintf(
                        '/api/v1.0/translations?limit=%d&start=%s&job_id=%d',
                        $limit,
                        $realStartValue,
                        $liltJobId
                    )
                )
            )->willReturn(
                $response
            )
        );
    }

    public function expectTranslationDownloadRequest(
        int $translationId,
        int $responseCode,
        ?array $responseBody = null
    ): void {
        $response = WireMock::aResponse()
            ->withStatus($responseCode);

        if ($responseBody !== null) {
            $response->withBody(json_encode($responseBody));
        }

        $this->wireMock->stubFor(
            WireMock::get(
                WireMock::urlEqualTo(
                    sprintf('/api/v1.0/translations/%d/download', $translationId)
                )
            )->willReturn(
                $response
            )
        );
    }

    public function expectTranslationGetRequest(
        int $translationId,
        int $responseCode,
        ?array $responseBody = null
    ): void {
        $response = WireMock::aResponse()
            ->withStatus($responseCode);

        if ($responseBody !== null) {
            $response->withBody(json_encode($responseBody));
        }

        $this->wireMock->stubFor(
            WireMock::get(
                WireMock::urlEqualTo(
                    sprintf('/api/v1.0/translations/%d', $translationId)
                )
            )->willReturn(
                $response
            )
        );
    }

    public function expectAllRequestsAreMatched(): void
    {
        $unmatched = $this->wireMock->findUnmatchedRequests();

        $requests = [];
        foreach ($unmatched->getRequests() as $request) {
            $requests[] = [
                'url' => $request->getMethod() . ' ' . $request->getUrl(),
                'body' => $request->getBody()
            ];
        }

        $this->assertEmpty(
            $unmatched->getRequests(),
            sprintf('Some of requests are unmatched: %s', json_encode($requests, JSON_PRETTY_PRINT))
        );
    }
}
