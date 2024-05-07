<?php

use Rector\Config\RectorConfig;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
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
        PreferPHPUnitThisCallRector::class,
        ClosureToArrowFunctionRector::class,
        ClassPropertyAssignToConstructorPromotionRector::class => [
            __DIR__.'/src/SmartSchedule/Planning/Project.php'
        ]
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true
    );
