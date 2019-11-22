<?php

declare(strict_types=1);

namespace App\Tests\Command\Contextual;

use App\Command\Contextual\DataCommand;
use App\Command\Contextual\PsCommand;
use App\Command\Contextual\RestartCommand;
use App\Command\Contextual\StopCommand;
use App\Exception\InvalidEnvironmentException;
use App\Helper\CommandExitCode;
use App\Middleware\Binary\DockerCompose;
use App\Middleware\SystemManager;
use App\Tests\Command\CustomCommandsTrait;
use Generator;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\Contextual\DataCommand
 * @covers \App\Command\Contextual\PsCommand
 * @covers \App\Command\Contextual\RestartCommand
 * @covers \App\Command\Contextual\StopCommand
 *
 * @uses \App\Event\AbstractEnvironmentEvent
 */
final class CommonCommandTest extends WebTestCase
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
        parent::setUp();

        $this->systemManager = $this->prophesize(SystemManager::class);
        $this->validator = $this->prophesize(ValidatorInterface::class);
        $this->dockerCompose = $this->prophesize(DockerCompose::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
    }

    /**
     * @dataProvider provideCommandDetails
     *
     * @throws InvalidEnvironmentException
     */
    public function testItExecutesProcessSuccessfully(string $classname, string $method, array $messages): void
    {
        $environment = $this->getFakeEnvironment();

        $this->systemManager->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);
        $this->dockerCompose->setActiveEnvironment($environment)->shouldBeCalledOnce();
        $this->dockerCompose->{$method}()->shouldBeCalledOnce()->willReturn(true);

        $command = new $classname(
            $this->systemManager->reveal(),
            $this->validator->reveal(),
            $this->dockerCompose->reveal(),
            $this->eventDispatcher->reveal()
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $display = $commandTester->getDisplay();

        static::assertDisplayIsVerbose($environment, $display);
        if ($messages['success'] !== null) {
            static::assertStringContainsString($messages['success'], $display);
        }
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    /**
     * @dataProvider provideCommandDetails
     *
     * @throws InvalidEnvironmentException
     */
    public function testItGracefullyExitsWhenAnExceptionOccurred(string $classname, string $method, array $messages): void
    {
        $environment = $this->getFakeEnvironment();

        $this->systemManager->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);
        $this->dockerCompose->setActiveEnvironment($environment)->shouldBeCalledOnce();
        $this->dockerCompose->{$method}()->shouldBeCalledOnce()->willReturn(false);

        $command = new $classname(
            $this->systemManager->reveal(),
            $this->validator->reveal(),
            $this->dockerCompose->reveal(),
            $this->eventDispatcher->reveal()
        );

        static::assertExceptionIsHandled($command, $messages['error']);
    }

    public function provideCommandDetails(): ?Generator
    {
        yield [
            DataCommand::class,
            'showResourcesUsage',
            [
                'success' => null,
                'error' => '[ERROR] An error occurred while checking the resources usage.',
            ],
        ];

        yield [
            PsCommand::class,
            'showServicesStatus',
            [
                'success' => null,
                'error' => '[ERROR] An error occurred while checking the services status.',
            ],
        ];

        yield [
            RestartCommand::class,
            'restartServices',
            [
                'success' => '[OK] Docker services successfully restarted.',
                'error' => '[ERROR] An error occurred while restarting the Docker services.',
            ],
        ];

        yield [
            StopCommand::class,
            'stopServices',
            [
                'success' => '[OK] Docker services successfully stopped.',
                'error' => '[ERROR] An error occurred while stopping the Docker services.',
            ],
        ];
    }
}
