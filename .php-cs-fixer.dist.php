<?php

return (new PhpCsFixer\Config())
    ->setRules([
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        '@DoctrineAnnotation' => true,
        '@Symfony' => true,
        'php_unit_strict' => ['assertions' => ['assertAttributeEquals', 'assertAttributeNotEquals']],
        'yoda_style' => false,
    ])
    ->setRiskyAllowed(true)
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in([__DIR__.'/config', __DIR__.'/src', __DIR__.'/tests'])
            ->append([__FILE__, __DIR__.'/bin/console', __DIR__.'/rector.php'])
    )
    ->setCacheFile(__DIR__.'/var/.php-cs-fixer.cache')
;
