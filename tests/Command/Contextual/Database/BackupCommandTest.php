<?php

declare(strict_types=1);

namespace App\Tests\Command\Contextual\Database;

use App\Command\Contextual\Database\BackupCommand;
use App\Exception\InvalidEnvironmentException;
use App\Helper\CurrentContext;
use App\Middleware\Binary\DockerCompose;
use App\Tests\Command\TestCommandTrait;
use App\Tests\TestLocationTrait;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\Contextual\Database\BackupCommand
 */
final class BackupCommandTest extends WebTestCase
{
    use ProphecyTrait;
    use TestCommandTrait;
    use TestLocationTrait;

    public function testItTriggersTheBackupProcess(): void
    {
        $environment = $this->createEnvironment();
        [$currentContext, $dockerCompose] = $this->prophesizeBackupCommandArguments();

        $currentContext->getEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce()->willReturn($environment);
        $currentContext->setActiveEnvironment($environment)->shouldBeCalledOnce();

        $dockerCompose->backupDatabaseVolume()->shouldBeCalledOnce()->willReturn(true);

        $command = new BackupCommand($currentContext->reveal(), $dockerCompose->reveal());
        static::assertResultIsSuccessful($command, $environment);
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        $environment = $this->createEnvironment();
        [$currentContext, $dockerCompose] = $this->prophesizeBackupCommandArguments();

        $currentContext->getEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce()->willReturn($environment);
        $currentContext->setActiveEnvironment($environment)->shouldBeCalledOnce()->willThrow(InvalidEnvironmentException::class);

        $command = new BackupCommand($currentContext->reveal(), $dockerCompose->reveal());
        static::assertExceptionIsHandled($command);
    }

    /**
     * Prophesizes arguments needed by the \App\Command\Contextual\Database\BackupCommand class.
     */
    private function prophesizeBackupCommandArguments(): array
    {
        return [
            $this->prophesize(CurrentContext::class),
            $this->prophesize(DockerCompose::class),
        ];
    }
}
