<?php

declare(strict_types=1);

namespace App\Tests\Middleware;

use App\Exception\DockerHubException;
use App\Middleware\DockerHub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 *
 * @covers \App\Middleware\DockerHub
 */
final class DockerHubTest extends TestCase
{
    public function testItRetrievesImageTags(): void
    {
        $successResponse = new MockResponse($this->getFakeSuccessfullyResponse(), ['http_code' => Response::HTTP_OK]);
        $httpClient = new MockHttpClient($successResponse);

        $dockerHub = new DockerHub($httpClient);
        $imageTags = $dockerHub->getImageTags('origami');

        static::assertSame(['foo', 'bar', 'latest'], $imageTags);
    }

    public function testItManagesResultsException(): void
    {
        $successResponse = new MockResponse('', ['http_code' => Response::HTTP_OK]);
        $httpClient = new MockHttpClient($successResponse);

        $this->expectException(DockerHubException::class);

        $dockerHub = new DockerHub($httpClient);
        $dockerHub->getImageTags('origami');
    }

    public function testItManagesParseException(): void
    {
        $successResponse = new MockResponse('', ['http_code' => Response::HTTP_BAD_GATEWAY]);
        $httpClient = new MockHttpClient($successResponse);

        $this->expectException(DockerHubException::class);

        $dockerHub = new DockerHub($httpClient);
        $dockerHub->getImageTags('origami');
    }

    /**
     * Retrieves a response based on the Docker Hub API format.
     */
    private function getFakeSuccessfullyResponse(): string
    {
        return <<<'BODY'
{
  "count": 3,
  "next": null,
  "previous": null,
  "results": [
    {
      "creator": null,
      "id": null,
      "image_id": null,
      "images": [
        {}
      ],
      "last_updated": null,
      "last_updater": null,
      "last_updater_username": null,
      "name": "foo",
      "repository": null,
      "full_size": null,
      "v2": true
    },
    {
      "creator": null,
      "id": null,
      "image_id": null,
      "images": [
        {}
      ],
      "last_updated": null,
      "last_updater": null,
      "last_updater_username": null,
      "name": "bar",
      "repository": null,
      "full_size": null,
      "v2": true
    },
    {
      "creator": null,
      "id": null,
      "image_id": null,
      "images": [
        {}
      ],
      "last_updated": null,
      "last_updater": null,
      "last_updater_username": null,
      "name": "latest",
      "repository": null,
      "full_size": null,
      "v2": true
    }
  ]
}
BODY;
    }
}
