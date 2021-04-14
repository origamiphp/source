<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\Services\AbstractServiceCommand;
use App\Command\Services\ElasticsearchCommand;
use App\Command\Services\MysqlCommand;
use App\Command\Services\NginxCommand;
use App\Command\Services\PhpCommand;
use App\Command\Services\RedisCommand;
use App\Helper\CurrentContext;
use App\Middleware\Binary\Docker;
use App\Tests\CustomProphecyTrait;
use App\Tests\TestCommandTrait;
use App\Tests\TestLocationTrait;
use Generator;
use Prophecy\Argument;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\Services\AbstractServiceCommand
 * @covers \App\Command\Services\ElasticsearchCommand
 * @covers \App\Command\Services\MysqlCommand
 * @covers \App\Command\Services\NginxCommand
 * @covers \App\Command\Services\PhpCommand
 * @covers \App\Command\Services\RedisCommand
 *
 * @uses \App\Event\AbstractEnvironmentEvent
 */
final class ServicesCommandTest extends WebTestCase
{
    use CustomProphecyTrait;
    use TestCommandTrait;
    use TestLocationTrait;

    /**
     * @dataProvider provideServiceDetails
     */
    public function testItOpensTerminalOnService(string $classname, string $service, string $user): void
    {
        if (!is_subclass_of($classname, AbstractServiceCommand::class)) {
            throw new RuntimeException("{$classname} is not a subclass of AbstractServiceCommand.");
        }

        $environment = $this->createEnvironment();

        [$currentContext, $docker] = $this->prophesizeObjectArguments();

        $currentContext->getEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce()->willReturn($environment);
        $currentContext->setActiveEnvironment($environment)->shouldBeCalledOnce();
        $docker->openTerminal($service, $user)->shouldBeCalledOnce()->willReturn(true);

        $command = new $classname($currentContext->reveal(), $docker->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    /**
     * @dataProvider provideServiceDetails
     */
    public function testItGracefullyExitsWhenAnExceptionOccurred(string $classname, string $service, string $user): void
    {
        if (!is_subclass_of($classname, AbstractServiceCommand::class)) {
            throw new RuntimeException("{$classname} is not a subclass of AbstractServiceCommand.");
        }

        $environment = $this->createEnvironment();

        [$currentContext, $docker] = $this->prophesizeObjectArguments();

        $currentContext->getEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce()->willReturn($environment);
        $currentContext->setActiveEnvironment($environment)->shouldBeCalledOnce();
        $docker->openTerminal($service, $user)->shouldBeCalledOnce()->willReturn(false);

        $command = new $classname($currentContext->reveal(), $docker->reveal());
        self::assertExceptionIsHandled($command);
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
     * {@inheritdoc}
     */
    protected function prophesizeObjectArguments(): array
    {
        return [
            $this->prophesize(CurrentContext::class),
            $this->prophesize(Docker::class),
        ];
    }
}
