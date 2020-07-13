<?php

declare(strict_types=1);

namespace App\Tests\Helper;

use App\Helper\ProcessFactory;
use App\Helper\ProcessProxy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;

/**
 * @internal
 *
 * @covers \App\Helper\ProcessFactory
 * @covers \App\Helper\ProcessProxy
 */
final class ProcessFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testItRunsBackgroundProcess(): void
    {
        $procesProxy = $this->prophesize(ProcessProxy::class);
        $logger = $this->prophesize(LoggerInterface::class);

        $logger->debug(Argument::type('string'), ['command' => 'php -v'])->shouldBeCalledOnce();

        $factory = new ProcessFactory($procesProxy->reveal(), $logger->reveal());
        $process = $factory->runBackgroundProcess(['php', '-v']);

        static::assertStringContainsString('PHP 7.', $process->getOutput());
    }

    public function testItRunsForegroundProcess(): void
    {
        $procesProxy = $this->prophesize(ProcessProxy::class);
        $logger = $this->prophesize(LoggerInterface::class);

        $procesProxy->isTtySupported()->shouldBeCalledOnce()->willReturn(false);
        $logger->debug(Argument::type('string'), ['command' => 'php -v'])->shouldBeCalledOnce();

        $this->expectOutputRegex('/^PHP 7\.\d{1}\.{1,2}\d/');

        $factory = new ProcessFactory($procesProxy->reveal(), $logger->reveal());
        $factory->runForegroundProcess(['php', '-v']);
    }

    public function testitRunsForegroundProcessFromShellCommandLine(): void
    {
        $procesProxy = $this->prophesize(ProcessProxy::class);
        $logger = $this->prophesize(LoggerInterface::class);

        $procesProxy->isTtySupported()->shouldBeCalledOnce()->willReturn(false);
        $logger->debug(Argument::type('string'), ['command' => 'php -v | grep PHP'])->shouldBeCalledOnce();

        $this->expectOutputRegex('/^PHP 7\.\d{1}\.{1,2}\d/');

        $factory = new ProcessFactory($procesProxy->reveal(), $logger->reveal());
        $factory->runForegroundProcessFromShellCommandLine('php -v | grep PHP');
    }
}
