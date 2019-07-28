<?php

declare(strict_types=1);

namespace App\Exception;

use Exception;

class ProjectNotFoundException extends Exception implements OrigamiExceptionInterface
{
}
