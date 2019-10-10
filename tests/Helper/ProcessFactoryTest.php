<?php

declare(strict_types=1);

namespace App\Tests\Helper;

use App\Helper\ProcessFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;

/**
 * @internal
 *
 * @covers \App\Helper\ProcessFactory
 */
final class ProcessFactoryTest extends TestCase
{
    public function testItRunsBackgroundProcess(): void
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $logger->debug(Argument::type('string'), ['command' => 'php -v'])->shouldBeCalledOnce();

        $factory = new ProcessFactory($logger->reveal());
        $process = $factory->runBackgroundProcess(['php', '-v']);

        static::assertStringContainsString('PHP 7.', $process->getOutput());
    }

    public function testItRunsForegroundProcess(): void
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $logger->debug(Argument::type('string'), ['command' => 'php -v'])->shouldBeCalledOnce();

        $this->expectOutputRegex('/^PHP 7\.\d{1}\.{1,2}\d/');

        $factory = new ProcessFactory($logger->reveal());
        $factory->runForegroundProcess(['php', '-v']);
    }

    public function testitRunsForegroundProcessFromShellCommandLine(): void
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $logger->debug(Argument::type('string'), ['command' => 'php -v | grep PHP'])->shouldBeCalledOnce();

        $this->expectOutputRegex('/^PHP 7\.\d{1}\.{1,2}\d/');

        $factory = new ProcessFactory($logger->reveal());
        $factory->runForegroundProcessFromShellCommandLine('php -v | grep PHP');
    }
}
