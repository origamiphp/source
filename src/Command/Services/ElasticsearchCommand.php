<?php

declare(strict_types=1);

namespace App\Command\Services;

class ElasticsearchCommand extends AbstractServiceCommand
{
    private const COMMAND_SERVICE_NAME = 'elasticsearch';

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