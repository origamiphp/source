<?php

declare(strict_types=1);

namespace App\Tests\Helper;

use App\Exception\FilesystemException;
use App\Exception\InvalidEnvironmentException;
use App\Helper\CurrentContext;
use App\Helper\ProcessProxy;
use App\Middleware\Binary\DockerCompose;
use App\Middleware\Database;
use App\Tests\TestFakeEnvironmentTrait;
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
    use TestFakeEnvironmentTrait;

    /**
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     */
    public function testItRetrieveTheActiveEnvironment(): void
    {
        $environment = $this->getFakeEnvironment();

        $database = $this->prophesize(Database::class);
        $processProxy = $this->prophesize(ProcessProxy::class);
        $dockerCompose = $this->prophesize(DockerCompose::class);
        $input = $this->prophesize(InputInterface::class);

        $database->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);

        $currentContext = new CurrentContext($database->reveal(), $processProxy->reveal(), $dockerCompose->reveal());
        static::assertSame($environment, $currentContext->getEnvironment($input->reveal()));
    }

    /**
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     */
    public function testItRetrieveTheEnvironmentFromInput(): void
    {
        $environment = $this->getFakeEnvironment();

        $database = $this->prophesize(Database::class);
        $processProxy = $this->prophesize(ProcessProxy::class);
        $dockerCompose = $this->prophesize(DockerCompose::class);
        $input = $this->prophesize(InputInterface::class);

        $database->getActiveEnvironment()->shouldBeCalledOnce()->willReturn(null);
        $input->hasArgument('environment')->shouldBeCalledOnce()->willReturn(true);
        $input->getArgument('environment')->shouldBeCalledOnce()->willReturn('origami');
        $database->getEnvironmentByName('origami')->shouldBeCalledOnce()->willReturn($environment);

        $currentContext = new CurrentContext($database->reveal(), $processProxy->reveal(), $dockerCompose->reveal());
        static::assertSame($environment, $currentContext->getEnvironment($input->reveal()));
    }

    /**
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     */
    public function testItRetrieveTheEnvironmentFromLocation(): void
    {
        $environment = $this->getFakeEnvironment();

        $database = $this->prophesize(Database::class);
        $processProxy = $this->prophesize(ProcessProxy::class);
        $dockerCompose = $this->prophesize(DockerCompose::class);
        $input = $this->prophesize(InputInterface::class);

        $database->getActiveEnvironment()->shouldBeCalledOnce()->willReturn(null);
        $input->hasArgument('environment')->shouldBeCalledOnce()->willReturn(true);
        $input->getArgument('environment')->shouldBeCalledOnce()->willReturn('origami');
        $database->getEnvironmentByName('origami')->shouldBeCalledOnce()->willReturn(null);
        $processProxy->getWorkingDirectory()->shouldBeCalledOnce()->willReturn('.');
        $database->getEnvironmentByLocation('.')->shouldBeCalledOnce()->willReturn($environment);

        $currentContext = new CurrentContext($database->reveal(), $processProxy->reveal(), $dockerCompose->reveal());
        static::assertSame($environment, $currentContext->getEnvironment($input->reveal()));
    }

    /**
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     */
    public function testItThrowsAnExceptionWithoutEnvironment(): void
    {
        $database = $this->prophesize(Database::class);
        $processProxy = $this->prophesize(ProcessProxy::class);
        $dockerCompose = $this->prophesize(DockerCompose::class);

        $processProxy->getWorkingDirectory()->shouldBeCalledOnce()->willReturn('.');

        $currentContext = new CurrentContext($database->reveal(), $processProxy->reveal(), $dockerCompose->reveal());
        $this->expectException(InvalidEnvironmentException::class);
        $currentContext->getEnvironment($this->prophesize(InputInterface::class)->reveal());
    }
}
