<?php

declare(strict_types=1);

namespace App\Tests\Helper;

use App\Helper\Validator;
use App\Tests\TestEnvironmentTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Validator\Constraints\Hostname;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 *
 * @covers \App\Helper\Validator
 */
final class ValidatorTest extends TestCase
{
    use ProphecyTrait;
    use TestEnvironmentTrait;

    public function testItValidatesConfiguration(): void
    {
        $symfonyValidator = $this->prophesize(ValidatorInterface::class);
        $projectDir = __DIR__.'/../../';

        $environment = $this->createEnvironment();
        $this->installEnvironmentConfiguration($environment);

        $validator = new Validator($symfonyValidator->reveal(), $projectDir);
        static::assertTrue($validator->validateConfigurationFiles($environment));
    }

    public function testItInvalidatesMissingConfiguration(): void
    {
        $symfonyValidator = $this->prophesize(ValidatorInterface::class);
        $projectDir = __DIR__.'/../../';

        $environment = $this->createEnvironment();

        $validator = new Validator($symfonyValidator->reveal(), $projectDir);
        static::assertFalse($validator->validateConfigurationFiles($environment));
    }

    public function testItValidatesAnExistingDotEnvFile(): void
    {
        $symfonyValidator = $this->prophesize(ValidatorInterface::class);
        $projectDir = __DIR__.'/../../';

        $environment = $this->createEnvironment();
        $this->installEnvironmentConfiguration($environment);

        $validator = new Validator($symfonyValidator->reveal(), $projectDir);
        static::assertTrue($validator->validateDotEnvExistence($environment));
    }

    public function testItInvalidatesAMissingDotEnvFile(): void
    {
        $symfonyValidator = $this->prophesize(ValidatorInterface::class);
        $projectDir = __DIR__.'/../../';

        $environment = $this->createEnvironment();

        $validator = new Validator($symfonyValidator->reveal(), $projectDir);
        static::assertFalse($validator->validateDotEnvExistence($environment));
    }

    public function testItValidatesAnAcceptableHostname(): void
    {
        $symfonyValidator = $this->prophesize(ValidatorInterface::class);
        $projectDir = __DIR__.'/../../';

        $symfonyValidator
            ->validate(Argument::type('string'), Argument::type(Hostname::class))
            ->shouldBeCalledOnce()
            ->willReturn(new ConstraintViolationList())
        ;

        $validator = new Validator($symfonyValidator->reveal(), $projectDir);
        static::assertTrue($validator->validateHostname('mydomain.test'));
    }

    public function testItInvalidatesAnUnacceptableHostname(): void
    {
        $symfonyValidator = $this->prophesize(ValidatorInterface::class);
        $projectDir = __DIR__.'/../../';

        $errors = new ConstraintViolationList();
        $errors->add($this->prophesize(ConstraintViolation::class)->reveal());

        $symfonyValidator
            ->validate(Argument::type('string'), Argument::type(Hostname::class))
            ->shouldBeCalledOnce()
            ->willReturn($errors)
        ;

        $validator = new Validator($symfonyValidator->reveal(), $projectDir);
        static::assertFalse($validator->validateHostname('azerty'));
    }
}
