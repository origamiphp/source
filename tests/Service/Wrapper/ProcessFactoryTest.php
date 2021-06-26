<?php

declare(strict_types=1);

namespace App\Tests\Service\Wrapper;

use App\Service\Wrapper\ProcessFactory;
use App\Service\Wrapper\ProcessProxy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;

/**
 * @internal
 *
 * @covers \App\Service\Wrapper\ProcessFactory
 * @covers \App\Service\Wrapper\ProcessProxy
 */
final class ProcessFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testItRunsBackgroundProcess(): void
    {
        $procesProxy = $this->prophesize(ProcessProxy::class);
        $logger = $this->prophesize(LoggerInterface::class);

        $logger
            ->debug(Argument::type('string'), ['command' => 'php -v'])
            ->shouldBeCalledOnce()
        ;

        $factory = new ProcessFactory($procesProxy->reveal(), $logger->reveal());
        $process = $factory->runBackgroundProcess(['php', '-v']);

        static::assertMatchesRegularExpression('/^PHP \d{1}\.\d{1}\.{1,2}\d/', $process->getOutput());
    }

    public function testItRunsForegroundProcess(): void
    {
        $procesProxy = $this->prophesize(ProcessProxy::class);
        $logger = $this->prophesize(LoggerInterface::class);

        $procesProxy
            ->isTtySupported()
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $logger
            ->debug(Argument::type('string'), ['command' => 'php -v'])
            ->shouldBeCalledOnce()
        ;

        $this->expectOutputRegex('/^PHP \d{1}\.\d{1}\.{1,2}\d/');

        $factory = new ProcessFactory($procesProxy->reveal(), $logger->reveal());
        $factory->runForegroundProcess(['php', '-v']);
    }

    public function testitRunsForegroundProcessFromShellCommandLine(): void
    {
        $procesProxy = $this->prophesize(ProcessProxy::class);
        $logger = $this->prophesize(LoggerInterface::class);

        $procesProxy
            ->isTtySupported()
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $logger
            ->debug(Argument::type('string'), ['command' => 'php -v | grep PHP'])
            ->shouldBeCalledOnce()
        ;

        $this->expectOutputRegex('/^PHP \d{1}\.\d{1}\.{1,2}\d/');

        $factory = new ProcessFactory($procesProxy->reveal(), $logger->reveal());
        $factory->runForegroundProcessFromShellCommandLine('php -v | grep PHP');
    }
}
