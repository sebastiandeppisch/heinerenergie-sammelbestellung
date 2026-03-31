<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;
use RectorLaravel\Rector\Class_\AddExtendsAnnotationToModelFactoriesRector;
use RectorLaravel\Rector\ClassMethod\AddGenericReturnTypeToRelationsRector;
use RectorLaravel\Set\LaravelSetList;
use RectorLaravel\Rector\MethodCall\ValidationRuleArrayStringValueToArrayRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/database',
        __DIR__.'/public',
        __DIR__.'/resources',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    ->withPhpSets(php84: true)
    ->withRules([
        AddExtendsAnnotationToModelFactoriesRector::class,
        AddGenericReturnTypeToRelationsRector::class,
        DeclareStrictTypesRector::class,
        ValidationRuleArrayStringValueToArrayRector::class,
    ])->withSets([
        LaravelSetList::LARAVEL_120,
    ])->withPreparedSets(
        earlyReturn: true,
        typeDeclarations: true,
        typeDeclarationDocblocks: true,
    )->withImportNames();
