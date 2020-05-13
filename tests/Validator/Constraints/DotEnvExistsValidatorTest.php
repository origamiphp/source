<?php

declare(strict_types=1);

namespace App\Tests\Validator\Constraints;

use App\Environment\EnvironmentEntity;
use App\Tests\TestFakeEnvironmentTrait;
use App\Tests\TestLocationTrait;
use App\Validator\Constraints\DotEnvExists;
use App\Validator\Constraints\DotEnvExistsValidator;
use Symfony\Component\Validator\Constraints\Blank;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @internal
 *
 * @covers \App\Validator\Constraints\DotEnvExistsValidator
 */
final class DotEnvExistsValidatorTest extends ConstraintValidatorTestCase
{
    use TestFakeEnvironmentTrait;
    use TestLocationTrait;

    /** @var string */
    private $filePath;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->createLocation();
        mkdir($this->location.'/var/docker', 0777, true);

        $this->filePath = $this->location.'/var/docker/.env';
        touch($this->filePath);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        if (file_exists($this->filePath)) {
            unlink($this->filePath);
        }

        $this->removeLocation();
    }

    public function testItThrowsAnExceptionWithAnInvalidConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate($this->getFakeEnvironment(), new Blank());
    }

    public function testItValidatesAnAcceptableValue(): void
    {
        $this->validator->validate(new EnvironmentEntity('', $this->location, ''), new DotEnvExists());
        $this->assertNoViolation();
    }

    public function testItInvalidatesAnUnacceptableValue(): void
    {
        $constraint = new DotEnvExists();
        $this->validator->validate($this->getFakeEnvironment(), $constraint);

        $this->buildViolation($constraint->message);
        $this->expectNoValidate();
    }

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new DotEnvExistsValidator();
    }
}
