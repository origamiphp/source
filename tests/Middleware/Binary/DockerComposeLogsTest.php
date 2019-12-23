<?php

declare(strict_types=1);

namespace App\Tests\Middleware\Binary;

use App\Entity\Environment;
use App\Helper\ProcessFactory;
use App\Middleware\Binary\DockerCompose;
use App\Tests\TestLocationTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 *
 * @covers \App\Middleware\Binary\DockerCompose
 */
final class DockerComposeLogsTest extends TestCase
{
    use DockerComposeTrait;
    use TestLocationTrait;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = $this->prophesize(ValidatorInterface::class);
        $this->processFactory = $this->prophesize(ProcessFactory::class);

        $this->createLocation();
        mkdir($this->location.'/var/docker', 0777, true);

        $this->environment = new Environment();
        $this->environment->setName('foo');
        $this->environment->setLocation(sys_get_temp_dir().'/origami/DockerComposeLogsTest');
        $this->environment->setType('magento2');
        $this->environment->setActive(true);

        $filesystem = new Filesystem();
        $filesystem->mirror(__DIR__.'/../../../src/Resources/magento2/', $this->location.'/var/docker');
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->removeLocation();
    }

    public function testItShowServicesLogsWithDefaultArguments(): void
    {
        $this->prophesizeSuccessfulValidations();

        $process = $this->prophesize(Process::class);
        $process->isSuccessful()->shouldBeCalledOnce()->willReturn(true);

        $environmentVariables = $this->getFakeEnvironmentVariables();

        $this->processFactory->runForegroundProcess(['docker-compose', 'logs', '--follow', '--tail=0'], $environmentVariables)
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $dockerCompose = new DockerCompose($this->validator->reveal(), $this->processFactory->reveal());
        $dockerCompose->setActiveEnvironment($this->environment);

        static::assertTrue($dockerCompose->showServicesLogs());
    }

    public function testItShowServicesLogsWithSpecificService(): void
    {
        $this->prophesizeSuccessfulValidations();

        $process = $this->prophesize(Process::class);
        $process->isSuccessful()->shouldBeCalledOnce()->willReturn(true);

        $environmentVariables = $this->getFakeEnvironmentVariables();

        $this->processFactory->runForegroundProcess(['docker-compose', 'logs', '--follow', '--tail=0', 'php'], $environmentVariables)
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $dockerCompose = new DockerCompose($this->validator->reveal(), $this->processFactory->reveal());
        $dockerCompose->setActiveEnvironment($this->environment);

        static::assertTrue($dockerCompose->showServicesLogs(0, 'php'));
    }

    public function testItShowServicesLogsWithSpecificTail(): void
    {
        $this->prophesizeSuccessfulValidations();

        $process = $this->prophesize(Process::class);
        $process->isSuccessful()->shouldBeCalledOnce()->willReturn(true);

        $environmentVariables = $this->getFakeEnvironmentVariables();

        $this->processFactory->runForegroundProcess(['docker-compose', 'logs', '--follow', '--tail=42'], $environmentVariables)
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $dockerCompose = new DockerCompose($this->validator->reveal(), $this->processFactory->reveal());
        $dockerCompose->setActiveEnvironment($this->environment);

        static::assertTrue($dockerCompose->showServicesLogs(42));
    }

    public function testItShowServicesLogsWithSpecificServiceAndTail(): void
    {
        $this->prophesizeSuccessfulValidations();

        $process = $this->prophesize(Process::class);
        $process->isSuccessful()->shouldBeCalledOnce()->willReturn(true);

        $environmentVariables = $this->getFakeEnvironmentVariables();

        $this->processFactory->runForegroundProcess(['docker-compose', 'logs', '--follow', '--tail=42', 'php'], $environmentVariables)
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $dockerCompose = new DockerCompose($this->validator->reveal(), $this->processFactory->reveal());
        $dockerCompose->setActiveEnvironment($this->environment);

        static::assertTrue($dockerCompose->showServicesLogs(42, 'php'));
    }
}
