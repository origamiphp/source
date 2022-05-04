<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\RegistryCommand;
use App\Service\ApplicationData;
use App\Tests\TestCommandTrait;
use App\Tests\TestEnvironmentTrait;
use App\ValueObject\EnvironmentCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\RegistryCommand
 */
final class RegistryCommandTest extends TestCase
{
    use ProphecyTrait;
    use TestCommandTrait;
    use TestEnvironmentTrait;

    public function testItPrintsNoteMessageWithoutEnvironments(): void
    {
        $database = $this->prophesize(ApplicationData::class);

        $database
            ->getAllEnvironments()
            ->shouldBeCalledOnce()
            ->willReturn(new EnvironmentCollection())
        ;

        $command = new RegistryCommand($database->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[NOTE] ', $display);
    }

    public function testItPrintsEnvironmentDetailsInTable(): void
    {
        $database = $this->prophesize(ApplicationData::class);
        $environment = $this->createEnvironment();

        $database
            ->getAllEnvironments()
            ->shouldBeCalledOnce()
            ->willReturn(new EnvironmentCollection([$environment]))
        ;

        $command = new RegistryCommand($database->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString($environment->getName(), $display);
        static::assertStringContainsString($environment->getLocation(), $display);
        static::assertStringContainsString($environment->getType(), $display);
        static::assertStringContainsString('Stopped', $display);
    }
}
