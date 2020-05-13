<?php

declare(strict_types=1);

namespace App\Tests\Command\Main;

use App\Command\Main\PrepareCommand;
use App\Helper\CommandExitCode;
use App\Helper\CurrentContext;
use App\Middleware\Binary\DockerCompose;
use App\Tests\Command\AbstractCommandWebTestCase;
use App\Tests\TestFakeEnvironmentTrait;
use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\Main\PrepareCommand
 *
 * @uses \App\Event\AbstractEnvironmentEvent
 */
final class PrepareCommandTest extends AbstractCommandWebTestCase
{
    use TestFakeEnvironmentTrait;

    /** @var ObjectProphecy */
    private $currentContext;

    /** @var ObjectProphecy */
    private $dockerCompose;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->currentContext = $this->prophet->prophesize(CurrentContext::class);
        $this->dockerCompose = $this->prophet->prophesize(DockerCompose::class);
    }

    public function testItPreparesTheActiveEnvironment(): void
    {
        $environment = $this->getFakeEnvironment();

        (new MethodProphecy($this->currentContext, 'getEnvironment', [Argument::type(InputInterface::class)]))
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        (new MethodProphecy($this->dockerCompose, 'prepareServices', []))
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $commandTester = new CommandTester($this->getCommand());
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $display = $commandTester->getDisplay();

        static::assertDisplayIsVerbose($environment, $display);
        static::assertStringContainsString('[OK] Docker services successfully prepared.', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        $environment = $this->getFakeEnvironment();

        (new MethodProphecy($this->currentContext, 'getEnvironment', [Argument::type(InputInterface::class)]))
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        (new MethodProphecy($this->dockerCompose, 'prepareServices', []))
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $commandTester = new CommandTester($this->getCommand());
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] An error occurred while preparing the Docker services.', $display);
        static::assertSame(CommandExitCode::EXCEPTION, $commandTester->getStatusCode());
    }

    /**
     * Retrieves the \App\Command\Contextual\PrepareCommand instance to use within the tests.
     */
    private function getCommand(): PrepareCommand
    {
        return new PrepareCommand(
            $this->currentContext->reveal(),
            $this->dockerCompose->reveal()
        );
    }
}
