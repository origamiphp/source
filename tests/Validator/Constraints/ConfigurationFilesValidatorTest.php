<?php

declare(strict_types=1);

namespace App\Tests\Validator\Constraints;

use App\Entity\Environment;
use App\Tests\TestFakeEnvironmentTrait;
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
 *
 * @covers \App\Validator\Constraints\ConfigurationFilesValidator
 */
final class ConfigurationFilesValidatorTest extends ConstraintValidatorTestCase
{
    use TestFakeEnvironmentTrait;
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
        $this->validator->validate($this->getFakeEnvironment(), new Blank());
    }

    /**
     * @dataProvider provideEnvironmentTypes
     */
    public function testItValidatesConfiguration(string $type): void
    {
        $filesystem = new Filesystem();
        $filesystem->mirror(__DIR__.'/../../../src/Resources/'.$type, $this->location.'/var/docker');

        $this->validator->validate(new Environment($type, $this->location, $type), new ConfigurationFiles());
        $this->assertNoViolation();
    }

    /**
     * @dataProvider provideEnvironmentTypes
     */
    public function testItInvalidatesMissingConfiguration(string $type): void
    {
        $constraint = new ConfigurationFiles();
        $this->validator->validate(new Environment($type, $this->location, $type), $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised()
        ;
    }

    public function provideEnvironmentTypes(): Generator
    {
        yield [Environment::TYPE_MAGENTO2];
        yield [Environment::TYPE_SYMFONY];
    }

    /**
     * {@inheritdoc}
     */
    protected function createValidator(): ConstraintValidatorInterface
    {
        return new ConfigurationFilesValidator();
    }
}
