<?php

declare(strict_types=1);

namespace App\Command\Contextual\Services;

interface ServiceCommandInterface
{
    /**
     * Retrieves the service name associated to the command.
     */
    public function getServiceName(): string;

    /**
     * Retrieves the username that will be used by the command.
     */
    public function getUsername(): string;
}
