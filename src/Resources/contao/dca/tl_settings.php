<?php

declare(strict_types=1);

use Contao\CoreBundle\DataContainer\PaletteManipulator;

PaletteManipulator::create()
    ->addLegend('portfolio_legend', 'global_legend', PaletteManipulator::POSITION_AFTER)
//    ->addField('portfolioRemoteWebsite', 'portfolio_legend', PaletteManipulator::POSITION_APPEND)
    ->addField('portfolioApiKey', 'portfolio_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_settings');

//$GLOBALS['TL_DCA']['tl_settings']['fields']['portfolioRemoteWebsite'] = [
//    'label' => &$GLOBALS['TL_LANG']['tl_settings']['portfolioApiKey'],
//    'inputType' => 'text',
//    'eval' => ['rgxp' => 'url'],
//];
$GLOBALS['TL_DCA']['tl_settings']['fields']['portfolioApiKey'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_settings']['portfolioApiKey'],
    'inputType' => 'text',
    'load_callback' => [
        [WEM\UtilsBundle\Classes\Encryption::class, 'decrypt'],
    ],
    'save_callback' => [
        [WEM\UtilsBundle\Classes\Encryption::class, 'encrypt'],
    ],
];