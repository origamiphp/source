<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class LocalDomainsValidator extends ConstraintValidator
{
    /** @var string */
    private const PATTERN = '/^([a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]+)+(\s([a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]+))*$/';

    /**
     * {@inheritdoc}
     */
    public function validate($domains, Constraint $constraint): void
    {
        if (!$constraint instanceof LocalDomains) {
            throw new UnexpectedTypeException($constraint, LocalDomains::class);
        }

        if (!preg_match(self::PATTERN, $domains)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
