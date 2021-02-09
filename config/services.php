<?php

declare(strict_types=1);

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
};
