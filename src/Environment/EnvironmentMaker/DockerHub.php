<?php

declare(strict_types=1);

namespace App\Environment\EnvironmentMaker;

use App\Exception\DockerHubException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class DockerHub
{
    public const DEFAULT_IMAGE_VERSION = 'latest';
    private const API_ENDPOINT = 'https://hub.docker.com/v2/repositories/%s/tags';

    /** @var HttpClientInterface */
    private $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Retrieves the tags associated to the given image.
     *
     * @throws DockerHubException
     * @throws TransportExceptionInterface
     */
    public function getImageTags(string $image): array
    {
        $response = $this->httpClient->request(Request::METHOD_GET, sprintf(self::API_ENDPOINT, $image));
        $parsedResponse = $this->parseResponse($response);

        if (\array_key_exists('results', $parsedResponse) && \is_array($parsedResponse['results'])) {
            $tags = array_column($parsedResponse['results'], 'name');
            rsort($tags);

            return $tags;
        }

        throw new DockerHubException('Unable to retrieve the image tags.');
    }

    /**
     * Analyzes the Docker Hub API response by checking the status code and by decoding the JSON content.
     *
     * @throws DockerHubException
     */
    private function parseResponse(ResponseInterface $response): array
    {
        try {
            return $response->toArray(true);
        } catch (ExceptionInterface $exception) {
            throw new DockerHubException(
                sprintf("Unable to parse the Docker Hub API response.\n%s", $exception->getMessage())
            );
        }
    }
}
