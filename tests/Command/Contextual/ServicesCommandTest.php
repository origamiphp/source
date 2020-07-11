<?php

declare(strict_types=1);

namespace App\Tests\Command\Contextual;

use App\Command\Contextual\Services\AbstractServiceCommand;
use App\Command\Contextual\Services\ElasticsearchCommand;
use App\Command\Contextual\Services\MysqlCommand;
use App\Command\Contextual\Services\NginxCommand;
use App\Command\Contextual\Services\PhpCommand;
use App\Command\Contextual\Services\RedisCommand;
use App\Exception\FilesystemException;
use App\Exception\InvalidEnvironmentException;
use App\Helper\CommandExitCode;
use App\Helper\CurrentContext;
use App\Middleware\Binary\DockerCompose;
use App\Tests\TestCommandTrait;
use App\Tests\TestFakeEnvironmentTrait;
use Generator;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
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
final class ServicesCommandTest extends WebTestCase
{
    use ProphecyTrait;
    use TestCommandTrait;
    use TestFakeEnvironmentTrait;

    /**
     * @dataProvider provideServiceDetails
     *
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     */
    public function testItOpensTerminalOnService(string $classname, string $service, string $user): void
    {
        if (!is_subclass_of($classname, AbstractServiceCommand::class)) {
            throw new RuntimeException("{$classname} is not a subclass of AbstractServiceCommand.");
        }

        $environment = $this->getFakeEnvironment();

        $currentContext = $this->prophesize(CurrentContext::class);
        $dockerCompose = $this->prophesize(DockerCompose::class);

        $currentContext->getEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce()->willReturn($environment);
        $dockerCompose->openTerminal($service, $user)->shouldBeCalledOnce()->willReturn(true);

        $command = new $classname($currentContext->reveal(), $dockerCompose->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    /**
     * @dataProvider provideServiceDetails
     *
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     */
    public function testItGracefullyExitsWhenAnExceptionOccurred(string $classname, string $service, string $user): void
    {
        if (!is_subclass_of($classname, AbstractServiceCommand::class)) {
            throw new RuntimeException("{$classname} is not a subclass of AbstractServiceCommand.");
        }

        $environment = $this->getFakeEnvironment();

        $currentContext = $this->prophesize(CurrentContext::class);
        $dockerCompose = $this->prophesize(DockerCompose::class);

        $currentContext->getEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce()->willReturn($environment);
        $dockerCompose->openTerminal($service, $user)->shouldBeCalledOnce()->willReturn(false);

        $command = new $classname($currentContext->reveal(), $dockerCompose->reveal());
        self::assertExceptionIsHandled($command, 'An error occurred while opening a terminal.');
    }

    public function provideServiceDetails(): Generator
    {
        yield 'elasticsearch' => [ElasticsearchCommand::class, 'elasticsearch', ''];
        yield 'mysql' => [MysqlCommand::class, 'mysql', ''];
        yield 'nginx' => [NginxCommand::class, 'nginx', ''];
        yield 'php' => [PhpCommand::class, 'php', 'www-data:www-data'];
        yield 'redis' => [RedisCommand::class, 'redis', ''];
    }
}
