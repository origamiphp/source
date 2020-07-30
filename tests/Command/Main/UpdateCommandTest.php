<?php

declare(strict_types=1);

namespace App\Tests\Command\Main;

use App\Command\Main\UpdateCommand;
use App\Environment\Configuration\ConfigurationUpdater;
use App\Environment\EnvironmentEntity;
use App\Exception\InvalidEnvironmentException;
use App\Helper\CurrentContext;
use App\Tests\Command\TestCommandTrait;
use App\Tests\TestLocationTrait;
use Generator;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Command\Command;
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
    use TestLocationTrait;

    public function testItSuccessfullyUpdateTheCurrentEnvironment(): void
    {
        $environment = $this->createEnvironment();

        [$currentContext, $updater] = $this->prophesizeUpdateCommandArguments();

        $currentContext->getEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce()->willReturn($environment);
        $currentContext->setActiveEnvironment($environment)->shouldBeCalledOnce();

        $command = new UpdateCommand($currentContext->reveal(), $updater->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] ', $display);
        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    /**
     * @dataProvider provideInvalidEnvironments
     */
    public function testItAbortGracefullyTheUpdate(EnvironmentEntity $environment): void
    {
        [$currentContext, $updater] = $this->prophesizeUpdateCommandArguments();

        $currentContext->getEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce()->willReturn($environment);
        $currentContext->setActiveEnvironment($environment)->shouldBeCalledOnce();
        $updater->update($environment)->shouldBeCalledOnce()->willThrow(InvalidEnvironmentException::class);

        $command = new UpdateCommand($currentContext->reveal(), $updater->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] ', $display);
        static::assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }

    public function provideInvalidEnvironments(): Generator
    {
        yield 'A custom environment' => [
            new EnvironmentEntity('foo', '~/Sites/foo', EnvironmentEntity::TYPE_CUSTOM, 'foo.localhost', false),
        ];

        yield 'A running environment' => [
            new EnvironmentEntity('bar', '~/Sites/bar', EnvironmentEntity::TYPE_SYMFONY, 'bar.localhost', true),
        ];
    }

    /**
     * Prophesizes arguments needed by the \App\Command\Main\UpdateCommand class.
     */
    private function prophesizeUpdateCommandArguments(): array
    {
        return [
            $this->prophesize(CurrentContext::class),
            $this->prophesize(ConfigurationUpdater::class),
        ];
    }
}
