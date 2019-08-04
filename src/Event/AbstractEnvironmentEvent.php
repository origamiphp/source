<?php

/** @noinspection PhpUndefinedClassInspection */

declare(strict_types=1);

namespace App\Event;

use App\Entity\Environment;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractEnvironmentEvent extends Event
{
    /** @var Environment */
    protected $environment;

    /** @var array */
    protected $environmentVariables = [];

    /** @var SymfonyStyle */
    protected $symfonyStyle;

    /**
     * AbstractEnvironmentEvent constructor.
     *
     * @param Environment  $environment
     * @param array        $environmentVariables
     * @param SymfonyStyle $symfonyStyle
     */
    public function __construct(Environment $environment, array $environmentVariables, SymfonyStyle $symfonyStyle)
    {
        $this->environment = $environment;
        $this->environmentVariables = $environmentVariables;
        $this->symfonyStyle = $symfonyStyle;
    }

    /**
     * Retrieves the environment associated to the current event.
     *
     * @return Environment
     */
    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    /**
     * Retrieves the environment variables associated to the current event.
     *
     * @return array
     */
    public function getEnvironmentVariables(): array
    {
        return $this->environmentVariables;
    }

    /**
     * Retrieves the SymfonyStyle object previously configured in the Command class.
     *
     * @return SymfonyStyle
     */
    public function getSymfonyStyle(): SymfonyStyle
    {
        return $this->symfonyStyle;
    }
}
