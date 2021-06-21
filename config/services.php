<?php

declare(strict_types=1);

use App\Environment\EnvironmentEntity;
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

    $php80 = ['ajardin/php:8.0', 'ajardin/php:8.0-blackfire', 'ajardin/php:8.0-xdebug'];
    $php74 = ['ajardin/php:7.4', 'ajardin/php:7.4-blackfire', 'ajardin/php7.4-xdebug'];
    $php73 = ['ajardin/php:7.3', 'ajardin/php:7.3-blackfire', 'ajardin/php7.3-xdebug'];

    $parameters->set('app.requirements', [
        // https://www.drupal.org/docs/system-requirements
        EnvironmentEntity::TYPE_DRUPAL => [
            '9.1' => [
                'database' => ['mariadb:10.5', 'mariadb:10.4', 'mariadb:10.3', 'mysql:8.0', 'mysql:5.7', 'postgres:10-alpine'],
                'php' => array_merge($php80, $php74, $php73),
            ],
            '9.0' => [
                'database' => ['mariadb:10.5', 'mariadb:10.4', 'mariadb:10.3', 'mysql:8.0', 'mysql:5.7', 'postgres:10-alpine'],
                'php' => array_merge($php80, $php74, $php73),
            ],
            '8.9' => [
                'database' => ['mariadb:10.5', 'mariadb:10.4', 'mariadb:10.3', 'mariadb:10.2', 'mariadb:10.1', 'mysql:8.0', 'mysql:5.7', 'mysql:5.6', 'postgres:9-alpine'],
                'php' => array_merge($php74, $php73),
            ],
            '8.8' => [
                'database' => ['mariadb:10.5', 'mariadb:10.4', 'mariadb:10.3', 'mariadb:10.2', 'mariadb:10.1', 'mysql:8.0', 'mysql:5.7', 'mysql:5.6', 'postgres:9-alpine'],
                'php' => array_merge($php74, $php73),
            ],
        ],

        // https://devdocs.magento.com/guides/v2.4/install-gde/system-requirements.html
        EnvironmentEntity::TYPE_MAGENTO2 => [
            '2.4' => [
                'database' => ['mariadb:10.4', 'mariadb:10.3', 'mariadb:10.2', 'mysql:8.0', 'mysql:5.7'],
                'elasticsearch' => ['ajardin/magento2-elasticsearch:7', 'ajardin/magento2-elasticsearch:6'],
                'php' => array_merge($php74, $php73),
                'redis' => ['redis:6-alpine', 'redis:5-alpine'],
            ],
            '2.3' => [
                'database' => ['mariadb:10.3', 'mariadb:10.2', 'mariadb:10.1', 'mysql:5.7', 'mysql:5.6'],
                'elasticsearch' => ['ajardin/magento2-elasticsearch:7', 'ajardin/magento2-elasticsearch:6'],
                'php' => array_merge($php74, $php73),
                'redis' => ['redis:6-alpine', 'redis:5-alpine'],
            ],
        ],

        // https://doc.oroinc.com/backend/setup/system-requirements/
        EnvironmentEntity::TYPE_OROCOMMERCE => [
            '5.0' => [
                'database' => ['mysql:8.0', 'postgres:13-alpine', 'postgres:12-alpine'],
                'elasticsearch' => ['elasticsearch:7.12.0'],
                'php' => array_merge($php80, $php74),
                'rabbitmq' => ['ajardin/orocommerce-rabbitmq:3.8'],
                'redis' => ['redis:6-alpine', 'redis:5-alpine'],
            ],
            '4.2' => [
                'database' => ['mysql:8.0', 'postgres:13-alpine', 'postgres:12-alpine'],
                'elasticsearch' => ['elasticsearch:7.12.0'],
                'php' => $php74,
                'rabbitmq' => ['ajardin/orocommerce-rabbitmq:3.8'],
                'redis' => ['redis:6-alpine', 'redis:5-alpine'],
            ],
            '4.1' => [
                'database' => ['mysql:5.7', 'postgres:9-alpine'],
                'elasticsearch' => ['elasticsearch:7.12.0'],
                'php' => $php73,
                'rabbitmq' => ['ajardin/orocommerce-rabbitmq:3.8'],
                'redis' => ['redis:6-alpine', 'redis:5-alpine'],
            ],
        ],

        // https://docs.sylius.com/en/latest/book/installation/requirements.html
        EnvironmentEntity::TYPE_SYLIUS => [
            '1.9' => [
                'database' => ['mysql:8.0', 'mysql:5.7'],
                'php' => array_merge($php74, $php73),
            ],
            '1.8' => [
                'database' => ['mysql:8.0', 'mysql:5.7'],
                'php' => array_merge($php74, $php73),
            ],
        ],

        // https://symfony.com/doc/current/setup.html#technical-requirements
        EnvironmentEntity::TYPE_SYMFONY => [
            '5.3' => [
                'database' => ['mariadb:10.5', 'mariadb:10.4', 'mariadb:10.3', 'mariadb:10.2', 'mariadb:10.1', 'mysql:8.0', 'mysql:5.7', 'mysql:5.6', 'postgres:13-alpine', 'postgres:12-alpine', 'postgres:11-alpine', 'postgres:10-alpine', 'postgres:9-alpine'],
                'php' => array_merge($php80, $php74, $php73),
            ],
            '5.2' => [
                'database' => ['mariadb:10.5', 'mariadb:10.4', 'mariadb:10.3', 'mariadb:10.2', 'mariadb:10.1', 'mysql:8.0', 'mysql:5.7', 'mysql:5.6', 'postgres:13-alpine', 'postgres:12-alpine', 'postgres:11-alpine', 'postgres:10-alpine', 'postgres:9-alpine'],
                'php' => array_merge($php80, $php74, $php73),
            ],
            '4.4' => [
                'database' => ['mariadb:10.5', 'mariadb:10.4', 'mariadb:10.3', 'mariadb:10.2', 'mariadb:10.1', 'mysql:8.0', 'mysql:5.7', 'mysql:5.6', 'postgres:13-alpine', 'postgres:12-alpine', 'postgres:11-alpine', 'postgres:10-alpine', 'postgres:9-alpine'],
                'php' => array_merge($php80, $php74, $php73),
            ],
            '3.4' => [
                'database' => ['mariadb:10.5', 'mariadb:10.4', 'mariadb:10.3', 'mariadb:10.2', 'mariadb:10.1', 'mysql:8.0', 'mysql:5.7', 'mysql:5.6', 'postgres:13-alpine', 'postgres:12-alpine', 'postgres:11-alpine', 'postgres:10-alpine', 'postgres:9-alpine'],
                'php' => array_merge($php80, $php74, $php73),
            ],
        ],
    ]);
};
