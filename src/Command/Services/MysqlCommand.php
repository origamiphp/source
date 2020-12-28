<?php

declare(strict_types=1);

namespace App\Command\Services;

class MysqlCommand extends AbstractServiceCommand
{
    /** @var string */
    private const COMMAND_SERVICE_NAME = 'mysql';

    /** {@inheritdoc} */
    protected static $defaultName = 'origami:mysql';

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