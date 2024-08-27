<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\If_\CombineIfRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\SymfonySetList;

/* Usage
 * vendor/bin/rector process src --dry-run //permet de faire un test
 * vendor/bin/rector process src  //re-ecrit les fichier
 * Tu peux faire src/dossier ou src-dossier/monfichier.php pour réduire sa porté
 * attention il peut faire de la merde, toujours relire ce qu’il fait mais
 * 95% du temps c’est ok.
 *
*/
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
        LevelSetList::UP_TO_PHP_82,
        SymfonySetList::SYMFONY_64,
    ]);