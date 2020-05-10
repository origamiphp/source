<?php

declare(strict_types=1);

namespace App\Tests\Command\Additional;

use App\Command\Additional\RegisterCommand;
use App\Environment\EnvironmentEntity;
use App\Exception\InvalidEnvironmentException;
use App\Helper\CommandExitCode;
use App\Helper\ProcessProxy;
use App\Middleware\Configuration\ConfigurationInstaller;
use App\Middleware\Database;
use App\Tests\Command\AbstractCommandWebTestCase;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\Additional\RegisterCommand
 */
final class RegisterCommandTest extends AbstractCommandWebTestCase
{
    /** @var ObjectProphecy */
    private $database;

    /** @var ObjectProphecy */
    private $installer;

    /** @var ObjectProphecy */
    private $processProxy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->database = $this->prophet->prophesize(Database::class);
        $this->installer = $this->prophet->prophesize(ConfigurationInstaller::class);
        $this->processProxy = $this->prophet->prophesize(ProcessProxy::class);
    }

    public function testItRegistersAnExternalEnvironment(): void
    {
        (new MethodProphecy($this->processProxy, 'getWorkingDirectory', []))
            ->shouldBeCalledOnce()
            ->willReturn('')
        ;

        (new MethodProphecy($this->installer, 'install', ['', EnvironmentEntity::TYPE_CUSTOM, null]))
            ->shouldBeCalledOnce()
        ;

        $commandTester = new CommandTester($this->getCommand());
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] Environment successfully registered.', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItAbortsTheRegistrationAfterDisapproval(): void
    {
        (new MethodProphecy($this->processProxy, 'getWorkingDirectory', []))
            ->shouldNotBeCalled()
        ;

        (new MethodProphecy($this->installer, 'install', ['', EnvironmentEntity::TYPE_CUSTOM, null]))
            ->shouldNotBeCalled()
        ;

        $commandTester = new CommandTester($this->getCommand());
        $commandTester->setInputs(['no']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringNotContainsString('[OK] Environment successfully registered.', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        (new MethodProphecy($this->processProxy, 'getWorkingDirectory', []))
            ->shouldBeCalled()
            ->willThrow(new InvalidEnvironmentException('Unable to determine the current working directory.'))
        ;

        (new MethodProphecy($this->installer, 'install', ['', EnvironmentEntity::TYPE_CUSTOM, null]))
            ->shouldNotBeCalled()
        ;

        $commandTester = new CommandTester($this->getCommand());
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] Unable to determine the current working directory.', $display);
        static::assertSame(CommandExitCode::EXCEPTION, $commandTester->getStatusCode());
    }

    /**
     * Retrieves the \App\Command\Additional\RegisterCommand instance to use within the tests.
     */
    private function getCommand(): RegisterCommand
    {
        return new RegisterCommand(
            $this->database->reveal(),
            $this->installer->reveal(),
            $this->processProxy->reveal(),
        );
    }
}
