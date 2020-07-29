<?php

declare(strict_types=1);

namespace App\Command\Contextual\Services;

class RedisCommand extends AbstractServiceCommand
{
    /** @var string */
    private const COMMAND_SERVICE_NAME = 'redis';

    /** {@inheritdoc} */
    protected static $defaultName = 'origami:redis';

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
