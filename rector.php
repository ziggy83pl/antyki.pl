<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/php',
        __DIR__ . '/admin/php',
    ])
    // uncomment to reach your current PHP version
    ->withPhpSets(php81: true)
    ->withPreparedSets(typeDeclarations: true, deadCode: false, codingStyle: false)
    ->withRules([
        AddVoidReturnTypeWhereNoReturnRector::class,
    ]);
