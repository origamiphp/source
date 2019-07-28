<?php

declare(strict_types=1);

namespace App\Exception;

use Exception;

class InvalidProjectException extends Exception implements OrigamiExceptionInterface
{
}
