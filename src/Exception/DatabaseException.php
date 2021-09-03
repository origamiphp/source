<?php

declare(strict_types=1);

namespace App\Exception;

use Exception;

class DatabaseException extends Exception implements OrigamiExceptionInterface
{
}
