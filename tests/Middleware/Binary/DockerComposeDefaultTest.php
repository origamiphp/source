<?php

declare(strict_types=1);

namespace App\Tests\Middleware\Binary;

use App\Environment\Configuration\AbstractConfiguration;
use App\Environment\EnvironmentEntity;
use App\Exception\InvalidConfigurationException;
use App\Helper\ProcessFactory;
use App\Middleware\Binary\DockerCompose;
use App\Tests\TestDockerComposeTrait;
use App\Validator\Constraints\ConfigurationFiles;
use App\Validator\Constraints\DotEnvExists;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Process\Process;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 *
 * @covers \App\Middleware\Binary\DockerCompose
 */
final class DockerComposeDefaultTest extends WebTestCase
{
    use ProphecyTrait;
    use TestDockerComposeTrait;

    /** @var EnvironmentEntity */
    protected $environment;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->createLocation();
        $this->prepareLocation();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->removeLocation();
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function testItDefinesTheActiveEnvironmentWithInternals(): void
    {
        $validator = $this->prophesize(ValidatorInterface::class);
        $processFactory = $this->prophesize(ProcessFactory::class);

        $validator->validate(Argument::any(), new DotEnvExists())->shouldBeCalledOnce()->willReturn(new ConstraintViolationList());
        $validator->validate(Argument::any(), new ConfigurationFiles())->shouldBeCalledOnce()->willReturn(new ConstraintViolationList());

        $dockerCompose = new DockerCompose($validator->reveal(), $processFactory->reveal());
        $dockerCompose->setActiveEnvironment($this->environment);

        $variables = $dockerCompose->getRequiredVariables($this->environment);

        static::assertArrayHasKey('COMPOSE_FILE', $variables);
        static::assertSame($this->location.AbstractConfiguration::INSTALLATION_DIRECTORY.'docker-compose.yml', $variables['COMPOSE_FILE']);

        static::assertArrayHasKey('COMPOSE_PROJECT_NAME', $variables);
        static::assertSame('symfony_foo', $variables['COMPOSE_PROJECT_NAME']);

        static::assertArrayHasKey('DOCKER_PHP_IMAGE', $variables);
        static::assertSame('', $variables['DOCKER_PHP_IMAGE']);

        static::assertArrayHasKey('PROJECT_LOCATION', $variables);
        static::assertSame($this->location, $variables['PROJECT_LOCATION']);
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function testItDefinesTheActiveEnvironmentWithExternals(): void
    {
        $environment = new EnvironmentEntity('bar', $this->location, EnvironmentEntity::TYPE_CUSTOM, null, true);

        $validator = $this->prophesize(ValidatorInterface::class);
        $processFactory = $this->prophesize(ProcessFactory::class);

        $validator->validate(Argument::any(), new DotEnvExists())->shouldNotBeCalled();
        $validator->validate(Argument::any(), new ConfigurationFiles())->shouldNotBeCalled();

        $dockerCompose = new DockerCompose($validator->reveal(), $processFactory->reveal());
        $dockerCompose->setActiveEnvironment($environment);

        $variables = $dockerCompose->getRequiredVariables($environment);

        static::assertArrayHasKey('COMPOSE_FILE', $variables);
        static::assertSame("{$this->location}/docker-compose.yml", $variables['COMPOSE_FILE']);

        static::assertArrayHasKey('COMPOSE_PROJECT_NAME', $variables);
        static::assertSame('custom_bar', $variables['COMPOSE_PROJECT_NAME']);

        static::assertArrayNotHasKey('DOCKER_PHP_IMAGE', $variables);

        static::assertArrayHasKey('PROJECT_LOCATION', $variables);
        static::assertSame($this->location, $variables['PROJECT_LOCATION']);
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function testItThrowsAnExceptionWithMissingDotEnvFile(): void
    {
        $validator = $this->prophesize(ValidatorInterface::class);

        $violation = $this->prophesize(ConstraintViolation::class);
        $errors = new ConstraintViolationList();
        $errors->add($violation->reveal());

        $processFactory = $this->prophesize(ProcessFactory::class);

        $violation->getMessage()->shouldBeCalledOnce()->willReturn('Dummy exception.');
        $validator->validate(Argument::any(), new DotEnvExists())->shouldBeCalledOnce()->willReturn($errors);
        $validator->validate(Argument::any(), new ConfigurationFiles())->shouldNotBeCalled();
        $this->expectException(InvalidConfigurationException::class);

        $dockerCompose = new DockerCompose($validator->reveal(), $processFactory->reveal());
        $dockerCompose->setActiveEnvironment($this->environment);
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function testItThrowsAnExceptionWithMissingConfigurationFiles(): void
    {
        $validator = $this->prophesize(ValidatorInterface::class);

        $violation = $this->prophesize(ConstraintViolation::class);
        $errors = new ConstraintViolationList();
        $errors->add($violation->reveal());

        $processFactory = $this->prophesize(ProcessFactory::class);

        $violation->getMessage()->shouldBeCalledOnce()->willReturn('Dummy exception.');
        $validator->validate(Argument::any(), new DotEnvExists())->shouldBeCalledOnce()->willReturn(new ConstraintViolationList());
        $validator->validate(Argument::any(), new ConfigurationFiles())->shouldBeCalledOnce()->willReturn($errors);
        $this->expectException(InvalidConfigurationException::class);

        $dockerCompose = new DockerCompose($validator->reveal(), $processFactory->reveal());
        $dockerCompose->setActiveEnvironment($this->environment);
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function testItPreparesTheEnvironmentServices(): void
    {
        $commands = [
            ['docker-compose', 'pull'],
            ['docker-compose', 'build', '--pull', '--parallel'],
        ];
        $environmentVariables = $this->getFakeEnvironmentVariables();

        $validator = $this->prophesize(ValidatorInterface::class);
        $processFactory = $this->prophesize(ProcessFactory::class);
        $process = $this->prophesize(Process::class);

        $validator->validate(Argument::any(), new DotEnvExists())->shouldBeCalledOnce()->willReturn(new ConstraintViolationList());
        $validator->validate(Argument::any(), new ConfigurationFiles())->shouldBeCalledOnce()->willReturn(new ConstraintViolationList());
        $process->isSuccessful()->shouldBeCalledTimes(2)->willReturn(true);
        $processFactory->runForegroundProcess($commands[0], $environmentVariables)->shouldBeCalledOnce()->willReturn($process->reveal());
        $processFactory->runForegroundProcess($commands[1], $environmentVariables)->shouldBeCalledOnce()->willReturn($process->reveal());

        $dockerCompose = new DockerCompose($validator->reveal(), $processFactory->reveal());
        $dockerCompose->setActiveEnvironment($this->environment);

        static::assertTrue($dockerCompose->prepareServices());
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function testItShowsResourcesUsage(): void
    {
        $command = 'docker-compose ps -q | xargs docker stats';
        $dockerCompose = $this->prepareForegroundFromShellCommand($command);

        static::assertTrue($dockerCompose->showResourcesUsage());
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function testItShowsServicesStatus(): void
    {
        $command = ['docker-compose', 'ps'];
        $dockerCompose = $this->prepareForegroundCommand($command);

        static::assertTrue($dockerCompose->showServicesStatus());
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function testItRestartsServicesStatus(): void
    {
        $command = ['docker-compose', 'restart'];
        $dockerCompose = $this->prepareForegroundCommand($command);

        static::assertTrue($dockerCompose->restartServices());
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function testItStartsServicesStatus(): void
    {
        $command = ['docker-compose', 'up', '--build', '--detach', '--remove-orphans'];
        $dockerCompose = $this->prepareForegroundCommand($command);

        static::assertTrue($dockerCompose->startServices());
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function testItStopsServicesStatus(): void
    {
        $command = ['docker-compose', 'stop'];
        $dockerCompose = $this->prepareForegroundCommand($command);

        static::assertTrue($dockerCompose->stopServices());
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function testItRemovesServicesStatus(): void
    {
        $command = ['docker-compose', 'down', '--rmi', 'local', '--volumes', '--remove-orphans'];
        $dockerCompose = $this->prepareForegroundCommand($command);

        static::assertTrue($dockerCompose->removeServices());
    }
}
