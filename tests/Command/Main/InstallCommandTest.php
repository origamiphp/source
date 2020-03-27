<?php

declare(strict_types=1);

namespace App\Tests\Command\Main;

use App\Command\Main\InstallCommand;
use App\Environment\EnvironmentEntity;
use App\Exception\InvalidEnvironmentException;
use App\Helper\CommandExitCode;
use App\Tests\AbstractCommandWebTestCase;
use App\Tests\TestLocationTrait;
use App\Validator\Constraints\LocalDomains;
use Generator;
use Symfony\Component\Console\Exception\RuntimeException;
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

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
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

        $commandTester = new CommandTester($this->getCommand(InstallCommand::class));
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

        yield [$location, EnvironmentEntity::TYPE_MAGENTO2, 'www.magento.localhost magento.localhost'];
        yield [$location, EnvironmentEntity::TYPE_MAGENTO2, ''];

        yield [$location, EnvironmentEntity::TYPE_SYMFONY, 'www.symfony.localhost symfony.localhost'];
        yield [$location, EnvironmentEntity::TYPE_SYMFONY, ''];
    }

    public function testItAbortsTheInstallationWithInvalidLocation(): void
    {
        $this->expectException(RuntimeException::class);

        $commandTester = new CommandTester($this->getCommand(InstallCommand::class));
        $commandTester->setInputs([EnvironmentEntity::TYPE_SYMFONY, 'azerty', 'no']);
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

        $this->expectException(RuntimeException::class);

        $commandTester = new CommandTester($this->getCommand(InstallCommand::class));
        $commandTester->setInputs([EnvironmentEntity::TYPE_SYMFONY, '.', 'yes', 'azerty']);
        $commandTester->execute([]);
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        $this->systemManager->install(realpath('.'), EnvironmentEntity::TYPE_SYMFONY, null)
            ->shouldBeCalledOnce()
            ->willThrow(new InvalidEnvironmentException('Dummy exception.'))
        ;

        $commandTester = new CommandTester($this->getCommand(InstallCommand::class));
        $commandTester->setInputs([EnvironmentEntity::TYPE_SYMFONY, '.', 'no']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] Dummy exception.', $display);
        static::assertSame(CommandExitCode::EXCEPTION, $commandTester->getStatusCode());
    }
}
