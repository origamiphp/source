<?php

declare(strict_types=1);

namespace App\Tests\Command\Main;

use App\Command\Main\UpdateCommand;
use App\Environment\Configuration\ConfigurationUpdater;
use App\Environment\EnvironmentEntity;
use App\Exception\InvalidEnvironmentException;
use App\Helper\CommandExitCode;
use App\Helper\CurrentContext;
use App\Tests\Command\AbstractCommandWebTestCase;
use App\Tests\TestFakeEnvironmentTrait;
use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
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
final class UpdateCommandTest extends AbstractCommandWebTestCase
{
    use TestFakeEnvironmentTrait;

    /** @var ObjectProphecy */
    private $currentContext;

    /** @var ObjectProphecy */
    private $updater;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->currentContext = $this->prophet->prophesize(CurrentContext::class);
        $this->updater = $this->prophet->prophesize(ConfigurationUpdater::class);
    }

    public function testItSuccessfullyUpdateTheCurrentEnvironment(): void
    {
        $environment = $this->getFakeEnvironment();

        (new MethodProphecy($this->currentContext, 'getEnvironment', [Argument::type(InputInterface::class)]))
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $commandTester = new CommandTester($this->getCommand());
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] Environment successfully updated.', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItAbortWhenTryingToUpdateACustomEnvironment(): void
    {
        $environment = new EnvironmentEntity(
            'origami',
            '~/Sites/origami',
            EnvironmentEntity::TYPE_CUSTOM,
            'origami.localhost www.origami.localhost',
            false
        );

        (new MethodProphecy($this->currentContext, 'getEnvironment', [Argument::type(InputInterface::class)]))
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        (new MethodProphecy($this->updater, 'update', [$environment]))
            ->shouldBeCalledOnce()
            ->willThrow(new InvalidEnvironmentException('Unable to update a custom environment.'))
        ;

        $commandTester = new CommandTester($this->getCommand());
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] Unable to update a custom environment.', $display);
        static::assertSame(CommandExitCode::EXCEPTION, $commandTester->getStatusCode());
    }

    public function testItAbortWhenTryingToUpdateARunningEnvironment(): void
    {
        $environment = new EnvironmentEntity(
            'origami',
            '~/Sites/origami',
            EnvironmentEntity::TYPE_SYMFONY,
            'origami.localhost www.origami.localhost',
            true
        );

        (new MethodProphecy($this->currentContext, 'getEnvironment', [Argument::type(InputInterface::class)]))
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        (new MethodProphecy($this->updater, 'update', [$environment]))
            ->shouldBeCalledOnce()
            ->willThrow(new InvalidEnvironmentException('Unable to update a running environment.'))
        ;

        $commandTester = new CommandTester($this->getCommand());
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] Unable to update a running environment.', $display);
        static::assertSame(CommandExitCode::EXCEPTION, $commandTester->getStatusCode());
    }

    /**
     * Retrieves the \App\Command\Contextual\UpdateCommand instance to use within the tests.
     */
    private function getCommand(): UpdateCommand
    {
        return new UpdateCommand(
            $this->currentContext->reveal(),
            $this->updater->reveal()
        );
    }
}
