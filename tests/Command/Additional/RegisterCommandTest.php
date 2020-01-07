<?php

declare(strict_types=1);

namespace App\Tests\Command\Additional;

use App\Command\Additional\RegisterCommand;
use App\Entity\Environment;
use App\Exception\InvalidEnvironmentException;
use App\Helper\CommandExitCode;
use App\Helper\ProcessProxy;
use App\Middleware\Binary\DockerCompose;
use App\Middleware\SystemManager;
use App\Tests\TestCustomCommandsTrait;
use App\Tests\TestLocationTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\Additional\RegisterCommand
 */
final class RegisterCommandTest extends WebTestCase
{
    use TestCustomCommandsTrait;
    use TestLocationTrait;

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

        $this->createLocation();
        putenv('COLUMNS=120'); // Required by tests running with Github Actions
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->removeLocation();
    }

    public function testItRegistersAnExternalEnvironment(): void
    {
        $this->processProxy->getWorkingDirectory()->shouldBeCalledOnce()->willReturn($this->location);
        $this->systemManager->install($this->location, Environment::TYPE_CUSTOM, null)->shouldBeCalledOnce();

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
        $this->systemManager->install($this->location, Environment::TYPE_CUSTOM, null)->shouldNotBeCalled();

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
        $this->systemManager->install($this->location, Environment::TYPE_CUSTOM, null)->shouldNotBeCalled();

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
