<?php

declare(strict_types=1);

namespace App\Tests\Command\Main;

use App\Command\Main\UpdateCommand;
use App\Environment\Configuration\ConfigurationUpdater;
use App\Environment\EnvironmentEntity;
use App\Exception\FilesystemException;
use App\Exception\InvalidEnvironmentException;
use App\Helper\CommandExitCode;
use App\Helper\CurrentContext;
use App\Tests\TestCommandTrait;
use App\Tests\TestFakeEnvironmentTrait;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\Main\UpdateCommand
 *
 * @uses \App\Event\AbstractEnvironmentEvent
 */
final class UpdateCommandTest extends WebTestCase
{
    use ProphecyTrait;
    use TestCommandTrait;
    use TestFakeEnvironmentTrait;

    /**
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     */
    public function testItSuccessfullyUpdateTheCurrentEnvironment(): void
    {
        $environment = $this->getFakeEnvironment();

        $currentContext = $this->prophesize(CurrentContext::class);
        $updater = $this->prophesize(ConfigurationUpdater::class);

        $currentContext->getEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce()->willReturn($environment);

        $command = new UpdateCommand($currentContext->reveal(), $updater->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] Environment successfully updated.', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    /**
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     */
    public function testItAbortWhenTryingToUpdateACustomEnvironment(): void
    {
        $environment = new EnvironmentEntity(
            'origami',
            '~/Sites/origami',
            EnvironmentEntity::TYPE_CUSTOM,
            'origami.localhost',
            false
        );
        $exception = new InvalidEnvironmentException('Unable to update a custom environment.');

        $currentContext = $this->prophesize(CurrentContext::class);
        $updater = $this->prophesize(ConfigurationUpdater::class);

        $currentContext->getEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce()->willReturn($environment);
        $updater->update($environment)->shouldBeCalledOnce()->willThrow($exception);

        $command = new UpdateCommand($currentContext->reveal(), $updater->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] Unable to update a custom environment.', $display);
        static::assertSame(CommandExitCode::EXCEPTION, $commandTester->getStatusCode());
    }

    /**
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     */
    public function testItAbortWhenTryingToUpdateARunningEnvironment(): void
    {
        $environment = new EnvironmentEntity(
            'origami',
            '~/Sites/origami',
            EnvironmentEntity::TYPE_SYMFONY,
            'origami.localhost',
            true
        );
        $exception = new InvalidEnvironmentException('Unable to update a running environment.');

        $currentContext = $this->prophesize(CurrentContext::class);
        $updater = $this->prophesize(ConfigurationUpdater::class);

        $currentContext->getEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce()->willReturn($environment);
        $updater->update($environment)->shouldBeCalledOnce()->willThrow($exception);

        $command = new UpdateCommand($currentContext->reveal(), $updater->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] Unable to update a running environment.', $display);
        static::assertSame(CommandExitCode::EXCEPTION, $commandTester->getStatusCode());
    }
}
