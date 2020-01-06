<?php

declare(strict_types=1);

namespace App\Tests\Command\Main;

use App\Command\Main\InstallCommand;
use App\Entity\Environment;
use App\Exception\InvalidEnvironmentException;
use App\Helper\CommandExitCode;
use App\Helper\ProcessProxy;
use App\Middleware\Binary\DockerCompose;
use App\Middleware\SystemManager;
use App\Tests\TestCustomCommandsTrait;
use App\Tests\TestLocationTrait;
use App\Validator\Constraints\LocalDomains;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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
final class InstallCommandTest extends WebTestCase
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
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->removeLocation();
    }

    /**
     * @dataProvider provideEnvironmentConfigurations
     */
    public function testItInstallTheRequestedEnvironment(string $location, string $type, string $domains = ''): void
    {
        if ($domains !== '') {
            $this->validator->validate($domains, new LocalDomains())
                ->shouldBeCalledOnce()
                ->willReturn(new ConstraintViolationList())
            ;
        }

        $this->systemManager->install(realpath($location), $type, $domains !== '' ? $domains : null)
            ->shouldBeCalledOnce()
        ;

        $command = new InstallCommand(
            $this->systemManager->reveal(),
            $this->validator->reveal(),
            $this->dockerCompose->reveal(),
            $this->eventDispatcher->reveal(),
            $this->processProxy->reveal(),
        );

        $commandTester = new CommandTester($command);
        if ($domains !== '') {
            $commandTester->setInputs([$type, $location, 'yes', $domains]);
        } else {
            $commandTester->setInputs([$type, $location, 'no']);
        }
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] Environment successfully installed.', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function provideEnvironmentConfigurations(): Generator
    {
        $location = sys_get_temp_dir().'/origami/InstallCommandTest';

        yield [$location, Environment::TYPE_MAGENTO2, 'www.magento.localhost magento.localhost'];
        yield [$location, Environment::TYPE_MAGENTO2, ''];

        yield [$location, Environment::TYPE_SYMFONY, 'www.symfony.localhost symfony.localhost'];
        yield [$location, Environment::TYPE_SYMFONY, ''];
    }

    public function testItAbortsTheInstallationWithInvalidLocation(): void
    {
        $command = new InstallCommand(
            $this->systemManager->reveal(),
            $this->validator->reveal(),
            $this->dockerCompose->reveal(),
            $this->eventDispatcher->reveal(),
            $this->processProxy->reveal(),
        );

        $this->expectException(RuntimeException::class);

        $commandTester = new CommandTester($command);
        $commandTester->setInputs([Environment::TYPE_SYMFONY, 'azerty', 'no']);
        $commandTester->execute([]);
    }

    public function testItAbortsTheInstallationWithInvalidDomains(): void
    {
        $violation = $this->prophesize(ConstraintViolation::class);
        $violation->getMessage()->shouldBeCalledOnce()->willReturn('Dummy exception');

        $errors = new ConstraintViolationList();
        $errors->add($violation->reveal());

        $this->validator->validate('azerty', new LocalDomains())
            ->shouldBeCalledOnce()
            ->willReturn($errors)
        ;

        $command = new InstallCommand(
            $this->systemManager->reveal(),
            $this->validator->reveal(),
            $this->dockerCompose->reveal(),
            $this->eventDispatcher->reveal(),
            $this->processProxy->reveal(),
        );

        $this->expectException(RuntimeException::class);

        $commandTester = new CommandTester($command);
        $commandTester->setInputs([Environment::TYPE_SYMFONY, '.', 'yes', 'azerty']);
        $commandTester->execute([]);
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        $this->systemManager->install(realpath('.'), Environment::TYPE_SYMFONY, null)
            ->shouldBeCalledOnce()
            ->willThrow(new InvalidEnvironmentException('Dummy exception.'))
        ;

        $command = new InstallCommand(
            $this->systemManager->reveal(),
            $this->validator->reveal(),
            $this->dockerCompose->reveal(),
            $this->eventDispatcher->reveal(),
            $this->processProxy->reveal(),
        );

        $commandTester = new CommandTester($command);
        $commandTester->setInputs([Environment::TYPE_SYMFONY, '.', 'no']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] Dummy exception.', $display);
        static::assertSame(CommandExitCode::EXCEPTION, $commandTester->getStatusCode());
    }
}
