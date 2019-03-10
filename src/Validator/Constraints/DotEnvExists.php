<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class DotEnvExists extends Constraint
{
    public $message = 'The environment is not configured, consider consider executing the "install" command.';
}
