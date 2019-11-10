<?php

declare(strict_types=1);

namespace App\Tests\Command\Contextual;

use App\Command\Contextual\LogsCommand;
use App\Exception\InvalidEnvironmentException;
use App\Helper\CommandExitCode;
use App\Middleware\Binary\DockerCompose;
use App\Middleware\SystemManager;
use App\Tests\Command\CustomCommandsTrait;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\Contextual\LogsCommand
 */
final class LogsCommandTest extends WebTestCase
{
    use CustomCommandsTrait;

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
     * @dataProvider provideCommandModifiers
     *
     * @throws InvalidEnvironmentException
     */
    public function testItShowsServicesLogs(?int $tail, ?string $service): void
    {
        $environment = $this->getFakeEnvironment();

        $this->systemManager->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);
        $this->dockerCompose->setActiveEnvironment($environment)->shouldBeCalledOnce();
        $this->dockerCompose->showServicesLogs($tail ?? 0, $service)->shouldBeCalledOnce();

        $command = new LogsCommand(
            $this->systemManager->reveal(),
            $this->validator->reveal(),
            $this->dockerCompose->reveal(),
            $this->eventDispatcher->reveal()
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['--tail' => $tail, 'service' => $service],
            ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]
        );

        $display = $commandTester->getDisplay();

        static::assertDisplayIsVerbose($environment, $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    /**
     * @dataProvider provideCommandModifiers
     *
     * @throws InvalidEnvironmentException
     */
    public function testItGracefullyExitsWhenAnExceptionOccurred(?int $tail, ?string $service): void
    {
        $environment = $this->getFakeEnvironment();

        $this->systemManager->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);
        $this->dockerCompose->setActiveEnvironment($environment)->shouldBeCalledOnce();
        $this->dockerCompose->showServicesLogs($tail ?? 0, $service)
            ->willThrow(new InvalidEnvironmentException('Dummy exception.'))
        ;

        $command = new LogsCommand(
            $this->systemManager->reveal(),
            $this->validator->reveal(),
            $this->dockerCompose->reveal(),
            $this->eventDispatcher->reveal()
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['--tail' => $tail, 'service' => $service],
            ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]
        );

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] Dummy exception.', $display);
        static::assertSame(CommandExitCode::EXCEPTION, $commandTester->getStatusCode());
    }

    public function provideCommandModifiers(): ?\Generator
    {
        yield [null, null];
        yield [50, null];
        yield [50, 'php'];
        yield [null, 'php'];
    }
}
