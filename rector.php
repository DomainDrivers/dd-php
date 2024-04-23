<?php

use Rector\Config\RectorConfig;
use Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitThisCallRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withParallel(240)
    ->withPhpVersion(PhpVersion::PHP_83)
    ->withRules([
        TypedPropertyFromStrictConstructorRector::class
    ])
    ->withImportNames(true, true, false)
    ->withPHPStanConfigs([__DIR__.'/phpstan.neon'])
    ->withPhpSets(php83: true)
    ->withSets([
        PHPUnitSetList::PHPUNIT_100,
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
    ])
    ->withSkip([
        PreferPHPUnitThisCallRector::class
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true
    );
