<?php

declare(strict_types=1);

namespace App\Service\Middleware\Api;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Packagist
{
    private const STABLE_RELEASES_URL = 'https://repo.packagist.org/p2/ajardin/origami.json';
    private const DEV_RELEASES_URL = 'https://repo.packagist.org/p2/ajardin/origami~dev.json';

    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Retrieves the latest "stable" release published on Packagist.
     */
    public function getLatestStableRelease(): ?array
    {
        return $this->getLatestRelease(self::STABLE_RELEASES_URL);
    }

    /**
     * Retrieves the latest "dev" release published on Packagist.
     */
    public function getLatestDevRelease(): ?array
    {
        return $this->getLatestRelease(self::DEV_RELEASES_URL);
    }

    /**
     * Retrieves the latest release according to the given URL.
     */
    private function getLatestRelease(string $url): ?array
    {
        try {
            $response = $this->httpClient->request(Request::METHOD_GET, $url);
            $releases = $response->toArray();
        } catch (TransportExceptionInterface | HttpExceptionInterface | DecodingExceptionInterface $exception) {
            return null;
        }

        return $releases['packages']['ajardin/origami'][0] ?? null;
    }
}
