<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\Middleware\Api\GitHub;
use App\Service\Middleware\Api\Packagist;
use App\Service\Middleware\Wrapper\OrigamiStyle;
use App\Service\ReleaseChecker;
use App\ValueObject\ApplicationVersion;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @internal
 *
 * @covers \App\Service\ReleaseChecker
 */
final class ReleaseCheckerTest extends TestCase
{
    use ProphecyTrait;

    public function testItDoesNothingWithDefaultVersion(): void
    {
        $application = $this->prophesize(ApplicationVersion::class);
        $packagist = $this->prophesize(Packagist::class);
        $github = $this->prophesize(GitHub::class);

        $application
            ->isDefault()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $io = $this->prophesize(OrigamiStyle::class);

        $releaseChecker = new ReleaseChecker($application->reveal(), $packagist->reveal(), $github->reveal());
        $releaseChecker->validate($io->reveal());
    }

    public function testItDetectsNextStableRelease(): void
    {
        $application = $this->prophesize(ApplicationVersion::class);
        $packagist = $this->prophesize(Packagist::class);
        $github = $this->prophesize(GitHub::class);

        $application
            ->isDefault()
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $application
            ->getValue()
            ->shouldBeCalledOnce()
            ->willReturn('1.2.3')
        ;

        $packagist
            ->getLatestStableRelease()
            ->shouldBeCalledOnce()
            ->willReturn(['version' => 'v2.0.0', 'version_normalized' => '2.0.0.0'])
        ;

        $io = $this->prophesize(OrigamiStyle::class);
        $io
            ->text(Argument::type('string'))
            ->shouldBeCalledOnce()
        ;

        $releaseChecker = new ReleaseChecker($application->reveal(), $packagist->reveal(), $github->reveal());
        $releaseChecker->validate($io->reveal());
    }

    public function testItDetectsSameStableRelease(): void
    {
        $application = $this->prophesize(ApplicationVersion::class);
        $packagist = $this->prophesize(Packagist::class);
        $github = $this->prophesize(GitHub::class);

        $application
            ->isDefault()
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $application
            ->getValue()
            ->shouldBeCalledOnce()
            ->willReturn('2.0.0')
        ;

        $packagist
            ->getLatestStableRelease()
            ->shouldBeCalledOnce()
            ->willReturn(['version' => 'v2.0.0', 'version_normalized' => '2.0.0.0'])
        ;

        $io = $this->prophesize(OrigamiStyle::class);
        $io
            ->text(Argument::type('string'))
            ->shouldNotBeCalled()
        ;

        $releaseChecker = new ReleaseChecker($application->reveal(), $packagist->reveal(), $github->reveal());
        $releaseChecker->validate($io->reveal());
    }

    public function testItDoesNothingWithStableReleaseAndPackagistException(): void
    {
        $application = $this->prophesize(ApplicationVersion::class);
        $packagist = $this->prophesize(Packagist::class);
        $github = $this->prophesize(GitHub::class);

        $application
            ->isDefault()
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $application
            ->getValue()
            ->shouldBeCalledOnce()
            ->willReturn('1.2.3')
        ;

        $packagist
            ->getLatestStableRelease()
            ->shouldBeCalledOnce()
            ->willReturn(null)
        ;

        $io = $this->prophesize(OrigamiStyle::class);
        $io
            ->text(Argument::type('string'))
            ->shouldNotBeCalled()
        ;

        $releaseChecker = new ReleaseChecker($application->reveal(), $packagist->reveal(), $github->reveal());
        $releaseChecker->validate($io->reveal());
    }

    public function testItDetectsNextDevRelease(): void
    {
        $application = $this->prophesize(ApplicationVersion::class);
        $packagist = $this->prophesize(Packagist::class);
        $github = $this->prophesize(GitHub::class);

        $application
            ->isDefault()
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $application
            ->getValue()
            ->shouldBeCalledOnce()
            ->willReturn('2.0.0@foobar')
        ;

        $packagist
            ->getLatestDevRelease()
            ->shouldBeCalledOnce()
            ->willReturn(['source' => ['reference' => 'azertyuiopqsdfghjklmwxcvbn']])
        ;

        $github
            ->getCommitMessage('azertyuiopqsdfghjklmwxcvbn')
            ->shouldBeCalledOnce()
            ->willReturn('Update to commit https://github.com/ajardin/origami-source/commit/123456123456123456123456')
        ;

        $io = $this->prophesize(OrigamiStyle::class);
        $io
            ->text(Argument::type('string'))
            ->shouldBeCalledOnce()
        ;

        $releaseChecker = new ReleaseChecker($application->reveal(), $packagist->reveal(), $github->reveal());
        $releaseChecker->validate($io->reveal());
    }

    public function testItDetectsSameDevRelease(): void
    {
        $application = $this->prophesize(ApplicationVersion::class);
        $packagist = $this->prophesize(Packagist::class);
        $github = $this->prophesize(GitHub::class);

        $application
            ->isDefault()
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $application
            ->getValue()
            ->shouldBeCalledOnce()
            ->willReturn('2.0.0@123456')
        ;

        $packagist
            ->getLatestDevRelease()
            ->shouldBeCalledOnce()
            ->willReturn(['source' => ['reference' => 'azertyuiopqsdfghjklmwxcvbn']])
        ;

        $github
            ->getCommitMessage('azertyuiopqsdfghjklmwxcvbn')
            ->shouldBeCalledOnce()
            ->willReturn('Update to commit https://github.com/ajardin/origami-source/commit/123456123456123456123456')
        ;

        $io = $this->prophesize(OrigamiStyle::class);
        $io
            ->text(Argument::type('string'))
            ->shouldNotBeCalled()
        ;

        $releaseChecker = new ReleaseChecker($application->reveal(), $packagist->reveal(), $github->reveal());
        $releaseChecker->validate($io->reveal());
    }

    public function testItDoesNothingWithDevReleaseAndPackagistException(): void
    {
        $application = $this->prophesize(ApplicationVersion::class);
        $packagist = $this->prophesize(Packagist::class);
        $github = $this->prophesize(GitHub::class);

        $application
            ->isDefault()
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $application
            ->getValue()
            ->shouldBeCalledOnce()
            ->willReturn('2.0.0@foobar')
        ;

        $packagist
            ->getLatestDevRelease()
            ->shouldBeCalledOnce()
            ->willReturn(null)
        ;

        $io = $this->prophesize(OrigamiStyle::class);
        $io
            ->text(Argument::type('string'))
            ->shouldNotBeCalled()
        ;

        $releaseChecker = new ReleaseChecker($application->reveal(), $packagist->reveal(), $github->reveal());
        $releaseChecker->validate($io->reveal());
    }

    public function testItDoesNothingWithDevReleaseAndGithubException(): void
    {
        $application = $this->prophesize(ApplicationVersion::class);
        $packagist = $this->prophesize(Packagist::class);
        $github = $this->prophesize(GitHub::class);

        $application
            ->isDefault()
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $application
            ->getValue()
            ->shouldBeCalledOnce()
            ->willReturn('2.0.0@foobar')
        ;

        $packagist
            ->getLatestDevRelease()
            ->shouldBeCalledOnce()
            ->willReturn(['source' => ['reference' => 'azertyuiopqsdfghjklmwxcvbn']])
        ;

        $github
            ->getCommitMessage('azertyuiopqsdfghjklmwxcvbn')
            ->shouldBeCalledOnce()
            ->willReturn(null)
        ;

        $io = $this->prophesize(OrigamiStyle::class);
        $io
            ->text(Argument::type('string'))
            ->shouldNotBeCalled()
        ;

        $releaseChecker = new ReleaseChecker($application->reveal(), $packagist->reveal(), $github->reveal());
        $releaseChecker->validate($io->reveal());
    }
}
