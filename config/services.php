<?php

declare(strict_types=1);

use App\ValueObject\EnvironmentEntity;
use Ergebnis\Environment\SystemVariables;
use Ergebnis\Environment\Variables;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Process\ExecutableFinder;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->defaults()
        ->autowire(true)
        ->autoconfigure(true)
        ->bind('$projectDir', '%kernel.project_dir%')
        ->bind('$installDir', '%app.install_dir%')
        ->bind('$requirements', '%app.requirements%')
    ;

    $services
        ->load('App\\', __DIR__.'/../src/*')
        ->exclude([__DIR__.'/../src/DependencyInjection/', __DIR__.'/../src/Kernel.php'])
    ;

    $services
        ->set(SystemVariables::class)->autowire(true)
        ->set(Variables::class, SystemVariables::class)
        ->set(ExecutableFinder::class)->autowire(true)
    ;

    $parameters = $containerConfigurator->parameters();
    $parameters->set('app.install_dir', '/var/docker');

    $parameters->set('app.requirements', [
        // https://www.drupal.org/docs/system-requirements
        EnvironmentEntity::TYPE_DRUPAL => [
            '10' => [
                'database' => ['mariadb:10.7', 'mariadb:10.6', 'mariadb:10.5', 'mariadb:10.4', 'mariadb:10.3', 'mysql:8.0-oracle', 'mysql:5.7-oracle', 'postgres:12-alpine'],
                'php' => ['ajardin/php:8.1'],
            ],
            '9.4' => [
                'database' => ['mariadb:10.7', 'mariadb:10.6', 'mariadb:10.5', 'mariadb:10.4', 'mariadb:10.3', 'mysql:8.0-oracle', 'mysql:5.7-oracle', 'postgres:10-alpine'],
                'php' => ['ajardin/php:8.1', 'ajardin/php:8.0', 'ajardin/php:7.4'],
            ],
            '9.3' => [
                'database' => ['mariadb:10.7', 'mariadb:10.6', 'mariadb:10.5', 'mariadb:10.4', 'mariadb:10.3', 'mysql:8.0-oracle', 'mysql:5.7-oracle', 'postgres:10-alpine'],
                'php' => ['ajardin/php:8.1', 'ajardin/php:8.0', 'ajardin/php:7.4', 'ajardin/php:7.3'],
            ],
            '9.2' => [
                'database' => ['mariadb:10.7', 'mariadb:10.6', 'mariadb:10.5', 'mariadb:10.4', 'mariadb:10.3', 'mysql:8.0-oracle', 'mysql:5.7-oracle', 'postgres:10-alpine'],
                'php' => ['ajardin/php:8.0', 'ajardin/php:7.4', 'ajardin/php:7.3'],
            ],
            '9.1' => [
                'database' => ['mariadb:10.7', 'mariadb:10.6', 'mariadb:10.5', 'mariadb:10.4', 'mariadb:10.3', 'mysql:8.0-oracle', 'mysql:5.7-oracle', 'postgres:10-alpine'],
                'php' => ['ajardin/php:8.0', 'ajardin/php:7.4', 'ajardin/php:7.3'],
            ],
            '9.0' => [
                'database' => ['mariadb:10.7', 'mariadb:10.6', 'mariadb:10.5', 'mariadb:10.4', 'mariadb:10.3', 'mysql:8.0-oracle', 'mysql:5.7-oracle', 'postgres:10-alpine'],
                'php' => ['ajardin/php:7.4', 'ajardin/php:7.3'],
            ],
        ],

        // https://devdocs.magento.com/guides/v2.4/install-gde/system-requirements.html
        EnvironmentEntity::TYPE_MAGENTO2 => [
            '2.4.5' => [
                'database' => ['mariadb:10.4', 'mysql:8.0-oracle'],
                'elasticsearch' => ['ajardin/magento2-elasticsearch:7'],
                'php' => ['ajardin/php:8.1'],
                'redis' => ['redis:6-alpine'],
            ],
            '2.4.4' => [
                'database' => ['mariadb:10.4', 'mysql:8.0-oracle'],
                'elasticsearch' => ['ajardin/magento2-elasticsearch:7'],
                'php' => ['ajardin/php:8.1'],
                'redis' => ['redis:6-alpine'],
            ],
            '2.4.3' => [
                'database' => ['mariadb:10.4', 'mysql:8.0-oracle'],
                'elasticsearch' => ['ajardin/magento2-elasticsearch:7'],
                'php' => ['ajardin/php:7.4'],
                'redis' => ['redis:6-alpine'],
            ],
            '2.4.2' => [
                'database' => ['mariadb:10.4', 'mysql:8.0-oracle'],
                'elasticsearch' => ['ajardin/magento2-elasticsearch:7'],
                'php' => ['ajardin/php:7.4'],
                'redis' => ['redis:6-alpine'],
            ],
            '2.4.1' => [
                'database' => ['mariadb:10.4', 'mysql:8.0-oracle'],
                'elasticsearch' => ['ajardin/magento2-elasticsearch:7'],
                'php' => ['ajardin/php:7.4'],
                'redis' => ['redis:5-alpine'],
            ],
            '2.4.0' => [
                'database' => ['mariadb:10.4', 'mariadb:10.3', 'mariadb:10.2', 'mysql:8.0-oracle', 'mysql:5.7-oracle'],
                'elasticsearch' => ['ajardin/magento2-elasticsearch:7'],
                'php' => ['ajardin/php:7.4', 'ajardin/php:7.3'],
                'redis' => ['redis:5-alpine'],
            ],
        ],

        // https://doc.oroinc.com/backend/setup/system-requirements/
        EnvironmentEntity::TYPE_OROCOMMERCE => [
            '5.0' => [
                'database' => ['mysql:8.0-oracle', 'postgres:13-alpine'],
                'elasticsearch' => ['elasticsearch:7.12.0'],
                'php' => ['ajardin/php:8.0'],
                'rabbitmq' => ['ajardin/orocommerce-rabbitmq:3.9'],
                'redis' => ['redis:6-alpine'],
            ],
            '4.2' => [
                'database' => ['mysql:8.0-oracle', 'postgres:13-alpine', 'postgres:12-alpine'],
                'elasticsearch' => ['elasticsearch:7.12.0'],
                'php' => ['ajardin/php:7.4'],
                'rabbitmq' => ['ajardin/orocommerce-rabbitmq:3.8'],
                'redis' => ['redis:6-alpine', 'redis:5-alpine'],
            ],
            '4.1' => [
                'database' => ['mysql:5.7-oracle', 'postgres:9-alpine'],
                'elasticsearch' => ['elasticsearch:7.12.0'],
                'php' => ['ajardin/php:7.3'],
                'rabbitmq' => ['ajardin/orocommerce-rabbitmq:3.8'],
                'redis' => ['redis:6-alpine', 'redis:5-alpine'],
            ],
        ],

        // https://docs.sylius.com/en/latest/book/installation/requirements.html
        EnvironmentEntity::TYPE_SYLIUS => [
            '1.11' => [
                'database' => ['mysql:8.0-oracle', 'mysql:5.7-oracle'],
                'php' => ['ajardin/php:8.1', 'ajardin/php:8.0'],
            ],
            '1.10' => [
                'database' => ['mysql:8.0-oracle', 'mysql:5.7-oracle'],
                'php' => ['ajardin/php:8.1', 'ajardin/php:8.0', 'ajardin/php:7.4'],
            ],
            '1.9' => [
                'database' => ['mysql:8.0-oracle', 'mysql:5.7-oracle'],
                'php' => ['ajardin/php:7.4', 'ajardin/php:7.3'],
            ],
            '1.8' => [
                'database' => ['mysql:8.0-oracle', 'mysql:5.7-oracle'],
                'php' => ['ajardin/php:7.4', 'ajardin/php:7.3'],
            ],
        ],

        // https://symfony.com/doc/current/setup.html#technical-requirements
        EnvironmentEntity::TYPE_SYMFONY => [
            '6.1' => [
                'database' => ['mariadb:10.7', 'mariadb:10.6', 'mariadb:10.5', 'mariadb:10.4', 'mariadb:10.3', 'mariadb:10.2', 'mariadb:10.1', 'mysql:8.0-oracle', 'mysql:5.7-oracle', 'mysql:5.6', 'postgres:14-alpine', 'postgres:13-alpine', 'postgres:12-alpine', 'postgres:11-alpine', 'postgres:10-alpine', 'postgres:9-alpine'],
                'php' => ['ajardin/php:8.1'],
            ],
            '6.0' => [
                'database' => ['mariadb:10.7', 'mariadb:10.6', 'mariadb:10.5', 'mariadb:10.4', 'mariadb:10.3', 'mariadb:10.2', 'mariadb:10.1', 'mysql:8.0-oracle', 'mysql:5.7-oracle', 'mysql:5.6', 'postgres:14-alpine', 'postgres:13-alpine', 'postgres:12-alpine', 'postgres:11-alpine', 'postgres:10-alpine', 'postgres:9-alpine'],
                'php' => ['ajardin/php:8.1', 'ajardin/php:8.0'],
            ],
            '5.4' => [
                'database' => ['mariadb:10.7', 'mariadb:10.6', 'mariadb:10.5', 'mariadb:10.4', 'mariadb:10.3', 'mariadb:10.2', 'mariadb:10.1', 'mysql:8.0-oracle', 'mysql:5.7-oracle', 'mysql:5.6', 'postgres:14-alpine', 'postgres:13-alpine', 'postgres:12-alpine', 'postgres:11-alpine', 'postgres:10-alpine', 'postgres:9-alpine'],
                'php' => ['ajardin/php:8.1', 'ajardin/php:8.0', 'ajardin/php:7.4', 'ajardin/php:7.3'],
            ],
            '4.4' => [
                'database' => ['mariadb:10.7', 'mariadb:10.6', 'mariadb:10.5', 'mariadb:10.4', 'mariadb:10.3', 'mariadb:10.2', 'mariadb:10.1', 'mysql:8.0-oracle', 'mysql:5.7-oracle', 'mysql:5.6', 'postgres:14-alpine', 'postgres:13-alpine', 'postgres:12-alpine', 'postgres:11-alpine', 'postgres:10-alpine', 'postgres:9-alpine'],
                'php' => ['ajardin/php:8.1', 'ajardin/php:8.0', 'ajardin/php:7.4', 'ajardin/php:7.3'],
            ],
        ],
    ]);
};
