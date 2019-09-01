<?php

declare(strict_types=1);

namespace App\Tests\Validator\Constraints;

use App\Entity\Environment;
use App\Validator\Constraints\DotEnvExists;
use App\Validator\Constraints\DotEnvExistsValidator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Constraints\Blank;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @internal
 * @covers \App\Validator\Constraints\DotEnvExistsValidator
 */
final class DotEnvExistsValidatorTest extends ConstraintValidatorTestCase
{
    protected $location;
    protected $filePath;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->location = sys_get_temp_dir().'/origami/DotEnvExistsValidatorTest';
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

        if (is_dir($this->location)) {
            $filesystem = new Filesystem();
            $filesystem->remove($this->location);
        }

        $this->location = null;
    }

    public function testItThrowsAnExceptionWithAnInvalidConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new Environment(), new Blank());
    }

    public function testItValidatesAnAcceptableValue(): void
    {
        $environment = new Environment();
        $environment->setLocation($this->location);

        $this->validator->validate($environment, new DotEnvExists());
        $this->assertNoViolation();
    }

    public function testItInvalidatesAnUnacceptableValue(): void
    {
        $constraint = new DotEnvExists();
        $this->validator->validate(new Environment(), $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised()
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function createValidator(): ConstraintValidatorInterface
    {
        return new DotEnvExistsValidator();
    }
}
