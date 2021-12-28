<?php

declare(strict_types=1);

namespace App\Tests\Command\Configure;

use App\Command\Configure\PhpstormCommand;
use App\Exception\InvalidEnvironmentException;
use App\Service\ApplicationContext;
use App\Service\Middleware\Database;
use App\Tests\TestCommandTrait;
use App\Tests\TestEnvironmentTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\Configure\PhpstormCommand
 */
final class PhpstormCommandTest extends TestCase
{
    use ProphecyTrait;
    use TestCommandTrait;
    use TestEnvironmentTrait;

    public function testItConfiguresPhpstormWithoutPhpServers(): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);
        $database = $this->prophesize(Database::class);
        $projectDir = __DIR__.'/../../..';

        $environment = $this->createEnvironment();

        mkdir($environment->getLocation().'/.idea');
        file_put_contents(
            $environment->getLocation().'/.idea/workspace.xml',
            '<?xml version="1.0" encoding="UTF-8"?><project></project>'
        );

        $applicationContext
            ->loadEnvironment(Argument::type(InputInterface::class))
            ->shouldBeCalledOnce()
        ;

        $applicationContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $database
            ->getDatabaseType()
            ->shouldBeCalledOnce()
            ->willReturn('postgres')
        ;

        $command = new PhpstormCommand($applicationContext->reveal(), $database->reveal(), $projectDir);
        static::assertResultIsSuccessful($command, $environment);
    }

    public function testItConfiguresPhpstormWithPhpServers(): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);
        $database = $this->prophesize(Database::class);
        $projectDir = __DIR__.'/../../..';

        $environment = $this->createEnvironment();

        mkdir($environment->getLocation().'/.idea');
        file_put_contents(
            $environment->getLocation().'/.idea/workspace.xml',
            '<?xml version="1.0" encoding="UTF-8"?><project><component name="PhpServers"></component></project>'
        );

        $applicationContext
            ->loadEnvironment(Argument::type(InputInterface::class))
            ->shouldBeCalledOnce()
        ;

        $applicationContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $database
            ->getDatabaseType()
            ->shouldBeCalledOnce()
            ->willReturn('postgres')
        ;

        $command = new PhpstormCommand($applicationContext->reveal(), $database->reveal(), $projectDir);
        static::assertResultIsSuccessful($command, $environment);
    }

    public function testItFailsWithUnsupportedDatabaseType(): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);
        $database = $this->prophesize(Database::class);
        $projectDir = __DIR__.'/../../..';

        $environment = $this->createEnvironment();

        mkdir($environment->getLocation().'/.idea');
        file_put_contents(
            $environment->getLocation().'/.idea/workspace.xml',
            '<?xml version="1.0" encoding="UTF-8"?><project></project>'
        );

        $applicationContext
            ->loadEnvironment(Argument::type(InputInterface::class))
            ->shouldBeCalledOnce()
        ;

        $applicationContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $database
            ->getDatabaseType()
            ->shouldBeCalledOnce()
            ->willReturn('foobar')
        ;

        $command = new PhpstormCommand($applicationContext->reveal(), $database->reveal(), $projectDir);
        static::assertExceptionIsHandled($command);
    }

    public function testItFailsWithoutValidEnvironment(): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);
        $database = $this->prophesize(Database::class);
        $projectDir = __DIR__.'/../../..';

        $applicationContext
            ->loadEnvironment(Argument::type(InputInterface::class))
            ->willThrow(InvalidEnvironmentException::class)
        ;

        $command = new PhpstormCommand($applicationContext->reveal(), $database->reveal(), $projectDir);
        static::assertExceptionIsHandled($command);
    }

    public function testItFailsWithoutPhpstormDirectory(): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);
        $database = $this->prophesize(Database::class);
        $projectDir = __DIR__.'/../../..';

        $environment = $this->createEnvironment();

        $applicationContext
            ->loadEnvironment(Argument::type(InputInterface::class))
            ->shouldBeCalledOnce()
        ;

        $applicationContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $command = new PhpstormCommand($applicationContext->reveal(), $database->reveal(), $projectDir);
        static::assertExceptionIsHandled($command);
    }
}
