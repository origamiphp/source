<?php

declare(strict_types=1);

namespace App\Tests;

use App\Environment\EnvironmentEntity;

trait TestFakeEnvironmentTrait
{
    /**
     * Retrieves a new fake Environment instance.
     */
    public function getFakeEnvironment(): EnvironmentEntity
    {
        return new EnvironmentEntity(
            'origami',
            '~/Sites/origami',
            EnvironmentEntity::TYPE_SYMFONY,
            'origami.localhost www.origami.localhost',
            false
        );
    }
}
