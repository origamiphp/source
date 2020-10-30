<?php

declare(strict_types=1);

namespace App\Tests\Command\Contextual\Database;

use App\Command\Contextual\Database\RestoreCommand;
use App\Exception\InvalidEnvironmentException;
use App\Helper\CurrentContext;
use App\Middleware\Binary\DockerCompose;
use App\Tests\Command\TestCommandTrait;
use App\Tests\CustomProphecyTrait;
use App\Tests\TestLocationTrait;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\Contextual\Database\RestoreCommand
 */
final class RestoreCommandTest extends WebTestCase
{
    use CustomProphecyTrait;
    use TestCommandTrait;
    use TestLocationTrait;

    public function testItTriggersTheRestoreProcess(): void
    {
        $environment = $this->createEnvironment();
        [$currentContext, $dockerCompose] = $this->prophesizeObjectArguments();

        $currentContext->getEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce()->willReturn($environment);
        $currentContext->setActiveEnvironment($environment)->shouldBeCalledOnce();

        $dockerCompose->resetDatabaseVolume()->shouldBeCalledOnce()->willReturn(true);
        $dockerCompose->restoreDatabaseVolume()->shouldBeCalledOnce()->willReturn(true);
        $dockerCompose->restartServices()->shouldBeCalledOnce()->willReturn(true);

        $command = new RestoreCommand($currentContext->reveal(), $dockerCompose->reveal());
        static::assertResultIsSuccessful($command, $environment);
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        $environment = $this->createEnvironment();
        [$currentContext, $dockerCompose] = $this->prophesizeObjectArguments();

        $currentContext->getEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce()->willReturn($environment);
        $currentContext->setActiveEnvironment($environment)->shouldBeCalledOnce()->willThrow(InvalidEnvironmentException::class);

        $command = new RestoreCommand($currentContext->reveal(), $dockerCompose->reveal());
        static::assertExceptionIsHandled($command);
    }

    /**
     * {@inheritdoc}
     */
    protected function prophesizeObjectArguments(): array
    {
        return [
            $this->prophesize(CurrentContext::class),
            $this->prophesize(DockerCompose::class),
        ];
    }
}
