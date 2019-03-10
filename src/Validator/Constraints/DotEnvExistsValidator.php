<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class DotEnvExistsValidator extends ConstraintValidator
{
    /**
     * @inheritdoc
     */
    public function validate($project, Constraint $constraint): void
    {
        if (!$constraint instanceof DotEnvExists) {
            throw new UnexpectedTypeException($constraint, DotEnvExists::class);
        }

        $filesystem = new Filesystem();

        $configuration = "$project/var/docker/.env";
        if (!$filesystem->exists($configuration)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
