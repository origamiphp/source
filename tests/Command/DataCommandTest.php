<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\DataCommand;
use App\Helper\CurrentContext;
use App\Middleware\Binary\Docker;
use App\Tests\CustomProphecyTrait;
use App\Tests\TestCommandTrait;
use App\Tests\TestEnvironmentTrait;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\DataCommand
 */
final class DataCommandTest extends WebTestCase
{
    use CustomProphecyTrait;
    use TestCommandTrait;
    use TestEnvironmentTrait;

    public function testItExecutesProcessSuccessfully(): void
    {
        $environment = $this->createEnvironment();
        [$currentContext, $docker] = $this->prophesizeObjectArguments();

        $currentContext->loadEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce();
        $currentContext->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);

        $docker->showResourcesUsage()->shouldBeCalledOnce()->willReturn(true);

        $command = new DataCommand($currentContext->reveal(), $docker->reveal());
        static::assertResultIsSuccessful($command, $environment);
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        $environment = $this->createEnvironment();
        [$currentContext, $docker] = $this->prophesizeObjectArguments();

        $currentContext->loadEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce();
        $currentContext->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);

        $docker->showResourcesUsage()->shouldBeCalledOnce()->willReturn(false);

        $command = new DataCommand($currentContext->reveal(), $docker->reveal());
        static::assertExceptionIsHandled($command);
    }

    /**
     * {@inheritdoc}
     */
    protected function prophesizeObjectArguments(): array
    {
        return [
            $this->prophesize(CurrentContext::class),
            $this->prophesize(Docker::class),
        ];
    }
}
