<?php

declare(strict_types=1);

namespace App\Tests\Service\Middleware\Api;

use App\Service\Middleware\Api\Packagist;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * @internal
 *
 * @covers \App\Service\Middleware\Api\Packagist
 */
final class PackagistTest extends TestCase
{
    public function testItRetrievesTheLatestStableRelease(): void
    {
        $client = HttpClient::create();
        $packagist = new Packagist($client);

        $result = $packagist->getLatestStableRelease();

        static::assertIsArray($result);
        static::assertArrayHasKey('version', $result);
        static::assertArrayHasKey('version_normalized', $result);
    }

    public function testItRetrievesTheLatestDevRelease(): void
    {
        $client = HttpClient::create();
        $packagist = new Packagist($client);

        $result = $packagist->getLatestStableRelease();

        static::assertIsArray($result);
        static::assertArrayHasKey('source', $result);
        static::assertArrayHasKey('reference', $result['source']);
    }

    public function testItReturnsNullWhenAnExceptionOccured(): void
    {
        $client = new MockHttpClient([new MockResponse()]);
        $packagist = new Packagist($client);

        static::assertNull($packagist->getLatestStableRelease());
        static::assertNull($packagist->getLatestDevRelease());
    }
}
