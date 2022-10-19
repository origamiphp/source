<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\Property\PropertyTypeDeclarationRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->parallel();

    $rectorConfig->paths([__DIR__.'/src', __DIR__.'/tests']);
    $rectorConfig->phpVersion(PhpVersion::PHP_80);
    $rectorConfig->phpstanConfig(__DIR__.'/phpstan.neon.dist');

    $rectorConfig->import(SetList::CODE_QUALITY);
    $rectorConfig->import(SetList::DEAD_CODE);
    $rectorConfig->import(SetList::PHP_74);
    $rectorConfig->import(SetList::PHP_80);
    $rectorConfig->import(SetList::TYPE_DECLARATION);

    $rectorConfig->skip([
        PropertyTypeDeclarationRector::class => [
            __DIR__.'/src/Command/',
            __DIR__.'/tests/Command/AbstractBaseCommandTest.php',
            __DIR__.'/tests/Command/DefaultCommandTest.php',
        ],
    ]);
};
