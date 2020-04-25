<?php

declare(strict_types=1);

namespace App\Tests\Command\Contextual;

use App\Command\Contextual\Services\ElasticsearchCommand;
use App\Command\Contextual\Services\MysqlCommand;
use App\Command\Contextual\Services\NginxCommand;
use App\Command\Contextual\Services\PhpCommand;
use App\Command\Contextual\Services\RedisCommand;
use App\Helper\CommandExitCode;
use App\Tests\Command\AbstractCommandWebTestCase;
use App\Tests\TestFakeEnvironmentTrait;
use Generator;
use Prophecy\Prophecy\MethodProphecy;
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
final class ServicesCommandTest extends AbstractCommandWebTestCase
{
    use TestFakeEnvironmentTrait;

    /**
     * @dataProvider provideServiceDetails
     */
    public function testItOpensTerminalOnService(string $classname, string $service, string $user): void
    {
        $environment = $this->getFakeEnvironment();

        (new MethodProphecy($this->database, 'getActiveEnvironment', []))
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        (new MethodProphecy($this->dockerCompose, 'setActiveEnvironment', [$environment]))
            ->shouldBeCalledOnce()
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

        (new MethodProphecy($this->database, 'getActiveEnvironment', []))
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        (new MethodProphecy($this->dockerCompose, 'setActiveEnvironment', [$environment]))
            ->shouldBeCalledOnce()
        ;

        (new MethodProphecy($this->dockerCompose, 'openTerminal', [$service, $user]))
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        self::assertExceptionIsHandled($this->getCommand($classname), 'An error occurred while opening a terminal.');
    }

    public function provideServiceDetails(): Generator
    {
        yield [ElasticsearchCommand::class, 'elasticsearch', ''];
        yield [MysqlCommand::class, 'mysql', ''];
        yield [NginxCommand::class, 'nginx', ''];
        yield [PhpCommand::class, 'php', 'www-data:www-data'];
        yield [RedisCommand::class, 'redis', ''];
    }
}
