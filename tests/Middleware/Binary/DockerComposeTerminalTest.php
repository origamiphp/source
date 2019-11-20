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
final class DockerComposeTerminalTest extends TestCase
{
    use DockerComposeTrait;
    use TestLocationTrait;

    private $validator;
    private $processFactory;
    private $environment;

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
        $this->environment->setLocation(sys_get_temp_dir().'/origami/DockerComposeTerminalTest');
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

    public function testItOpensTerminalOnGivenServiceWithSpecificUser(): void
    {
        $this->prophesizeSuccessfulValidations();

        $process = $this->prophesize(Process::class);
        $process->isSuccessful()->shouldBeCalledOnce()->willReturn(true);

        $environmentVariables = $this->getFakeEnvironmentVariables();

        $this->processFactory->runForegroundProcess(['docker-compose', 'exec', '-u', 'www-data:www-data', 'php', 'sh', '-l'], $environmentVariables)
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $dockerCompose = new DockerCompose($this->validator->reveal(), $this->processFactory->reveal());
        $dockerCompose->setActiveEnvironment($this->environment);

        static::assertTrue($dockerCompose->openTerminal('php', 'www-data:www-data'));
    }

    public function testItOpensTerminalOnGivenServiceWithoutSpecificUser(): void
    {
        $this->prophesizeSuccessfulValidations();

        $process = $this->prophesize(Process::class);
        $process->isSuccessful()->shouldBeCalledOnce()->willReturn(true);

        $environmentVariables = $this->getFakeEnvironmentVariables();

        $this->processFactory->runForegroundProcess(['docker-compose', 'exec', 'php', 'sh', '-l'], $environmentVariables)
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $dockerCompose = new DockerCompose($this->validator->reveal(), $this->processFactory->reveal());
        $dockerCompose->setActiveEnvironment($this->environment);

        static::assertTrue($dockerCompose->openTerminal('php'));
    }
}
