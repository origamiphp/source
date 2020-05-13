<?php

declare(strict_types=1);

namespace App\Tests\Command\Main;

use App\Command\Main\InstallCommand;
use App\Environment\EnvironmentEntity;
use App\Event\EnvironmentInstalledEvent;
use App\Exception\InvalidEnvironmentException;
use App\Helper\CommandExitCode;
use App\Helper\ProcessProxy;
use App\Middleware\Configuration\ConfigurationInstaller;
use App\Middleware\DockerHub;
use App\Tests\Command\AbstractCommandWebTestCase;
use App\Validator\Constraints\LocalDomains;
use Generator;
use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\Main\InstallCommand
 *
 * @uses \App\Event\AbstractEnvironmentEvent
 */
final class InstallCommandTest extends AbstractCommandWebTestCase
{
    /** @var ObjectProphecy */
    private $processProxy;

    /** @var ObjectProphecy */
    private $dockerHub;

    /** @var ObjectProphecy */
    private $validator;

    /** @var ObjectProphecy */
    private $installer;

    /** @var ObjectProphecy */
    private $eventDispatcher;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->processProxy = $this->prophet->prophesize(ProcessProxy::class);
        $this->dockerHub = $this->prophet->prophesize(DockerHub::class);
        $this->validator = $this->prophet->prophesize(ValidatorInterface::class);
        $this->installer = $this->prophet->prophesize(ConfigurationInstaller::class);
        $this->eventDispatcher = $this->prophet->prophesize(EventDispatcher::class);
    }

    /**
     * @dataProvider provideEnvironmentConfigurations
     */
    public function testItInstallTheRequestedEnvironmentWithDefaultName(string $type, string $phpVersion, ?string $domains): void
    {
        (new MethodProphecy($this->processProxy, 'getWorkingDirectory', []))
            ->shouldBeCalledOnce()
            ->willReturn('/fake/directory')
        ;

        if ($domains) {
            (new MethodProphecy($this->validator, 'validate', [$domains, new LocalDomains()]))
                ->shouldBeCalledOnce()
                ->willReturn(new ConstraintViolationList())
            ;
        }

        (new MethodProphecy($this->dockerHub, 'getImageTags', ["{$type}-php"]))
            ->shouldBeCalledOnce()
            ->willReturn(['foo', 'bar', 'latest'])
        ;

        (new MethodProphecy($this->installer, 'install', ['directory', '/fake/directory', $type, $phpVersion, $domains]))
            ->shouldBeCalledOnce()
        ;

        (new MethodProphecy($this->eventDispatcher, 'dispatch', [Argument::type(EnvironmentInstalledEvent::class)]))
            ->shouldBeCalledOnce()
        ;

        $commandTester = new CommandTester($this->getCommand());
        if ($domains) {
            $commandTester->setInputs(['', $type, $phpVersion, 'yes', $domains]);
        } else {
            $commandTester->setInputs(['', $type, $phpVersion, 'no']);
        }
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] Environment successfully installed.', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    /**
     * @dataProvider provideEnvironmentConfigurations
     */
    public function testItInstallTheRequestedEnvironmentWithCustomName(string $type, string $phpVersion, ?string $domains): void
    {
        (new MethodProphecy($this->processProxy, 'getWorkingDirectory', []))
            ->shouldBeCalledOnce()
            ->willReturn('/fake/directory')
        ;

        if ($domains) {
            (new MethodProphecy($this->validator, 'validate', [$domains, new LocalDomains()]))
                ->shouldBeCalledOnce()
                ->willReturn(new ConstraintViolationList())
            ;
        }

        (new MethodProphecy($this->dockerHub, 'getImageTags', ["{$type}-php"]))
            ->shouldBeCalledOnce()
            ->willReturn(['foo', 'bar', 'latest'])
        ;

        (new MethodProphecy($this->installer, 'install', ['custom-name', '/fake/directory', $type, $phpVersion, $domains]))
            ->shouldBeCalledOnce()
        ;

        (new MethodProphecy($this->eventDispatcher, 'dispatch', [Argument::type(EnvironmentInstalledEvent::class)]))
            ->shouldBeCalledOnce()
        ;

        $commandTester = new CommandTester($this->getCommand());
        if ($domains) {
            $commandTester->setInputs(['custom-name', $type, $phpVersion, 'yes', $domains]);
        } else {
            $commandTester->setInputs(['custom-name', $type, $phpVersion, 'no']);
        }
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] Environment successfully installed.', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function provideEnvironmentConfigurations(): Generator
    {
        yield [EnvironmentEntity::TYPE_MAGENTO2, 'latest', 'magento.localhost www.magento.localhost'];
        yield [EnvironmentEntity::TYPE_MAGENTO2, 'latest', null];

        yield [EnvironmentEntity::TYPE_SYMFONY, 'latest', 'symfony.localhost www.symfony.localhost'];
        yield [EnvironmentEntity::TYPE_SYMFONY, 'latest', null];
    }

    public function testItReplacesAnInvalidDomainByTheDefaultValue(): void
    {
        (new MethodProphecy($this->processProxy, 'getWorkingDirectory', []))
            ->shouldBeCalledOnce()
            ->willReturn('/fake/directory')
        ;

        (new MethodProphecy($this->dockerHub, 'getImageTags', [EnvironmentEntity::TYPE_SYMFONY.'-php']))
            ->shouldBeCalledOnce()
            ->willReturn(['latest'])
        ;

        $invalidDomains = 'azerty';
        $defaultDomains = 'symfony.localhost www.symfony.localhost';

        $violation = $this->prophet->prophesize(ConstraintViolation::class);
        (new MethodProphecy($violation, 'getMessage', []))
            ->shouldBeCalledOnce()
            ->willReturn('Dummy exception')
        ;

        $errors = new ConstraintViolationList();
        $errors->add($violation->reveal());

        (new MethodProphecy($this->validator, 'validate', [$invalidDomains, new LocalDomains()]))
            ->shouldBeCalledOnce()
            ->willReturn($errors)
        ;
        (new MethodProphecy($this->validator, 'validate', [$defaultDomains, new LocalDomains()]))
            ->shouldBeCalledOnce()
            ->willReturn(new ConstraintViolationList())
        ;

        (new MethodProphecy($this->installer, 'install', ['directory', '/fake/directory', EnvironmentEntity::TYPE_SYMFONY, 'latest', $defaultDomains]))
            ->shouldBeCalledOnce()
        ;

        (new MethodProphecy($this->eventDispatcher, 'dispatch', [Argument::type(EnvironmentInstalledEvent::class)]))
            ->shouldBeCalledOnce()
        ;

        $commandTester = new CommandTester($this->getCommand());
        $commandTester->setInputs(['', EnvironmentEntity::TYPE_SYMFONY, 'yes', $invalidDomains]);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] Environment successfully installed.', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        (new MethodProphecy($this->processProxy, 'getWorkingDirectory', []))
            ->shouldBeCalledOnce()
            ->willReturn('/fake/directory')
        ;

        (new MethodProphecy($this->dockerHub, 'getImageTags', [EnvironmentEntity::TYPE_SYMFONY.'-php']))
            ->shouldBeCalledOnce()
            ->willReturn(['latest'])
        ;

        (new MethodProphecy($this->installer, 'install', ['directory', '/fake/directory', EnvironmentEntity::TYPE_SYMFONY, 'latest', null]))
            ->shouldBeCalledOnce()
            ->willThrow(new InvalidEnvironmentException('Dummy exception.'))
        ;

        $commandTester = new CommandTester($this->getCommand());
        $commandTester->setInputs(['', EnvironmentEntity::TYPE_SYMFONY, 'no']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] Dummy exception.', $display);
        static::assertSame(CommandExitCode::EXCEPTION, $commandTester->getStatusCode());
    }

    /**
     * Retrieves the \App\Command\Additional\InstallCommand instance to use within the tests.
     */
    private function getCommand(): InstallCommand
    {
        return new InstallCommand(
            $this->processProxy->reveal(),
            $this->dockerHub->reveal(),
            $this->validator->reveal(),
            $this->installer->reveal(),
            $this->eventDispatcher->reveal(),
        );
    }
}
