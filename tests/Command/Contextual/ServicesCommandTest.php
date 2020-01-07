<?php

declare(strict_types=1);

namespace App\Tests\Command\Contextual;

use App\Command\Contextual\Services\ElasticsearchCommand;
use App\Command\Contextual\Services\MysqlCommand;
use App\Command\Contextual\Services\NginxCommand;
use App\Command\Contextual\Services\PhpCommand;
use App\Command\Contextual\Services\RedisCommand;
use App\Exception\InvalidEnvironmentException;
use App\Helper\CommandExitCode;
use App\Helper\ProcessProxy;
use App\Middleware\Binary\DockerCompose;
use App\Middleware\SystemManager;
use App\Tests\TestCustomCommandsTrait;
use App\Tests\TestFakeEnvironmentTrait;
use Generator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
final class ServicesCommandTest extends TestCase
{
    use TestCustomCommandsTrait;
    use TestFakeEnvironmentTrait;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->systemManager = $this->prophesize(SystemManager::class);
        $this->validator = $this->prophesize(ValidatorInterface::class);
        $this->dockerCompose = $this->prophesize(DockerCompose::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->processProxy = $this->prophesize(ProcessProxy::class);

        putenv('COLUMNS=120'); // Required by tests running with Github Actions
    }

    /**
     * @dataProvider provideServiceDetails
     *
     * @throws InvalidEnvironmentException
     */
    public function testItOpensTerminalOnService(string $classname, string $service, string $user): void
    {
        $environment = $this->getFakeEnvironment();

        $this->systemManager->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);
        $this->dockerCompose->setActiveEnvironment($environment)->shouldBeCalledOnce();
        $this->dockerCompose->openTerminal($service, $user)->shouldBeCalledOnce()->willReturn(true);

        $command = new $classname(
            $this->systemManager->reveal(),
            $this->validator->reveal(),
            $this->dockerCompose->reveal(),
            $this->eventDispatcher->reveal(),
            $this->processProxy->reveal(),
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    /**
     * @dataProvider provideServiceDetails
     *
     * @throws InvalidEnvironmentException
     */
    public function testItGracefullyExitsWhenAnExceptionOccurred(string $classname, string $service, string $user): void
    {
        $environment = $this->getFakeEnvironment();

        $this->systemManager->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);
        $this->dockerCompose->setActiveEnvironment($environment)->shouldBeCalledOnce();
        $this->dockerCompose->openTerminal($service, $user)->shouldBeCalledOnce()->willReturn(false);

        $command = new $classname(
            $this->systemManager->reveal(),
            $this->validator->reveal(),
            $this->dockerCompose->reveal(),
            $this->eventDispatcher->reveal(),
            $this->processProxy->reveal(),
        );

        self::assertExceptionIsHandled($command, 'An error occurred while opening a terminal.');
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
