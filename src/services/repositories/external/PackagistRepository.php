<?php

declare(strict_types=1);

namespace lilthq\craftliltplugin\services\repositories\external;

use GuzzleHttp\Client;
use lilthq\craftliltplugin\Craftliltplugin;

class PackagistRepository
{
    private $client;

    public function __construct(string $baseUri = 'https://packagist.org')
    {
        $this->client = new Client(['base_uri' => $baseUri]);
    }

    public function getLatestPluginVersion(): ?string
    {
        $response = $this->client->request('GET', '/packages/lilt/craft-lilt-plugin.json');

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);

        if (!$data || !isset($data['package']['versions'])) {
            return null;
        }
        $currentVersionParts = explode('.', Craftliltplugin::getInstance()->getVersion());

        if (empty($currentVersionParts)) {
            return null;
        }

        $latestVersion = null;
        foreach ($data['package']['versions'] as $version) {
            $versionParts = explode('.', $version['version']);
            if (
                count($versionParts) < 3
                || $currentVersionParts[0] !== $versionParts[0]
                || !preg_match('/^\d+\.\d+\.\d+$/', $version['version'])
            ) {
                continue;
            }

            if (empty($latestVersion)) {
                $latestVersion = $version['version'];
                continue;
            }

            if (version_compare($version['version'], $latestVersion, '>')) {
                $latestVersion = $version['version'];
            }
        }

        return $latestVersion;
    }
}
