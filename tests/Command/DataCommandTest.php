<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\DataCommand;
use App\Helper\CurrentContext;
use App\Middleware\Binary\DockerCompose;
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
 * @covers \App\Command\DataCommand
 */
final class DataCommandTest extends WebTestCase
{
    use CustomProphecyTrait;
    use TestCommandTrait;
    use TestLocationTrait;

    public function testItExecutesProcessSuccessfully(): void
    {
        $environment = $this->createEnvironment();

        [$currentContext, $dockerCompose] = $this->prophesizeObjectArguments();

        $currentContext->getEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce()->willReturn($environment);
        $currentContext->setActiveEnvironment($environment)->shouldBeCalledOnce();
        $dockerCompose->showResourcesUsage()->shouldBeCalledOnce()->willReturn(true);

        $command = new DataCommand($currentContext->reveal(), $dockerCompose->reveal());
        static::assertResultIsSuccessful($command, $environment);
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        $environment = $this->createEnvironment();

        [$currentContext, $dockerCompose] = $this->prophesizeObjectArguments();

        $command = new DataCommand($currentContext->reveal(), $dockerCompose->reveal());
        $currentContext->getEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce()->willReturn($environment);
        $currentContext->setActiveEnvironment($environment)->shouldBeCalledOnce();
        $dockerCompose->showResourcesUsage()->shouldBeCalledOnce()->willReturn(false);

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