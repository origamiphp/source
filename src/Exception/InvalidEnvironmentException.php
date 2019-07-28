<?php

declare(strict_types=1);

namespace App\Exception;

use Exception;

class InvalidEnvironmentException extends Exception implements OrigamiExceptionInterface
{
}
