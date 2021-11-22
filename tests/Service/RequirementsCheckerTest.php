<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Exception\MissingRequirementException;
use App\Service\RequirementsChecker;
use App\Service\Wrapper\OrigamiStyle;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Process\ExecutableFinder;

/**
 * @internal
 *
 * @covers \App\Service\RequirementsChecker
 */
final class RequirementsCheckerTest extends TestCase
{
    use ProphecyTrait;

    public function testItDisplaysNothingWithNormalOutput(): void
    {
        $executableFinder = $this->prophesize(ExecutableFinder::class);
        $io = $this->prophesize(OrigamiStyle::class);

        $executableFinder
            ->find('docker')
            ->shouldBeCalledOnce()
            ->willReturn('/usr/local/bin/docker')
        ;

        $executableFinder
            ->find('mkcert')
            ->shouldBeCalledOnce()
            ->willReturn('/usr/local/bin/mkcert')
        ;

        $io
            ->title(Argument::type('string'))
            ->shouldNotBeCalled()
        ;

        $io
            ->listing(Argument::type('array'))
            ->shouldNotBeCalled()
        ;

        $requirementsChecker = new RequirementsChecker($executableFinder->reveal());
        $requirementsChecker->validate($io->reveal(), false);
    }

    public function testItDetectsMissingMandatoryBinary(): void
    {
        $executableFinder = $this->prophesize(ExecutableFinder::class);
        $io = $this->prophesize(OrigamiStyle::class);

        $executableFinder
            ->find('docker')
            ->shouldBeCalledOnce()
            ->willReturn(null)
        ;

        $executableFinder
            ->find('mkcert')
            ->shouldBeCalledOnce()
            ->willReturn('/usr/local/bin/mkcert')
        ;

        $io
            ->title(Argument::type('string'))
            ->shouldBeCalledOnce()
        ;

        $io
            ->listing([
                '❌ docker - A self-sufficient runtime for containers.',
                '✅ mkcert - A simple zero-config tool to make locally trusted development certificates.',
            ])
            ->shouldBeCalledOnce()
        ;

        $this->expectException(MissingRequirementException::class);

        $requirementsChecker = new RequirementsChecker($executableFinder->reveal());
        $requirementsChecker->validate($io->reveal(), true);
    }

    public function testItDetectsMissingNonMandatoryBinary(): void
    {
        $executableFinder = $this->prophesize(ExecutableFinder::class);
        $io = $this->prophesize(OrigamiStyle::class);

        $executableFinder
            ->find('docker')
            ->shouldBeCalledOnce()
            ->willReturn('/usr/local/bin/docker')
        ;

        $executableFinder
            ->find('mkcert')
            ->shouldBeCalledOnce()
            ->willReturn(null)
        ;

        $io
            ->title(Argument::type('string'))
            ->shouldBeCalledOnce()
        ;

        $io
            ->listing([
                '✅ docker - A self-sufficient runtime for containers.',
                '❌ mkcert - A simple zero-config tool to make locally trusted development certificates.',
            ])
            ->shouldBeCalledOnce()
        ;

        $requirementsChecker = new RequirementsChecker($executableFinder->reveal());
        $requirementsChecker->validate($io->reveal(), true);
    }
}
