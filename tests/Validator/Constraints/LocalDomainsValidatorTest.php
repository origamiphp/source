<?php

declare(strict_types=1);

namespace App\Tests\Validator\Constraints;

use App\Validator\Constraints\LocalDomains;
use App\Validator\Constraints\LocalDomainsValidator;
use Generator;
use Symfony\Component\Validator\Constraints\Blank;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @internal
 * @covers \App\Validator\Constraints\LocalDomainsValidator
 */
final class LocalDomainsValidatorTest extends ConstraintValidatorTestCase
{
    public function testItThrowsAnExceptionWithAnInvalidConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate('azerty.localhost', new Blank());
    }

    /**
     * @dataProvider provideAcceptableValues
     */
    public function testItValidatesAnAcceptableValue(string $value): void
    {
        $this->validator->validate($value, new LocalDomains());
        $this->assertNoViolation();
    }

    public function provideAcceptableValues(): Generator
    {
        yield ['www.origami.localhost origami.localhost'];
        yield ['www.origami.localhost'];
        yield ['origami.localhost'];
    }

    /**
     * @dataProvider provideUnacceptableValues
     */
    public function testItInvalidatesAnUnacceptableValue(string $value): void
    {
        $constraint = new LocalDomains();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised()
        ;
    }

    public function provideUnacceptableValues(): Generator
    {
        yield ['azerty'];
        yield ['azerty.'];
        yield ['.azerty'];
    }

    /**
     * {@inheritdoc}
     */
    protected function createValidator(): ConstraintValidatorInterface
    {
        return new LocalDomainsValidator();
    }
}
