<?php

declare(strict_types=1);

namespace App\Tests\Middleware\Binary;

use App\Entity\Environment;
use App\Exception\InvalidEnvironmentException;
use App\Middleware\Binary\DockerCompose;
use App\Tests\AbstractDockerComposeTestCase;
use App\Validator\Constraints\ConfigurationFiles;
use App\Validator\Constraints\DotEnvExists;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Process\Process;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @internal
 *
 * @covers \App\Middleware\Binary\DockerCompose
 */
final class DockerComposeDefaultTest extends AbstractDockerComposeTestCase
{
    /**
     * @throws InvalidEnvironmentException
     */
    public function testItDefinesTheActiveEnvironmentWithInternals(): void
    {
        $this->initializeSuccessfulValidators();

        $dockerCompose = new DockerCompose($this->validator->reveal(), $this->processFactory->reveal());
        $dockerCompose->setActiveEnvironment($this->environment);

        $variables = $dockerCompose->getRequiredVariables();

        static::assertArrayHasKey('COMPOSE_FILE', $variables);
        static::assertSame($this->location.'/var/docker/docker-compose.yml', $variables['COMPOSE_FILE']);

        static::assertArrayHasKey('COMPOSE_PROJECT_NAME', $variables);
        static::assertSame('symfony_foo', $variables['COMPOSE_PROJECT_NAME']);

        static::assertArrayHasKey('DOCKER_PHP_IMAGE', $variables);
        static::assertSame('default', $variables['DOCKER_PHP_IMAGE']);

        static::assertArrayHasKey('PROJECT_LOCATION', $variables);
        static::assertSame($this->location, $variables['PROJECT_LOCATION']);
    }

    /**
     * @throws InvalidEnvironmentException
     */
    public function testItDefinesTheActiveEnvironmentWithExternals(): void
    {
        $this->initializeSuccessfulValidators();

        $dockerCompose = new DockerCompose($this->validator->reveal(), $this->processFactory->reveal());
        $dockerCompose->setActiveEnvironment(
            new Environment('bar', $this->location, Environment::TYPE_CUSTOM, null, true)
        );
        $variables = $dockerCompose->getRequiredVariables();

        static::assertArrayHasKey('COMPOSE_FILE', $variables);
        static::assertSame($this->location.'/docker-compose.yml', $variables['COMPOSE_FILE']);

        static::assertArrayHasKey('COMPOSE_PROJECT_NAME', $variables);
        static::assertSame('custom_bar', $variables['COMPOSE_PROJECT_NAME']);

        static::assertArrayNotHasKey('DOCKER_PHP_IMAGE', $variables);

        static::assertArrayHasKey('PROJECT_LOCATION', $variables);
        static::assertSame($this->location, $variables['PROJECT_LOCATION']);
    }

    /**
     * @throws InvalidEnvironmentException
     */
    public function testItThrowsAnExceptionWithMissingDotEnvFile(): void
    {
        $violation = $this->prophesize(ConstraintViolation::class);
        $violation->getMessage()->shouldBeCalledOnce()->willReturn('Dummy exception.');

        $errors = new ConstraintViolationList();
        $errors->add($violation->reveal());

        $this->validator->validate(Argument::any(), new DotEnvExists())
            ->shouldBeCalledOnce()
            ->willReturn($errors)
        ;
        $this->validator->validate(Argument::any(), new ConfigurationFiles())->shouldNotBeCalled();

        $this->expectException(InvalidEnvironmentException::class);

        $dockerCompose = new DockerCompose($this->validator->reveal(), $this->processFactory->reveal());
        $dockerCompose->setActiveEnvironment($this->environment);
    }

    /**
     * @throws InvalidEnvironmentException
     */
    public function testItThrowsAnExceptionWithMissingConfigurationFiles(): void
    {
        $violation = $this->prophesize(ConstraintViolation::class);
        $violation->getMessage()->shouldBeCalledOnce()->willReturn('Dummy exception.');

        $errors = new ConstraintViolationList();
        $errors->add($violation->reveal());

        $this->validator->validate(Argument::any(), new DotEnvExists())
            ->shouldBeCalledOnce()
            ->willReturn(new ConstraintViolationList())
        ;
        $this->validator->validate(Argument::any(), new ConfigurationFiles())
            ->shouldBeCalledOnce()
            ->willReturn($errors)
        ;

        $this->expectException(InvalidEnvironmentException::class);

        $dockerCompose = new DockerCompose($this->validator->reveal(), $this->processFactory->reveal());
        $dockerCompose->setActiveEnvironment($this->environment);
    }

    /**
     * @throws InvalidEnvironmentException
     */
    public function testItPreparesTheEnvironmentServices(): void
    {
        $this->initializeSuccessfulValidators();

        $process = $this->prophesize(Process::class);
        $process->isSuccessful()->shouldBeCalledTimes(2)->willReturn(true);

        $environmentVariables = $this->getFakeEnvironmentVariables();

        $this->processFactory->runForegroundProcess(['docker-compose', 'pull'], $environmentVariables)
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $this->processFactory->runForegroundProcess(['docker-compose', 'build', '--pull', '--parallel'], $environmentVariables)
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $dockerCompose = new DockerCompose($this->validator->reveal(), $this->processFactory->reveal());
        $dockerCompose->setActiveEnvironment($this->environment);

        static::assertTrue($dockerCompose->prepareServices());
    }

    /**
     * @throws InvalidEnvironmentException
     */
    public function testItShowsResourcesUsage(): void
    {
        $this->initializeSuccessfulValidators();
        $process = $this->initializeSuccessfullProcess();
        $environmentVariables = $this->getFakeEnvironmentVariables();

        $this->processFactory->runForegroundProcessFromShellCommandLine('docker-compose ps -q | xargs docker stats', $environmentVariables)
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $dockerCompose = new DockerCompose($this->validator->reveal(), $this->processFactory->reveal());
        $dockerCompose->setActiveEnvironment($this->environment);

        static::assertTrue($dockerCompose->showResourcesUsage());
    }

    /**
     * @throws InvalidEnvironmentException
     */
    public function testItShowsServicesStatus(): void
    {
        $this->initializeSuccessfulValidators();
        $process = $this->initializeSuccessfullProcess();
        $environmentVariables = $this->getFakeEnvironmentVariables();

        $this->processFactory->runForegroundProcess(['docker-compose', 'ps'], $environmentVariables)
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $dockerCompose = new DockerCompose($this->validator->reveal(), $this->processFactory->reveal());
        $dockerCompose->setActiveEnvironment($this->environment);

        static::assertTrue($dockerCompose->showServicesStatus());
    }

    /**
     * @throws InvalidEnvironmentException
     */
    public function testItRestartsServicesStatus(): void
    {
        $this->initializeSuccessfulValidators();
        $process = $this->initializeSuccessfullProcess();
        $environmentVariables = $this->getFakeEnvironmentVariables();

        $this->processFactory->runForegroundProcess(['docker-compose', 'restart'], $environmentVariables)
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $dockerCompose = new DockerCompose($this->validator->reveal(), $this->processFactory->reveal());
        $dockerCompose->setActiveEnvironment($this->environment);

        static::assertTrue($dockerCompose->restartServices());
    }

    /**
     * @throws InvalidEnvironmentException
     */
    public function testItStartsServicesStatus(): void
    {
        $this->initializeSuccessfulValidators();
        $process = $this->initializeSuccessfullProcess();
        $environmentVariables = $this->getFakeEnvironmentVariables();

        $this->processFactory->runForegroundProcess(['docker-compose', 'up', '--build', '--detach', '--remove-orphans'], $environmentVariables)
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $dockerCompose = new DockerCompose($this->validator->reveal(), $this->processFactory->reveal());
        $dockerCompose->setActiveEnvironment($this->environment);

        static::assertTrue($dockerCompose->startServices());
    }

    /**
     * @throws InvalidEnvironmentException
     */
    public function testItStopsServicesStatus(): void
    {
        $this->initializeSuccessfulValidators();
        $process = $this->initializeSuccessfullProcess();
        $environmentVariables = $this->getFakeEnvironmentVariables();

        $this->processFactory->runForegroundProcess(['docker-compose', 'stop'], $environmentVariables)
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $dockerCompose = new DockerCompose($this->validator->reveal(), $this->processFactory->reveal());
        $dockerCompose->setActiveEnvironment($this->environment);

        static::assertTrue($dockerCompose->stopServices());
    }

    /**
     * @throws InvalidEnvironmentException
     */
    public function testItRemovesServicesStatus(): void
    {
        $this->initializeSuccessfulValidators();
        $process = $this->initializeSuccessfullProcess();
        $environmentVariables = $this->getFakeEnvironmentVariables();

        $this->processFactory->runForegroundProcess(['docker-compose', 'down', '--rmi', 'local', '--volumes', '--remove-orphans'], $environmentVariables)
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $dockerCompose = new DockerCompose($this->validator->reveal(), $this->processFactory->reveal());
        $dockerCompose->setActiveEnvironment($this->environment);

        static::assertTrue($dockerCompose->removeServices());
    }

    private function initializeSuccessfulValidators(): void
    {
        $this->prophesizeSuccessfulValidations();
    }

    private function initializeSuccessfullProcess(): ObjectProphecy
    {
        $process = $this->prophesize(Process::class);
        $process->isSuccessful()->shouldBeCalledOnce()->willReturn(true);

        return $process;
    }
}
