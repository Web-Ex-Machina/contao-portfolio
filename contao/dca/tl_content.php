<?php

declare(strict_types=1);

/**
 * Contao Portfolio for Contao Open Source CMS
 * Copyright (c) 2015-2025 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-portfolio
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-portfolio/
 */

use Contao\System;

$GLOBALS['TL_DCA']['tl_content']['fields']['wem_language'] = [
    'exclude' => true,
    'filter' => true,
    'sorting' => true,
    'inputType' => 'select',
    'options' => System::getContainer()->get('contao.intl.locales')->getLocales(null, false),
    'eval' => ['includeBlankOption' => true, 'mandatory' => true, 'chosen' => true, 'tl_class' => 'w50'],
    'sql' => "varchar(64) NOT NULL default ''",
];