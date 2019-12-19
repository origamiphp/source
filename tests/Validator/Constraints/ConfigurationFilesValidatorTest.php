<?php

declare(strict_types=1);

namespace App\Tests\Validator\Constraints;

use App\Entity\Environment;
use App\Tests\TestLocationTrait;
use App\Validator\Constraints\ConfigurationFiles;
use App\Validator\Constraints\ConfigurationFilesValidator;
use Generator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Constraints\Blank;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @internal
 * @covers \App\Validator\Constraints\ConfigurationFilesValidator
 */
final class ConfigurationFilesValidatorTest extends ConstraintValidatorTestCase
{
    use TestLocationTrait;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->createLocation();
        mkdir($this->location.'/var/docker', 0777, true);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->removeLocation();
    }

    public function testItThrowsAnExceptionWithAnInvalidConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new Environment(), new Blank());
    }

    /**
     * @dataProvider provideEnvironmentTypes
     */
    public function testItValidatesConfiguration(string $type): void
    {
        $environment = new Environment();
        $environment->setType($type);
        $environment->setLocation($this->location);

        $filesystem = new Filesystem();
        $filesystem->mirror(__DIR__.'/../../../src/Resources/'.$type, $this->location.'/var/docker');

        $this->validator->validate($environment, new ConfigurationFiles());
        $this->assertNoViolation();
    }

    /**
     * @dataProvider provideEnvironmentTypes
     */
    public function testItInvalidatesMissingConfiguration(string $type): void
    {
        $environment = new Environment();
        $environment->setType($type);

        $constraint = new ConfigurationFiles();
        $this->validator->validate($environment, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised()
        ;
    }

    public function provideEnvironmentTypes(): ?Generator
    {
        yield ['magento2'];
        yield ['symfony'];
    }

    /**
     * {@inheritdoc}
     */
    protected function createValidator(): ConstraintValidatorInterface
    {
        return new ConfigurationFilesValidator();
    }
}
