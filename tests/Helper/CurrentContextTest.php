<?php

declare(strict_types=1);

namespace App\Tests\Helper;

use App\Exception\InvalidEnvironmentException;
use App\Helper\CurrentContext;
use App\Helper\ProcessProxy;
use App\Middleware\Binary\DockerCompose;
use App\Middleware\Database;
use App\Tests\TestFakeEnvironmentTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @internal
 *
 * @covers \App\Helper\CurrentContext
 */
final class CurrentContextTest extends TestCase
{
    use TestFakeEnvironmentTrait;

    /** @var Prophet */
    private $prophet;

    /** @var ObjectProphecy */
    private $database;

    /** @var ObjectProphecy */
    private $processProxy;

    /** @var ObjectProphecy */
    private $dockerCompose;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prophet = new Prophet();
        $this->database = $this->prophet->prophesize(Database::class);
        $this->processProxy = $this->prophet->prophesize(ProcessProxy::class);
        $this->dockerCompose = $this->prophet->prophesize(DockerCompose::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->prophet->checkPredictions();
    }

    /**
     * @throws InvalidEnvironmentException
     */
    public function testItRetrieveTheActiveEnvironment(): void
    {
        $environment = $this->getFakeEnvironment();
        $input = $this->prophet->prophesize(InputInterface::class);

        (new MethodProphecy($this->database, 'getActiveEnvironment', []))
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $currentContext = new CurrentContext(
            $this->database->reveal(),
            $this->processProxy->reveal(),
            $this->dockerCompose->reveal()
        );

        static::assertSame($environment, $currentContext->getEnvironment($input->reveal()));
    }

    /**
     * @throws InvalidEnvironmentException
     */
    public function testItRetrieveTheEnvironmentFromInput(): void
    {
        $environment = $this->getFakeEnvironment();
        $input = $this->prophet->prophesize(InputInterface::class);

        (new MethodProphecy($this->database, 'getActiveEnvironment', []))
            ->shouldBeCalledOnce()
            ->willReturn(null)
        ;

        (new MethodProphecy($input, 'hasArgument', ['environment']))
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        (new MethodProphecy($input, 'getArgument', ['environment']))
            ->shouldBeCalledOnce()
            ->willReturn('origami')
        ;

        (new MethodProphecy($this->database, 'getEnvironmentByName', ['origami']))
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $currentContext = new CurrentContext(
            $this->database->reveal(),
            $this->processProxy->reveal(),
            $this->dockerCompose->reveal()
        );

        static::assertSame($environment, $currentContext->getEnvironment($input->reveal()));
    }

    /**
     * @throws InvalidEnvironmentException
     */
    public function testItRetrieveTheEnvironmentFromLocation(): void
    {
        $environment = $this->getFakeEnvironment();
        $input = $this->prophet->prophesize(InputInterface::class);

        (new MethodProphecy($this->database, 'getActiveEnvironment', []))
            ->shouldBeCalledOnce()
            ->willReturn(null)
        ;

        (new MethodProphecy($input, 'hasArgument', ['environment']))
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        (new MethodProphecy($input, 'getArgument', ['environment']))
            ->shouldBeCalledOnce()
            ->willReturn('origami')
        ;

        (new MethodProphecy($this->database, 'getEnvironmentByName', ['origami']))
            ->shouldBeCalledOnce()
            ->willReturn(null)
        ;

        (new MethodProphecy($this->processProxy, 'getWorkingDirectory', []))
            ->shouldBeCalledOnce()
            ->willReturn('.')
        ;

        (new MethodProphecy($this->database, 'getEnvironmentByLocation', ['.']))
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $currentContext = new CurrentContext(
            $this->database->reveal(),
            $this->processProxy->reveal(),
            $this->dockerCompose->reveal()
        );

        static::assertSame($environment, $currentContext->getEnvironment($input->reveal()));
    }

    /**
     * @throws InvalidEnvironmentException
     */
    public function testItThrowsAnExceptionWithoutEnvironment(): void
    {
        (new MethodProphecy($this->processProxy, 'getWorkingDirectory', []))
            ->shouldBeCalledOnce()
            ->willReturn('.')
        ;

        $currentContext = new CurrentContext(
            $this->database->reveal(),
            $this->processProxy->reveal(),
            $this->dockerCompose->reveal()
        );

        $this->expectExceptionObject(
            new InvalidEnvironmentException(
                'An environment must be given, please consider using the install command instead.'
            )
        );

        $currentContext->getEnvironment($this->prophet->prophesize(InputInterface::class)->reveal());
    }
}
