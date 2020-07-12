<?php

declare(strict_types=1);

namespace App\Tests\Command\Contextual;

use App\Command\Contextual\Services\AbstractServiceCommand;
use App\Command\Contextual\Services\ElasticsearchCommand;
use App\Command\Contextual\Services\MysqlCommand;
use App\Command\Contextual\Services\NginxCommand;
use App\Command\Contextual\Services\PhpCommand;
use App\Command\Contextual\Services\RedisCommand;
use App\Helper\CommandExitCode;
use Generator;
use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\Contextual\Services\AbstractServiceCommand
 * @covers \App\Command\Contextual\Services\ElasticsearchCommand
 * @covers \App\Command\Contextual\Services\MysqlCommand
 * @covers \App\Command\Contextual\Services\NginxCommand
 * @covers \App\Command\Contextual\Services\PhpCommand
 * @covers \App\Command\Contextual\Services\RedisCommand
 *
 * @uses \App\Event\AbstractEnvironmentEvent
 */
final class ServicesCommandTest extends AbstractContextualCommandWebTestCase
{
    /**
     * @dataProvider provideServiceDetails
     */
    public function testItOpensTerminalOnService(string $classname, string $service, string $user): void
    {
        $environment = $this->getFakeEnvironment();

        (new MethodProphecy($this->currentContext, 'getEnvironment', [Argument::type(InputInterface::class)]))
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        (new MethodProphecy($this->dockerCompose, 'openTerminal', [$service, $user]))
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $commandTester = new CommandTester($this->getCommand($classname));
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    /**
     * @dataProvider provideServiceDetails
     */
    public function testItGracefullyExitsWhenAnExceptionOccurred(string $classname, string $service, string $user): void
    {
        $environment = $this->getFakeEnvironment();

        (new MethodProphecy($this->currentContext, 'getEnvironment', [Argument::type(InputInterface::class)]))
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        (new MethodProphecy($this->dockerCompose, 'openTerminal', [$service, $user]))
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        self::assertExceptionIsHandled($this->getCommand($classname), 'An error occurred while opening a terminal.');
    }

    public function provideServiceDetails(): Generator
    {
        yield 'elasticsearch' => [ElasticsearchCommand::class, 'elasticsearch', ''];
        yield 'mysql' => [MysqlCommand::class, 'mysql', ''];
        yield 'nginx' => [NginxCommand::class, 'nginx', ''];
        yield 'php' => [PhpCommand::class, 'php', 'www-data:www-data'];
        yield 'redis' => [RedisCommand::class, 'redis', ''];
    }

    /**
     * Retrieves the \App\Command\Contextual\Services\AbstractServiceCommand child instance to use within the tests.
     */
    private function getCommand(string $classname): AbstractServiceCommand
    {
        if (!is_subclass_of($classname, AbstractServiceCommand::class)) {
            throw new RuntimeException("{$classname} is not a subclass of AbstractServiceCommand.");
        }

        return new $classname($this->currentContext->reveal(), $this->dockerCompose->reveal());
    }
}
