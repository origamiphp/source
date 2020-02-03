<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class LocalDomains extends Constraint
{
    /** @var string */
    public $message = 'The value must be a valid domain or a list of valid domains.';
}
