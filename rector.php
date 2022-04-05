<?php

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\Property\PropertyTypeDeclarationRector;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set(Option::PATHS, [__DIR__.'/src', __DIR__.'/tests']);
    $parameters->set(Option::PHP_VERSION_FEATURES, PhpVersion::PHP_80);
    $parameters->set(Option::PHPSTAN_FOR_RECTOR_PATH, __DIR__.'/phpstan.neon.dist');

    $containerConfigurator->import(SetList::CODE_QUALITY);
    $containerConfigurator->import(SetList::DEAD_CODE);
    $containerConfigurator->import(SetList::PHP_74);
    $containerConfigurator->import(SetList::PHP_80);
    $containerConfigurator->import(SetList::TYPE_DECLARATION);

    $parameters->set(Option::SKIP, [
        PropertyTypeDeclarationRector::class => [
            __DIR__.'/src/Command/',
            __DIR__.'/tests/Command/AbstractBaseCommandTest.php',
            __DIR__.'/tests/Command/DefaultCommandTest.php',
        ],
    ]);
};
