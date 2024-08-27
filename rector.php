<?php

declare(strict_types=1);

use Contao\Rector\Set\ContaoLevelSetList;
use Contao\Rector\Set\ContaoSetList;
use Rector\CodeQuality\Rector\If_\CombineIfRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\SymfonySetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
    ])
    ->withSkip([
        CombineIfRector::class
    ])
    ->withSets([
        SetList::CODING_STYLE,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::TYPE_DECLARATION,
        SetList::PHP_74,
        ContaoSetList::CONTAO_413,
        ContaoLevelSetList::UP_TO_CONTAO_53,
        SymfonySetList::SYMFONY_64,
    ]);