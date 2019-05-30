<?php

declare(strict_types=1);

namespace App\Event;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractEnvironmentEvent extends Event
{
    /** @var array */
    protected $environmentVariables = [];
    /** @var SymfonyStyle */
    protected $symfonyStyle;

    /**
     * AbstractEnvironmentEvent constructor.
     *
     * @param array        $environmentVariables
     * @param SymfonyStyle $symfonyStyle
     */
    public function __construct(array $environmentVariables, SymfonyStyle $symfonyStyle)
    {
        $this->environmentVariables = $environmentVariables;
        $this->symfonyStyle = $symfonyStyle;
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
