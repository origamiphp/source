<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Entity\Environment;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class DotEnvExistsValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($domains, Constraint $constraint): void
    {
        if (!$constraint instanceof DotEnvExists) {
            throw new UnexpectedTypeException($constraint, DotEnvExists::class);
        }

        /** @var Environment $environment */
        $environment = $domains;

        $filesystem = new Filesystem();

        $configuration = "{$environment->getLocation()}/var/docker/.env";
        if (!$filesystem->exists($configuration)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
