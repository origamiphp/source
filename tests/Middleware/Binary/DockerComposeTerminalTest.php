<?php

declare(strict_types=1);

namespace App\Tests\Middleware\Binary;

use App\Middleware\Binary\DockerCompose;
use App\Tests\TestDockerComposeTrait;
use App\Tests\TestLocationTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * @internal
 *
 * @covers \App\Middleware\Binary\DockerCompose
 */
final class DockerComposeTerminalTest extends TestCase
{
    use TestDockerComposeTrait;
    use TestLocationTrait;

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
