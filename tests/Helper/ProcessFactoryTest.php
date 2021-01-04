<?php

declare(strict_types=1);

namespace App\Tests\Helper;

use App\Helper\ProcessFactory;
use App\Helper\ProcessProxy;
use App\Tests\CustomProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;

/**
 * @internal
 *
 * @covers \App\Helper\ProcessFactory
 * @covers \App\Helper\ProcessProxy
 */
final class ProcessFactoryTest extends TestCase
{
    use CustomProphecyTrait;

    public function testItRunsBackgroundProcess(): void
    {
        [$procesProxy, $logger] = $this->prophesizeObjectArguments();

        $logger->debug(Argument::type('string'), ['command' => 'php -v'])->shouldBeCalledOnce();

        $factory = new ProcessFactory($procesProxy->reveal(), $logger->reveal());
        $process = $factory->runBackgroundProcess(['php', '-v']);

        static::assertMatchesRegularExpression('/^PHP \d{1}\.\d{1}\.{1,2}\d/', $process->getOutput());
    }

    public function testItRunsForegroundProcess(): void
    {
        [$procesProxy, $logger] = $this->prophesizeObjectArguments();

        $procesProxy->isTtySupported()->shouldBeCalledOnce()->willReturn(false);
        $logger->debug(Argument::type('string'), ['command' => 'php -v'])->shouldBeCalledOnce();

        $this->expectOutputRegex('/^PHP \d{1}\.\d{1}\.{1,2}\d/');

        $factory = new ProcessFactory($procesProxy->reveal(), $logger->reveal());
        $factory->runForegroundProcess(['php', '-v']);
    }

    public function testitRunsForegroundProcessFromShellCommandLine(): void
    {
        [$procesProxy, $logger] = $this->prophesizeObjectArguments();

        $procesProxy->isTtySupported()->shouldBeCalledOnce()->willReturn(false);
        $logger->debug(Argument::type('string'), ['command' => 'php -v | grep PHP'])->shouldBeCalledOnce();

        $this->expectOutputRegex('/^PHP \d{1}\.\d{1}\.{1,2}\d/');

        $factory = new ProcessFactory($procesProxy->reveal(), $logger->reveal());
        $factory->runForegroundProcessFromShellCommandLine('php -v | grep PHP');
    }

    /**
     * {@inheritdoc}
     */
    protected function prophesizeObjectArguments(): array
    {
        return [
            $this->prophesize(ProcessProxy::class),
            $this->prophesize(LoggerInterface::class),
        ];
    }
}
