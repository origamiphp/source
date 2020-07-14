<?php

declare(strict_types=1);

namespace App\Tests\Helper;

use App\Environment\Configuration\ConfigurationInstaller;
use App\Exception\FilesystemException;
use App\Exception\InvalidConfigurationException;
use App\Exception\InvalidEnvironmentException;
use App\Helper\CurrentContext;
use App\Helper\ProcessProxy;
use App\Helper\Validator;
use App\Middleware\Binary\DockerCompose;
use App\Middleware\Database;
use App\Tests\TestLocationTrait;
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
    use TestLocationTrait;

    /**
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     */
    public function testItRetrieveTheActiveEnvironment(): void
    {
        $environment = $this->createEnvironment();

        [$database, $processProxy, $dockerCompose, $validator] = $this->prophesizeCurrentContextArguments();
        $input = $this->prophesize(InputInterface::class);

        $database->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);

        $currentContext = new CurrentContext($database->reveal(), $processProxy->reveal(), $dockerCompose->reveal(), $validator->reveal());
        static::assertSame($environment, $currentContext->getEnvironment($input->reveal()));
    }

    /**
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     */
    public function testItRetrieveTheEnvironmentFromInput(): void
    {
        $environment = $this->createEnvironment();

        [$database, $processProxy, $dockerCompose, $validator] = $this->prophesizeCurrentContextArguments();
        $input = $this->prophesize(InputInterface::class);

        $database->getActiveEnvironment()->shouldBeCalledOnce()->willReturn(null);
        $input->hasArgument('environment')->shouldBeCalledOnce()->willReturn(true);
        $input->getArgument('environment')->shouldBeCalledOnce()->willReturn('origami');
        $database->getEnvironmentByName('origami')->shouldBeCalledOnce()->willReturn($environment);

        $currentContext = new CurrentContext($database->reveal(), $processProxy->reveal(), $dockerCompose->reveal(), $validator->reveal());
        static::assertSame($environment, $currentContext->getEnvironment($input->reveal()));
    }

    /**
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     */
    public function testItRetrieveTheEnvironmentFromLocation(): void
    {
        $environment = $this->createEnvironment();

        [$database, $processProxy, $dockerCompose, $validator] = $this->prophesizeCurrentContextArguments();
        $input = $this->prophesize(InputInterface::class);

        $database->getActiveEnvironment()->shouldBeCalledOnce()->willReturn(null);
        $input->hasArgument('environment')->shouldBeCalledOnce()->willReturn(true);
        $input->getArgument('environment')->shouldBeCalledOnce()->willReturn('origami');
        $database->getEnvironmentByName('origami')->shouldBeCalledOnce()->willReturn(null);
        $processProxy->getWorkingDirectory()->shouldBeCalledOnce()->willReturn('.');
        $database->getEnvironmentByLocation('.')->shouldBeCalledOnce()->willReturn($environment);

        $currentContext = new CurrentContext($database->reveal(), $processProxy->reveal(), $dockerCompose->reveal(), $validator->reveal());
        static::assertSame($environment, $currentContext->getEnvironment($input->reveal()));
    }

    /**
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     */
    public function testItThrowsAnExceptionWithoutEnvironment(): void
    {
        [$database, $processProxy, $dockerCompose, $validator] = $this->prophesizeCurrentContextArguments();

        $processProxy->getWorkingDirectory()->shouldBeCalledOnce()->willReturn('.');

        $currentContext = new CurrentContext($database->reveal(), $processProxy->reveal(), $dockerCompose->reveal(), $validator->reveal());
        $this->expectException(InvalidEnvironmentException::class);
        $currentContext->getEnvironment($this->prophesize(InputInterface::class)->reveal());
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function testItRefreshEnvironmentVariables(): void
    {
        $environment = $this->createEnvironment();
        touch($this->location.ConfigurationInstaller::INSTALLATION_DIRECTORY.'.env');

        [$database, $processProxy, $dockerCompose, $validator] = $this->prophesizeCurrentContextArguments();

        $validator->validateDotEnvExistence($environment)->shouldBeCalledOnce()->willReturn(true);
        $validator->validateConfigurationFiles($environment)->shouldBeCalledOnce()->willReturn(true);
        $dockerCompose->refreshEnvironmentVariables($environment)->shouldBeCalledOnce();

        $currentContext = new CurrentContext($database->reveal(), $processProxy->reveal(), $dockerCompose->reveal(), $validator->reveal());
        $currentContext->setActiveEnvironment($environment);
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function testItThrowsAnExceptionWithMissingDotEnvFile(): void
    {
        $environment = $this->createEnvironment();

        [$database, $processProxy, $dockerCompose, $validator] = $this->prophesizeCurrentContextArguments();

        $validator->validateDotEnvExistence($environment)->shouldBeCalledOnce()->willReturn(false);
        $validator->validateConfigurationFiles($environment)->shouldNotBeCalled();
        $this->expectException(InvalidConfigurationException::class);

        $currentContext = new CurrentContext($database->reveal(), $processProxy->reveal(), $dockerCompose->reveal(), $validator->reveal());
        $currentContext->setActiveEnvironment($environment);
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function testItThrowsAnExceptionWithMissingConfigurationFiles(): void
    {
        $environment = $this->createEnvironment();
        touch($this->location.ConfigurationInstaller::INSTALLATION_DIRECTORY.'.env');

        [$database, $processProxy, $dockerCompose, $validator] = $this->prophesizeCurrentContextArguments();

        $validator->validateDotEnvExistence($environment)->shouldBeCalledOnce()->willReturn(true);
        $validator->validateConfigurationFiles($environment)->shouldBeCalledOnce()->willReturn(false);
        $this->expectException(InvalidConfigurationException::class);

        $currentContext = new CurrentContext($database->reveal(), $processProxy->reveal(), $dockerCompose->reveal(), $validator->reveal());
        $currentContext->setActiveEnvironment($environment);
    }

    /**
     * Prophesizes arguments needed by the \App\Helper\CurrentContext class.
     */
    private function prophesizeCurrentContextArguments(): array
    {
        return [
            $this->prophesize(Database::class),
            $this->prophesize(ProcessProxy::class),
            $this->prophesize(DockerCompose::class),
            $this->prophesize(Validator::class),
        ];
    }
}
