<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\RegistryCommand;
use App\Environment\EnvironmentCollection;
use App\Environment\EnvironmentEntity;
use App\Middleware\Database;
use App\Tests\CustomProphecyTrait;
use App\Tests\TestCommandTrait;
use App\Tests\TestEnvironmentTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\RegistryCommand
 */
final class RegistryCommandTest extends WebTestCase
{
    use CustomProphecyTrait;
    use TestCommandTrait;
    use TestEnvironmentTrait;

    public function testItPrintsNoteMessageWithoutEnvironments(): void
    {
        [$database] = $this->prophesizeObjectArguments();
        $database->getAllEnvironments()->shouldBeCalledOnce()->willReturn(new EnvironmentCollection());

        $command = new RegistryCommand($database->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[NOTE] ', $display);
    }

    /**
     * @dataProvider provideMultipleInstallContexts
     */
    public function testItPrintsEnvironmentDetailsInTable(
        string $name,
        string $type,
        ?string $domains = null
    ): void {
        $environment = new EnvironmentEntity($name, $this->location, $type, $domains);
        [$database] = $this->prophesizeObjectArguments();

        $database->getAllEnvironments()->shouldBeCalledOnce()->willReturn(new EnvironmentCollection([$environment]));

        $command = new RegistryCommand($database->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();

        $name = $environment->getName();
        static::assertStringContainsString($name, $display);

        $location = $environment->getLocation();
        static::assertStringContainsString($location, $display);

        $type = $environment->getType();
        static::assertStringContainsString($type, $display);

        if ($domains = $environment->getDomains()) {
            static::assertStringContainsString($domains, $display);
        }

        if ($environment->isActive()) {
            static::assertStringContainsString('Started', $display);
        } else {
            static::assertStringContainsString('Stopped', $display);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function prophesizeObjectArguments(): array
    {
        return [
            $this->prophesize(Database::class),
        ];
    }
}
