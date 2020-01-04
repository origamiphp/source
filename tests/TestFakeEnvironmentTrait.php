<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\Environment;

trait TestFakeEnvironmentTrait
{
    /**
     * Retrieves a new fake Environment instance.
     */
    public function getFakeEnvironment(): Environment
    {
        return new Environment(
            'origami',
            '~/Sites/origami',
            'symfony',
            'origami.localhost www.origami.localhost',
            false
        );
    }
}
