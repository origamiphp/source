<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Environment\Configuration\AbstractConfiguration;
use App\Environment\EnvironmentEntity;
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

        /** @var EnvironmentEntity $environment */
        $environment = $domains;

        $filesystem = new Filesystem();

        $configuration = $environment->getLocation().AbstractConfiguration::INSTALLATION_DIRECTORY.'.env';
        if (!$filesystem->exists($configuration)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
