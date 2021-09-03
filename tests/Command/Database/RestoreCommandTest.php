<?php

declare(strict_types=1);

namespace App\Tests\Command\Database;

use App\Command\Database\RestoreCommand;
use App\Exception\DatabaseException;
use App\Service\CurrentContext;
use App\Service\Middleware\Database;
use App\Tests\TestCommandTrait;
use App\Tests\TestEnvironmentTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\Database\RestoreCommand
 */
final class RestoreCommandTest extends TestCase
{
    use ProphecyTrait;
    use TestCommandTrait;
    use TestEnvironmentTrait;

    public function testItExecutesProcessSuccessfully(): void
    {
        $currentContext = $this->prophesize(CurrentContext::class);
        $database = $this->prophesize(Database::class);

        $environment = $this->createEnvironment();

        $currentContext
            ->loadEnvironment(Argument::type(InputInterface::class))
            ->shouldBeCalledOnce()
        ;

        $currentContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $database
            ->restore(Argument::type('string'))
            ->shouldBeCalledOnce()
        ;

        $command = new RestoreCommand($currentContext->reveal(), $database->reveal());
        static::assertResultIsSuccessful($command, $environment);
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        $currentContext = $this->prophesize(CurrentContext::class);
        $database = $this->prophesize(Database::class);

        $environment = $this->createEnvironment();

        $currentContext
            ->loadEnvironment(Argument::type(InputInterface::class))
            ->shouldBeCalledOnce()
        ;

        $currentContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $database
            ->restore(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willThrow(DatabaseException::class)
        ;

        $command = new RestoreCommand($currentContext->reveal(), $database->reveal());
        static::assertExceptionIsHandled($command);
    }
}
