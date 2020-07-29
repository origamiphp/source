<?php

declare(strict_types=1);

namespace App\Command\Contextual\Services;

class NginxCommand extends AbstractServiceCommand
{
    /** @var string */
    private const COMMAND_SERVICE_NAME = 'nginx';

    /** {@inheritdoc} */
    protected static $defaultName = 'origami:nginx';

    /**
     * {@inheritdoc}
     */
    public function getServiceName(): string
    {
        return self::COMMAND_SERVICE_NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername(): string
    {
        return '';
    }
}
