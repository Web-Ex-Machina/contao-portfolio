<?php

declare(strict_types=1);

use Contao\CoreBundle\DataContainer\PaletteManipulator;

PaletteManipulator::create()
    ->addLegend('portfolio_legend')
    ->addField('portfolioApiKey', 'portfolio_legend')
    ->applyToPalette('default', 'tl_settings');

$GLOBALS['TL_DCA']['tl_settings']['fields']['portfolioApiKey'] = [
    'inputType' => 'text',
    'load_callback' => [
        ['wem.encryption_util', 'decrypt_b64'],
    ],
    'save_callback' => [
        ['wem.encryption_util', 'encrypt_b64'],
    ],
];