<?php

declare(strict_types=1);

namespace App\Tests\Helper;

use App\Helper\Validator;
use App\Tests\TestLocationTrait;
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
    use TestLocationTrait;

    public function testItValidatesConfiguration(): void
    {
        $environment = $this->createEnvironment();
        [$symfonyValidator, $projectDir] = $this->prophesizeValidatorArguments();

        $this->installEnvironmentConfiguration($environment);

        $validator = new Validator($symfonyValidator->reveal(), $projectDir);
        static::assertTrue($validator->validateConfigurationFiles($environment));
    }

    public function testItInvalidatesMissingConfiguration(): void
    {
        $environment = $this->createEnvironment();
        [$symfonyValidator, $projectDir] = $this->prophesizeValidatorArguments();

        $validator = new Validator($symfonyValidator->reveal(), $projectDir);
        static::assertFalse($validator->validateConfigurationFiles($environment));
    }

    public function testItValidatesAnExistingDotEnvFile(): void
    {
        $environment = $this->createEnvironment();
        $this->installEnvironmentConfiguration($environment);
        [$symfonyValidator, $projectDir] = $this->prophesizeValidatorArguments();

        $validator = new Validator($symfonyValidator->reveal(), $projectDir);
        static::assertTrue($validator->validateDotEnvExistence($environment));
    }

    public function testItInvalidatesAMissingDotEnvFile(): void
    {
        $environment = $this->createEnvironment();
        [$symfonyValidator, $projectDir] = $this->prophesizeValidatorArguments();

        $validator = new Validator($symfonyValidator->reveal(), $projectDir);
        static::assertFalse($validator->validateDotEnvExistence($environment));
    }

    public function testItValidatesAnAcceptableHostname(): void
    {
        $noErrors = new ConstraintViolationList();

        [$symfonyValidator, $projectDir] = $this->prophesizeValidatorArguments();
        $symfonyValidator->validate(Argument::type('string'), Argument::type(Hostname::class))->shouldBeCalledOnce()->willReturn($noErrors);

        $validator = new Validator($symfonyValidator->reveal(), $projectDir);
        static::assertTrue($validator->validateHostname('symfony.localhost'));
    }

    public function testItInvalidatesAnUnacceptableHostname(): void
    {
        $violation = $this->prophesize(ConstraintViolation::class);
        $errors = new ConstraintViolationList();
        $errors->add($violation->reveal());

        [$symfonyValidator, $projectDir] = $this->prophesizeValidatorArguments();
        $symfonyValidator->validate(Argument::type('string'), Argument::type(Hostname::class))->shouldBeCalledOnce()->willReturn($errors);

        $validator = new Validator($symfonyValidator->reveal(), $projectDir);
        static::assertFalse($validator->validateHostname('azerty'));
    }

    /**
     * Prophesizes arguments needed by the \App\Helper\Validator class.
     */
    private function prophesizeValidatorArguments(): array
    {
        return [
            $this->prophesize(ValidatorInterface::class),
            __DIR__.'/../../',
        ];
    }
}
