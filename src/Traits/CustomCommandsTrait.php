<?php

declare(strict_types=1);

namespace App\Traits;

use App\Manager\ApplicationLock;
use App\Manager\EnvironmentVariables;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;

trait CustomCommandsTrait
{
    /** @var ApplicationLock */
    private $applicationLock;

    /** @var EnvironmentVariables */
    private $environmentVariables;

    /** @var ValidatorInterface */
    private $validator;

    /** @var SymfonyStyle */
    private $io;

    /** @var string */
    private $project;
}
