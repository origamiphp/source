<?php

declare(strict_types=1);

namespace App\Tests\Middleware\Api;

use App\Middleware\Api\GitHub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * @internal
 *
 * @covers \App\Middleware\Api\GitHub
 */
final class GitHubTest extends TestCase
{
    public function testItReturnsCommitMessageOnExistingReference(): void
    {
        $client = HttpClient::create();
        $github = new GitHub($client);

        static::assertSame(
            'Update to commit https://github.com/ajardin/origami-source/commit/1deb8068fe54202a65da0078a4985e52f3836f97',
            $github->getCommitMessage('0b5192bf11dbf555a6c04db534283da0fb32505b')
        );

        static::assertSame(
            'Update to commit https://github.com/ajardin/origami-source/commit/7d757a74398fc77d4f81dfdd95f14db76fa7aea2',
            $github->getCommitMessage('95b3b7620b16b314240e5c2970aa15a251b0b601')
        );
    }

    public function testItReturnsNullWithUnknownReference(): void
    {
        $client = HttpClient::create();
        $github = new GitHub($client);

        static::assertNull($github->getCommitMessage('azertyuiop'));
    }

    public function testItReturnsNullWhenAnExceptionOccured(): void
    {
        $client = new MockHttpClient([new MockResponse()]);
        $packagist = new GitHub($client);

        static::assertNull($packagist->getCommitMessage('azertyuiop'));
    }
}
