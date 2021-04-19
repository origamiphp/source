<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\RootCommand;
use App\Environment\Configuration\AbstractConfiguration;
use App\Environment\EnvironmentEntity;
use App\Exception\InvalidEnvironmentException;
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
 * @covers \App\Command\RootCommand
 *
 * @uses \App\Event\AbstractEnvironmentEvent
 */
final class RootCommandTest extends WebTestCase
{
    use CustomProphecyTrait;
    use TestCommandTrait;
    use TestLocationTrait;

    public function testItShowsRootInstructions(): void
    {
        $environment = $this->createEnvironment();
        $environmentVariables = [
            'COMPOSE_FILE' => $environment->getLocation().AbstractConfiguration::INSTALLATION_DIRECTORY.'docker-compose.yml',
            'COMPOSE_PROJECT_NAME' => "{$environment->getType()}_{$environment->getName()}",
            'DOCKER_PHP_IMAGE' => 'default',
            'PROJECT_LOCATION' => $environment->getLocation(),
        ];

        [$currentContext, $docker] = $this->prophesizeObjectArguments();

        $currentContext->getEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce()->willReturn($environment);
        $currentContext->setActiveEnvironment($environment)->shouldBeCalledOnce();
        $docker->getRequiredVariables($environment)->willReturn($environmentVariables);

        $command = new RootCommand($currentContext->reveal(), $docker->reveal());
        static::assertResultIsSuccessful($command, $environment);
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        [$currentContext, $docker] = $this->prophesizeObjectArguments();

        $currentContext->getEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce()->willThrow(InvalidEnvironmentException::class);
        $currentContext->setActiveEnvironment(Argument::type(EnvironmentEntity::class))->shouldNotBeCalled();

        $command = new RootCommand($currentContext->reveal(), $docker->reveal());
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
