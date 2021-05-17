<?php

declare(strict_types=1);

namespace App\Tests\Helper;

use App\Exception\FilesystemException;
use App\Exception\InvalidConfigurationException;
use App\Exception\InvalidEnvironmentException;
use App\Helper\CurrentContext;
use App\Helper\ProcessProxy;
use App\Helper\Validator;
use App\Middleware\Database;
use App\Tests\TestEnvironmentTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @internal
 *
 * @covers \App\Helper\CurrentContext
 */
final class CurrentContextTest extends TestCase
{
    use ProphecyTrait;
    use TestEnvironmentTrait;

    /**
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     * @throws InvalidConfigurationException
     */
    public function testItRetrieveTheActiveEnvironment(): void
    {
        $database = $this->prophesize(Database::class);
        $processProxy = $this->prophesize(ProcessProxy::class);
        $validator = $this->prophesize(Validator::class);

        $environment = $this->createEnvironment();
        $this->installEnvironmentConfiguration($environment);

        $database
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $validator
            ->validateDotEnvExistence($environment)
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $validator
            ->validateConfigurationFiles($environment)
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $currentContext = new CurrentContext($database->reveal(), $processProxy->reveal(), $validator->reveal());
        $currentContext->loadEnvironment($this->prophesize(InputInterface::class)->reveal());
        static::assertSame($environment, $currentContext->getActiveEnvironment());
        static::assertSame("{$environment->getType()}_{$environment->getName()}", $currentContext->getProjectName());
    }

    /**
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     * @throws InvalidConfigurationException
     */
    public function testItRetrieveTheEnvironmentFromInput(): void
    {
        $database = $this->prophesize(Database::class);
        $processProxy = $this->prophesize(ProcessProxy::class);
        $validator = $this->prophesize(Validator::class);

        $environment = $this->createEnvironment();
        $this->installEnvironmentConfiguration($environment);
        $input = $this->prophesize(InputInterface::class);

        $database
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn(null)
        ;

        $input
            ->hasArgument('environment')
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $input
            ->getArgument('environment')
            ->shouldBeCalledOnce()
            ->willReturn('origami')
        ;

        $database
            ->getEnvironmentByName('origami')
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $validator
            ->validateDotEnvExistence($environment)
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $validator
            ->validateConfigurationFiles($environment)
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $currentContext = new CurrentContext($database->reveal(), $processProxy->reveal(), $validator->reveal());
        $currentContext->loadEnvironment($input->reveal());
        static::assertSame($environment, $currentContext->getActiveEnvironment());
        static::assertSame("{$environment->getType()}_{$environment->getName()}", $currentContext->getProjectName());
    }

    /**
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     * @throws InvalidConfigurationException
     */
    public function testItRetrieveTheEnvironmentFromLocation(): void
    {
        $database = $this->prophesize(Database::class);
        $processProxy = $this->prophesize(ProcessProxy::class);
        $validator = $this->prophesize(Validator::class);

        $environment = $this->createEnvironment();
        $this->installEnvironmentConfiguration($environment);
        $input = $this->prophesize(InputInterface::class);

        $database
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn(null)
        ;

        $input
            ->hasArgument('environment')
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $input
            ->getArgument('environment')
            ->shouldBeCalledOnce()
            ->willReturn('origami')
        ;

        $database
            ->getEnvironmentByName('origami')
            ->shouldBeCalledOnce()
            ->willReturn(null)
        ;

        $processProxy
            ->getWorkingDirectory()
            ->shouldBeCalledOnce()->willReturn('.')
        ;

        $database
            ->getEnvironmentByLocation('.')
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $validator
            ->validateDotEnvExistence($environment)
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $validator
            ->validateConfigurationFiles($environment)
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $currentContext = new CurrentContext($database->reveal(), $processProxy->reveal(), $validator->reveal());
        $currentContext->loadEnvironment($input->reveal());
        static::assertSame($environment, $currentContext->getActiveEnvironment());
        static::assertSame("{$environment->getType()}_{$environment->getName()}", $currentContext->getProjectName());
    }

    /**
     * @throws FilesystemException
     * @throws InvalidConfigurationException
     */
    public function testItThrowsAnExceptionWithoutEnvironment(): void
    {
        $database = $this->prophesize(Database::class);
        $processProxy = $this->prophesize(ProcessProxy::class);
        $validator = $this->prophesize(Validator::class);

        $processProxy
            ->getWorkingDirectory()
            ->shouldBeCalledOnce()
            ->willReturn('.')
        ;

        $currentContext = new CurrentContext($database->reveal(), $processProxy->reveal(), $validator->reveal());
        $this->expectException(InvalidEnvironmentException::class);
        $currentContext->loadEnvironment($this->prophesize(InputInterface::class)->reveal());
    }

    /**
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     * @throws InvalidConfigurationException
     */
    public function testItThrowsAnExceptionWithMissingDotEnvFile(): void
    {
        $database = $this->prophesize(Database::class);
        $processProxy = $this->prophesize(ProcessProxy::class);
        $validator = $this->prophesize(Validator::class);

        $environment = $this->createEnvironment();
        $this->installEnvironmentConfiguration($environment);

        $database
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $validator
            ->validateDotEnvExistence($environment)
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $validator
            ->validateConfigurationFiles($environment)
            ->shouldNotBeCalled()
        ;

        $this->expectException(InvalidConfigurationException::class);

        $currentContext = new CurrentContext($database->reveal(), $processProxy->reveal(), $validator->reveal());
        $currentContext->loadEnvironment($this->prophesize(InputInterface::class)->reveal());
    }

    /**
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     * @throws InvalidConfigurationException
     */
    public function testItThrowsAnExceptionWithMissingConfigurationFiles(): void
    {
        $database = $this->prophesize(Database::class);
        $processProxy = $this->prophesize(ProcessProxy::class);
        $validator = $this->prophesize(Validator::class);

        $environment = $this->createEnvironment();
        $this->installEnvironmentConfiguration($environment);

        $database
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $validator
            ->validateDotEnvExistence($environment)
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $validator
            ->validateConfigurationFiles($environment)
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $this->expectException(InvalidConfigurationException::class);

        $currentContext = new CurrentContext($database->reveal(), $processProxy->reveal(), $validator->reveal());
        $currentContext->loadEnvironment($this->prophesize(InputInterface::class)->reveal());
    }
}
