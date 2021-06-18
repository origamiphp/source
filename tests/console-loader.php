<?php

declare(strict_types=1);

use App\Application;
use App\Kernel;
use App\ValueObject\ApplicationVersion;

require __DIR__.'/bootstrap.php';
$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);

return new Application($kernel, new ApplicationVersion());
