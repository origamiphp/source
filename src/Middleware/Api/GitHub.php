<?php

declare(strict_types=1);

namespace App\Middleware\Api;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GitHub
{
    private const GITHUB_COMMIT_URL = 'https://api.github.com/repos/ajardin/origami/commits/{ref}';

    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Retrieves the commit message associated to the given GitHub reference.
     */
    public function getCommitMessage(string $reference): ?string
    {
        $url = str_replace('{ref}', $reference, self::GITHUB_COMMIT_URL);

        try {
            $response = $this->httpClient->request(Request::METHOD_GET, $url);
            $details = $response->toArray();
        } catch (HttpExceptionInterface | TransportExceptionInterface | DecodingExceptionInterface $exception) {
            return null;
        }

        return $details['commit']['message'] ?? null;
    }
}
