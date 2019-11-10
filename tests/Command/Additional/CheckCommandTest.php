<?php

declare(strict_types=1);

namespace App\Tests\Command\Additional;

use App\Command\Additional\CheckCommand;
use App\Helper\CommandExitCode;
use App\Middleware\Binary\DockerCompose;
use App\Middleware\SystemManager;
use Generator;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\Additional\CheckCommand
 */
final class CheckCommandTest extends WebTestCase
{
    private $systemManager;
    private $validator;
    private $dockerCompose;
    private $eventDispatcher;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->systemManager = $this->prophesize(SystemManager::class);
        $this->validator = $this->prophesize(ValidatorInterface::class);
        $this->dockerCompose = $this->prophesize(DockerCompose::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
    }

    /**
     * @dataProvider provideSystemRequirements
     */
    public function testItPrintsSuccessWithAllRequirements(array $requirements): void
    {
        foreach ($requirements as $name => $description) {
            $this->systemManager->isBinaryInstalled($name)
                ->shouldBeCalledOnce()
                ->willReturn(true)
            ;
        }

        $command = new CheckCommand(
            $this->systemManager->reveal(),
            $this->validator->reveal(),
            $this->dockerCompose->reveal(),
            $this->eventDispatcher->reveal(),
            $requirements
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] Your system is ready.', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    /**
     * @dataProvider provideSystemRequirements
     */
    public function testItPrintsErrorWithoutAllRequirements(array $requirements): void
    {
        foreach ($requirements as $name => $description) {
            $this->systemManager->isBinaryInstalled($name)
                ->shouldBeCalledOnce()
                ->willReturn(false)
            ;
        }

        $command = new CheckCommand(
            $this->systemManager->reveal(),
            $this->validator->reveal(),
            $this->dockerCompose->reveal(),
            $this->eventDispatcher->reveal(),
            $requirements
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] At least one system requirement is missing.', $display);
        static::assertGreaterThan(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function provideSystemRequirements(): ?Generator
    {
        yield [
            [
                'php' => 'The programming language used by Origami.',
                'composer' => 'The dependency manager for PHP.',
                'homebrew' => 'The package manager for macOS.',
            ],
        ];
    }
}
