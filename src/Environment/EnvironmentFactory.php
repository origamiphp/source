<?php

declare(strict_types=1);

namespace App\Environment;

use App\ValueObject\PrepareAnswers;

/**
 * @codeCoverageIgnore
 */
class EnvironmentFactory
{
    public static function createEntityFromUserInputs(PrepareAnswers $answers): EnvironmentEntity
    {
        return new EnvironmentEntity(
            $answers->getName(),
            $answers->getLocation(),
            $answers->getType(),
            $answers->getDomains()
        );
    }
}
