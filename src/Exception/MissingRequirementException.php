<?php

declare(strict_types=1);

namespace App\Exception;

use Exception;

class MissingRequirementException extends Exception implements OrigamiExceptionInterface
{
}
