<?php

declare(strict_types=1);

namespace App\Exception;

use Exception;

class UnsupportedOperatingSystemException extends Exception implements OrigamiExceptionInterface
{
}
