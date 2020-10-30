<?php

declare(strict_types=1);

namespace App\Tests\Command\Additional;

use App\Command\Additional\RegistryCommand;
use App\Environment\EnvironmentCollection;
use App\Environment\EnvironmentEntity;
use App\Exception\InvalidEnvironmentException;
use App\Middleware\Database;
use App\Tests\Command\TestCommandTrait;
use App\Tests\CustomProphecyTrait;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\Additional\RegistryCommand
 */
final class RegistryCommandTest extends WebTestCase
{
    use CustomProphecyTrait;
    use TestCommandTrait;

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
     * @dataProvider provideEnvironmentList
     *
     * @throws InvalidEnvironmentException
     */
    public function testItPrintsEnvironmentDetailsInTable(EnvironmentEntity $environment): void
    {
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

    public function provideEnvironmentList(): Generator
    {
        yield 'inactive environment without domains' => [
            new EnvironmentEntity(
                'POC',
                '~/Sites/poc-symfony',
                EnvironmentEntity::TYPE_SYMFONY,
                null,
                false
            ),
        ];

        yield 'active environment with domains' => [
            new EnvironmentEntity(
                'Origami',
                '~/Sites/origami',
                EnvironmentEntity::TYPE_SYMFONY,
                'origami.localhost',
                true
            ),
        ];
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
