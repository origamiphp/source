<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class DotEnvExists extends Constraint
{
    public string $message = 'The environment is not configured, consider executing the "install" command.';
}
