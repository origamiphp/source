<?php

declare(strict_types=1);

namespace App\Event;

use App\Service\Wrapper\OrigamiStyle;
use App\ValueObject\EnvironmentEntity;
use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractEnvironmentEvent extends Event
{
    public function __construct(
        protected EnvironmentEntity $environment,
        protected OrigamiStyle $symfonyStyle
    ) {
    }

    /**
     * Retrieves the environment associated to the current event.
     */
    public function getEnvironment(): EnvironmentEntity
    {
        return $this->environment;
    }

    /**
     * Retrieves the OrigamiStyle object previously configured in the Command class.
     */
    public function getConsoleStyle(): OrigamiStyle
    {
        return $this->symfonyStyle;
    }
}
