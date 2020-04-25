<?php

declare(strict_types=1);

namespace App\Tests\Command\Main;

use App\Command\Main\InstallCommand;
use App\Environment\EnvironmentEntity;
use App\Exception\InvalidEnvironmentException;
use App\Helper\CommandExitCode;
use App\Middleware\DockerHub;
use App\Tests\Command\AbstractCommandWebTestCase;
use App\Validator\Constraints\LocalDomains;
use Generator;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

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
    /** @var Prophet */
    protected $prophet;

    /** @var ObjectProphecy */
    protected $dockerHub;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prophet = new Prophet();
        $this->dockerHub = $this->prophet->prophesize(DockerHub::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->prophet->checkPredictions();
    }

    /**
     * @dataProvider provideEnvironmentConfigurations
     */
    public function testItInstallTheRequestedEnvironment(string $type, string $phpVersion, ?string $domains): void
    {
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

        /** @var string $location */
        $location = realpath('.');

        (new MethodProphecy($this->systemManager, 'install', [$location, $type, $phpVersion, $domains]))
            ->shouldBeCalledOnce()
        ;

        $commandTester = new CommandTester($this->getInstallCommand());
        if ($domains) {
            $commandTester->setInputs([$type, $phpVersion, 'yes', $domains]);
        } else {
            $commandTester->setInputs([$type, $phpVersion, 'no']);
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

        /** @var string $location */
        $location = realpath('.');

        (new MethodProphecy($this->systemManager, 'install', [$location, EnvironmentEntity::TYPE_SYMFONY, 'latest', $defaultDomains]))
            ->shouldBeCalledOnce()
        ;

        $commandTester = new CommandTester($this->getInstallCommand());
        $commandTester->setInputs([EnvironmentEntity::TYPE_SYMFONY, 'yes', $invalidDomains]);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] Environment successfully installed.', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        (new MethodProphecy($this->dockerHub, 'getImageTags', [EnvironmentEntity::TYPE_SYMFONY.'-php']))
            ->shouldBeCalledOnce()
            ->willReturn(['latest'])
        ;

        /** @var string $location */
        $location = realpath('.');

        (new MethodProphecy($this->systemManager, 'install', [$location, EnvironmentEntity::TYPE_SYMFONY, 'latest', null]))
            ->shouldBeCalledOnce()
            ->willThrow(new InvalidEnvironmentException('Dummy exception.'))
        ;

        $commandTester = new CommandTester($this->getInstallCommand());
        $commandTester->setInputs([EnvironmentEntity::TYPE_SYMFONY, 'no']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] Dummy exception.', $display);
        static::assertSame(CommandExitCode::EXCEPTION, $commandTester->getStatusCode());
    }

    /**
     * Retrieves the \App\Command\Additional\InstallCommand instance to use within the tests.
     */
    protected function getInstallCommand(): InstallCommand
    {
        return new InstallCommand(
            $this->database->reveal(),
            $this->systemManager->reveal(),
            $this->validator->reveal(),
            $this->dockerCompose->reveal(),
            $this->eventDispatcher->reveal(),
            $this->processProxy->reveal(),
            $this->dockerHub->reveal()
        );
    }
}
