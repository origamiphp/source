<?php

declare(strict_types=1);

namespace App\Tests\Command\Main;

use App\Command\Main\InstallCommand;
use App\Exception\InvalidEnvironmentException;
use App\Helper\CommandExitCode;
use App\Middleware\Binary\DockerCompose;
use App\Middleware\SystemManager;
use App\Validator\Constraints\LocalDomains;
use Generator;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\Main\InstallCommand
 */
final class InstallCommandTest extends WebTestCase
{
    private $systemManager;
    private $validator;
    private $dockerCompose;
    private $eventDispatcher;

    private $location;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->systemManager = $this->prophesize(SystemManager::class);
        $this->validator = $this->prophesize(ValidatorInterface::class);
        $this->dockerCompose = $this->prophesize(DockerCompose::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

        $this->location = sys_get_temp_dir().'/origami/InstallCommandTest';
        mkdir($this->location, 0777, true);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        if (is_dir($this->location)) {
            $filesystem = new Filesystem();
            $filesystem->remove($this->location);
        }

        $this->location = null;
    }

    /**
     * @dataProvider provideEnvironmentConfigurations
     *
     * @param string $location
     * @param string $type
     * @param string $domains
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
            ['magento2', 'symfony']
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

    public function provideEnvironmentConfigurations(): ?Generator
    {
        $location = sys_get_temp_dir().'/origami/InstallCommandTest';

        yield [$location, 'magento2', 'www.magento.localhost magento.localhost'];
        yield [$location, 'magento2', ''];

        yield [$location, 'symfony', 'www.symfony.localhost symfony.localhost'];
        yield [$location, 'symfony', ''];
    }

    public function testItAbortsTheInstallationWithInvalidLocation(): void
    {
        $command = new InstallCommand(
            $this->systemManager->reveal(),
            $this->validator->reveal(),
            $this->dockerCompose->reveal(),
            $this->eventDispatcher->reveal(),
            ['magento2', 'symfony']
        );

        $this->expectException(RuntimeException::class);

        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['symfony', 'azerty', 'no']);
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
            ['magento2', 'symfony']
        );

        $this->expectException(RuntimeException::class);

        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['symfony', '.', 'yes', 'azerty']);
        $commandTester->execute([]);
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        $this->systemManager->install(realpath('.'), 'symfony', null)
            ->shouldBeCalledOnce()
            ->willThrow(new InvalidEnvironmentException('Dummy exception.'))
        ;

        $command = new InstallCommand(
            $this->systemManager->reveal(),
            $this->validator->reveal(),
            $this->dockerCompose->reveal(),
            $this->eventDispatcher->reveal(),
            ['magento2', 'symfony']
        );

        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['symfony', '.', 'no']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] Dummy exception.', $display);
        static::assertSame(CommandExitCode::EXCEPTION, $commandTester->getStatusCode());
    }
}
