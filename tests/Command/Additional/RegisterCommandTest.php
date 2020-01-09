<?php

declare(strict_types=1);

namespace App\Tests\Command\Additional;

use App\Command\Additional\RegisterCommand;
use App\Entity\Environment;
use App\Exception\InvalidEnvironmentException;
use App\Helper\CommandExitCode;
use App\Tests\AbstractCommandWebTestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\Additional\RegisterCommand
 */
final class RegisterCommandTest extends AbstractCommandWebTestCase
{
    public function testItRegistersAnExternalEnvironment(): void
    {
        $this->processProxy->getWorkingDirectory()->shouldBeCalledOnce()->willReturn('');
        $this->systemManager->install('', Environment::TYPE_CUSTOM, null)->shouldBeCalledOnce();

        $command = new RegisterCommand(
            $this->systemManager->reveal(),
            $this->validator->reveal(),
            $this->dockerCompose->reveal(),
            $this->eventDispatcher->reveal(),
            $this->processProxy->reveal()
        );

        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] Environment successfully registered.', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItAbortsTheRegistrationAfterDisapproval(): void
    {
        $this->processProxy->getWorkingDirectory()->shouldNotBeCalled();
        $this->systemManager->install(Argument::type('string'), Environment::TYPE_CUSTOM, null)->shouldNotBeCalled();

        $command = new RegisterCommand(
            $this->systemManager->reveal(),
            $this->validator->reveal(),
            $this->dockerCompose->reveal(),
            $this->eventDispatcher->reveal(),
            $this->processProxy->reveal()
        );

        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['no']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringNotContainsString('[OK] Environment successfully registered.', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        $this->processProxy->getWorkingDirectory()
            ->shouldBeCalledOnce()
            ->willThrow(new InvalidEnvironmentException('Unable to determine the current working directory.'))
        ;
        $this->systemManager->install(Argument::type('string'), Environment::TYPE_CUSTOM, null)->shouldNotBeCalled();

        $command = new RegisterCommand(
            $this->systemManager->reveal(),
            $this->validator->reveal(),
            $this->dockerCompose->reveal(),
            $this->eventDispatcher->reveal(),
            $this->processProxy->reveal()
        );

        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] Unable to determine the current working directory.', $display);
        static::assertSame(CommandExitCode::EXCEPTION, $commandTester->getStatusCode());
    }
}
