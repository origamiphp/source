<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Environment;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractEnvironmentEvent extends Event
{
    protected Environment $environment;
    protected SymfonyStyle $symfonyStyle;

    public function __construct(Environment $environment, SymfonyStyle $symfonyStyle)
    {
        $this->environment = $environment;
        $this->symfonyStyle = $symfonyStyle;
    }

    /**
     * Retrieves the environment associated to the current event.
     */
    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    /**
     * Retrieves the SymfonyStyle object previously configured in the Command class.
     */
    public function getSymfonyStyle(): SymfonyStyle
    {
        return $this->symfonyStyle;
    }
}
