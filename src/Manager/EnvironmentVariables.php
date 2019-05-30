<?php

declare(strict_types=1);

namespace App\Manager;

use Symfony\Component\Dotenv\Dotenv;

class EnvironmentVariables
{
    /**
     * Loads environment variables from the project .env file.
     *
     * @param string $configuration
     */
    public function loadFromDotEnv(string $configuration): void
    {
        $dotenv = new Dotenv();
        $dotenv->overload($configuration);
    }

    /**
     * Retrieves environment variables required to run processes.
     *
     * @param string $project
     *
     * @return array
     */
    public function getRequiredVariables(string $project): array
    {
        $environment = getenv('DOCKER_ENVIRONMENT');

        return [
            'COMPOSE_FILE' => "$project/var/docker/docker-compose.yml",
            'COMPOSE_PROJECT_NAME' => $environment.'_'.basename($project),
            'DOCKER_PHP_IMAGE' => getenv('DOCKER_PHP_IMAGE'),
            'PROJECT_LOCATION' => $project,
        ];
    }
}
