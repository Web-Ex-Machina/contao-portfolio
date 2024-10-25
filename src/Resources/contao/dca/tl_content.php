<?php

declare(strict_types=1);

/**
 * Contao Portfolio for Contao Open Source CMS
 * Copyright (c) 2015-2024 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-portfolio
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-portfolio/
 */

// Dynamically add the permission check and parent table
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\Input;
use Contao\System;

if ('wem_portfolio_feed' === Input::get('do')) {
    $GLOBALS['TL_DCA']['tl_content']['config']['ptable'] = 'tl_wem_portfolio';
}

$GLOBALS['TL_DCA']['tl_content']['fields']['wem_language'] = [
    'exclude' => true,
    'filter' => true,
    'sorting' => true,
    'inputType' => 'select',
    'options' => System::getContainer()->get('contao.intl.locales')->getLocales(null, false),
    'eval' => ['includeBlankOption' => true, 'mandatory' => true, 'chosen' => true, 'tl_class' => 'w50'],
    'sql' => "varchar(64) NOT NULL default ''",
];

if ('wem_portfolio_feed' === Input::get('do')) {
    foreach ($GLOBALS['TL_DCA']['tl_content']['palettes'] as $key => $value) {
        if ('__selector__' === $key || 'default' === $key) {
            continue;
        }

        PaletteManipulator::create()
            // apply the field "custom_field" after the field "username"
            ->addLegend('language')
            ->addField('wem_language', 'language')

            // now the field is registered in the PaletteManipulator
            // but it still has to be registered in the globals array:
            ->applyToPalette($key, 'tl_content')
        ;
    }
}
