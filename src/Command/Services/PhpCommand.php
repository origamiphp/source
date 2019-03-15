<?php

declare(strict_types=1);

namespace App\Command\Services;

class PhpCommand extends AbstractServiceCommand
{
    private const COMMAND_SERVICE_NAME = 'php';

    /**
     * {@inheritdoc}
     */
    public function getServiceName(): string
    {
        return self::COMMAND_SERVICE_NAME;
    }
}
