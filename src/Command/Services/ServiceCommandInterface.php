<?php

declare(strict_types=1);

namespace App\Command\Services;

interface ServiceCommandInterface
{
    /**
     * Retrieves the service name associated to the command.
     *
     * @return string
     */
    public function getServiceName(): string;

    /**
     * Retrieves the username that will be used by the command.
     *
     * @return string
     */
    public function getUsername(): string;
}
