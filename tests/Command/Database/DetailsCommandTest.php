<?php

declare(strict_types=1);

namespace App\Tests\Command\Database;

use App\Command\Database\DetailsCommand;
use App\Exception\InvalidEnvironmentException;
use App\Service\ApplicationContext;
use App\Service\Middleware\Database;
use App\Tests\TestCommandTrait;
use App\Tests\TestEnvironmentTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\Database\DetailsCommand
 */
final class DetailsCommandTest extends TestCase
{
    use ProphecyTrait;
    use TestCommandTrait;
    use TestEnvironmentTrait;

    public function testItShowsDatabaseDetails(): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);
        $database = $this->prophesize(Database::class);

        $applicationContext
            ->loadEnvironment(Argument::type(InputInterface::class))
            ->shouldBeCalledOnce()
        ;

        $database->getDatabaseType()->shouldBeCalledOnce()->willReturn('postgres');
        $database->getDatabaseVersion()->shouldBeCalledOnce()->willReturn('14-alpine');
        $database->getDatabaseUsername()->shouldBeCalledOnce()->willReturn('postgres');
        $database->getDatabasePassword()->shouldBeCalledOnce()->willReturn('YourPwdShouldBeLongAndSecure');

        $command = new DetailsCommand($applicationContext->reveal(), $database->reveal());

        $commandTester = new CommandTester($command);
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('Type: ', $display);
        static::assertStringContainsString('Version: ', $display);
        static::assertStringContainsString('Username: ', $display);
        static::assertStringContainsString('Password: ', $display);
        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);
        $database = $this->prophesize(Database::class);

        $applicationContext
            ->loadEnvironment(Argument::type(InputInterface::class))
            ->willThrow(InvalidEnvironmentException::class)
        ;

        $command = new DetailsCommand($applicationContext->reveal(), $database->reveal());
        static::assertExceptionIsHandled($command);
    }
}
