<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\PsCommand;
use App\Helper\CurrentContext;
use App\Middleware\Binary\Docker;
use App\Tests\CustomProphecyTrait;
use App\Tests\TestCommandTrait;
use App\Tests\TestLocationTrait;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\PsCommand
 */
final class PsCommandTest extends WebTestCase
{
    use CustomProphecyTrait;
    use TestCommandTrait;
    use TestLocationTrait;

    public function testItExecutesProcessSuccessfully(): void
    {
        $environment = $this->createEnvironment();

        [$currentContext, $docker] = $this->prophesizeObjectArguments();

        $currentContext->getEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce()->willReturn($environment);
        $currentContext->setActiveEnvironment($environment)->shouldBeCalledOnce();
        $docker->showServicesStatus()->shouldBeCalledOnce()->willReturn(true);

        $command = new PsCommand($currentContext->reveal(), $docker->reveal());
        static::assertResultIsSuccessful($command, $environment);
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        $environment = $this->createEnvironment();

        [$currentContext, $docker] = $this->prophesizeObjectArguments();

        $currentContext->getEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce()->willReturn($environment);
        $currentContext->setActiveEnvironment($environment)->shouldBeCalledOnce();
        $docker->showServicesStatus()->shouldBeCalledOnce()->willReturn(false);

        $command = new PsCommand($currentContext->reveal(), $docker->reveal());
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
