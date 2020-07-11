<?php

declare(strict_types=1);

namespace App\Tests;

use App\Environment\Configuration\AbstractConfiguration;
use App\Environment\EnvironmentEntity;
use App\Exception\InvalidConfigurationException;
use App\Helper\ProcessFactory;
use App\Middleware\Binary\DockerCompose;
use App\Validator\Constraints\ConfigurationFiles;
use App\Validator\Constraints\DotEnvExists;
use Prophecy\Argument;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

trait TestDockerComposeTrait
{
    use TestLocationTrait;

    /**
     * Prepares the test location by pre-installing an environment inside.
     */
    protected function prepareLocation(): void
    {
        mkdir($this->location.AbstractConfiguration::INSTALLATION_DIRECTORY, 0777, true);
        $this->environment = new EnvironmentEntity('foo', $this->location, EnvironmentEntity::TYPE_SYMFONY, null, true);

        $filesystem = new Filesystem();
        $filesystem->mirror(
            __DIR__.'/../src/Resources/symfony/',
            $this->location.AbstractConfiguration::INSTALLATION_DIRECTORY
        );
    }

    /**
     * Retrieves fake environment variables to use within tests related to Docker Compose.
     */
    protected function getFakeEnvironmentVariables(): array
    {
        return [
            'COMPOSE_FILE' => $this->location.AbstractConfiguration::INSTALLATION_DIRECTORY.'docker-compose.yml',
            'COMPOSE_PROJECT_NAME' => $this->environment->getType().'_'.$this->environment->getName(),
            'DOCKER_PHP_IMAGE' => '',
            'PROJECT_LOCATION' => $this->location,
        ];
    }

    /**
     * Prepares the common instructions needed by foreground commands.
     *
     * @throws InvalidConfigurationException
     */
    protected function prepareForegroundCommand(array $command): DockerCompose
    {
        $environmentVariables = $this->getFakeEnvironmentVariables();

        $validator = $this->prophesize(ValidatorInterface::class);
        $processFactory = $this->prophesize(ProcessFactory::class);
        $process = $this->prophesize(Process::class);

        $validator->validate(Argument::any(), new DotEnvExists())->shouldBeCalledOnce()->willReturn(new ConstraintViolationList());
        $validator->validate(Argument::any(), new ConfigurationFiles())->shouldBeCalledOnce()->willReturn(new ConstraintViolationList());
        $process->isSuccessful()->shouldBeCalledOnce()->willReturn(true);
        $processFactory->runForegroundProcess($command, $environmentVariables)->shouldBeCalledOnce()->willReturn($process->reveal());

        $dockerCompose = new DockerCompose($validator->reveal(), $processFactory->reveal());
        $dockerCompose->setActiveEnvironment($this->environment);

        return $dockerCompose;
    }

    /**
     * Prepares the common instructions needed by complex foreground commands.
     *
     * @throws InvalidConfigurationException
     */
    protected function prepareForegroundFromShellCommand(string $command): DockerCompose
    {
        $environmentVariables = $this->getFakeEnvironmentVariables();

        $validator = $this->prophesize(ValidatorInterface::class);
        $processFactory = $this->prophesize(ProcessFactory::class);
        $process = $this->prophesize(Process::class);

        $validator->validate(Argument::any(), new DotEnvExists())->shouldBeCalledOnce()->willReturn(new ConstraintViolationList());
        $validator->validate(Argument::any(), new ConfigurationFiles())->shouldBeCalledOnce()->willReturn(new ConstraintViolationList());
        $process->isSuccessful()->shouldBeCalledOnce()->willReturn(true);
        $processFactory->runForegroundProcessFromShellCommandLine($command, $environmentVariables)->shouldBeCalledOnce()->willReturn($process->reveal());

        $dockerCompose = new DockerCompose($validator->reveal(), $processFactory->reveal());
        $dockerCompose->setActiveEnvironment($this->environment);

        return $dockerCompose;
    }
}
