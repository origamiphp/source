<?php

declare(strict_types=1);

namespace App\Tests\Command\Main;

use App\Command\Main\InstallCommand;
use App\Environment\EnvironmentEntity;
use App\Exception\InvalidEnvironmentException;
use App\Helper\CommandExitCode;
use App\Middleware\DockerHub;
use App\Tests\AbstractCommandWebTestCase;
use App\Tests\TestLocationTrait;
use App\Validator\Constraints\LocalDomains;
use Generator;
use Prophecy\Prophecy\ObjectProphecy;
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
    use TestLocationTrait;

    /** @var DockerHub|ObjectProphecy */
    protected $dockerHub;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->createLocation();

        $this->dockerHub = $this->prophesize(DockerHub::class);
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
    public function testItInstallTheRequestedEnvironment(string $type, string $phpVersion, string $location, ?string $domains): void
    {
        if ($domains) {
            $this->validator->validate($domains, new LocalDomains())
                ->shouldBeCalledOnce()
                ->willReturn(new ConstraintViolationList())
            ;
        }

        $this->dockerHub->getImageTags("{$type}-php")
            ->shouldBeCalledOnce()->willReturn(['foo', 'bar', 'latest'])
        ;

        $this->systemManager->install(realpath($location), $type, $phpVersion, $domains)
            ->shouldBeCalledOnce()
        ;

        $commandTester = new CommandTester($this->getInstallCommand());
        if ($domains) {
            $commandTester->setInputs([$type, $phpVersion, $location, 'yes', $domains]);
        } else {
            $commandTester->setInputs([$type, $phpVersion, $location, 'no']);
        }
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] Environment successfully installed.', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function provideEnvironmentConfigurations(): Generator
    {
        $location = sys_get_temp_dir().'/origami/InstallCommandTest';

        yield [EnvironmentEntity::TYPE_MAGENTO2, 'latest', $location, 'magento.localhost www.magento.localhost'];
        yield [EnvironmentEntity::TYPE_MAGENTO2, 'latest', $location, null];

        yield [EnvironmentEntity::TYPE_SYMFONY, 'latest', $location, 'symfony.localhost www.symfony.localhost'];
        yield [EnvironmentEntity::TYPE_SYMFONY, 'latest', $location, null];
    }

    public function testItReplacesAnInvalidLocationByTheDefaultValue(): void
    {
        $this->dockerHub->getImageTags(EnvironmentEntity::TYPE_SYMFONY.'-php')
            ->shouldBeCalledOnce()->willReturn(['latest']);

        $this->systemManager->install(realpath('.'), EnvironmentEntity::TYPE_SYMFONY, 'latest', null)
            ->shouldBeCalledOnce()
        ;

        $commandTester = new CommandTester($this->getInstallCommand());
        $commandTester->setInputs([EnvironmentEntity::TYPE_SYMFONY, 'azerty']);
        $commandTester->execute([]);
    }

    public function testItReplacesAnInvalidDomainByTheDefaultValue(): void
    {
        $this->dockerHub->getImageTags(EnvironmentEntity::TYPE_SYMFONY.'-php')
            ->shouldBeCalledOnce()->willReturn(['latest']);

        $invalidDomains = 'azerty';
        $defaultDomains = 'symfony.localhost www.symfony.localhost';

        $violation = $this->prophesize(ConstraintViolation::class);
        $violation->getMessage()->shouldBeCalledOnce()->willReturn('Dummy exception');

        $errors = new ConstraintViolationList();
        $errors->add($violation->reveal());

        $this->validator->validate($invalidDomains, new LocalDomains())->shouldBeCalledOnce()->willReturn($errors);
        $this->validator->validate($defaultDomains, new LocalDomains())->shouldBeCalledOnce()->willReturn(new ConstraintViolationList());

        $this->systemManager->install(realpath('.'), EnvironmentEntity::TYPE_SYMFONY, 'latest', $defaultDomains)
            ->shouldBeCalledOnce()
        ;

        $commandTester = new CommandTester($this->getInstallCommand());
        $commandTester->setInputs([EnvironmentEntity::TYPE_SYMFONY, null, 'yes', $invalidDomains]);
        $commandTester->execute([]);
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        $this->dockerHub->getImageTags(EnvironmentEntity::TYPE_SYMFONY.'-php')
            ->shouldBeCalledOnce()->willReturn(['latest']);

        $this->systemManager->install(realpath('.'), EnvironmentEntity::TYPE_SYMFONY, 'latest', null)
            ->shouldBeCalledOnce()
            ->willThrow(new InvalidEnvironmentException('Dummy exception.'))
        ;

        $commandTester = new CommandTester($this->getInstallCommand());
        $commandTester->setInputs([EnvironmentEntity::TYPE_SYMFONY, null, 'no']);
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
