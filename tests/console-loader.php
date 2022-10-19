<?php

declare(strict_types=1);

use App\Application;
use App\Kernel;
use App\ValueObject\ApplicationVersion;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__.'/../vendor/autoload.php';

(new Dotenv())->bootEnv(__DIR__.'/../.env');

$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? true));

return new Application($kernel, new ApplicationVersion());
