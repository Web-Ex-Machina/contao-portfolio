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

use Contao\DataContainer;
use Contao\DC_Table;
use Contao\System;
use WEM\PortfolioBundle\DataContainer\PortfolioL10nContainer;

$GLOBALS['TL_DCA']['tl_wem_portfolio_l10n'] = [
    // Config
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_wem_portfolio',
        'switchToEdit' => true,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid' => 'index',
            ],
        ],
        'onload_callback' => [
            [PortfolioL10nContainer::class, 'updatePalettes'],
        ],
    ],

    // List
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_PARENT,
            'fields' => ['language ASC'],
            'headerFields' => ['title'],
            'panelLayout' => 'filter;sort,search,limit',
            'child_record_callback' => [PortfolioL10nContainer::class, 'listItems'],
        ],
        'global_operations' => ['all'],
        'operations' => ['edit', 'copy', 'delete', 'show'],
    ],

    // Palettes
    'palettes' => [
        'default' => '
            {title_legend},language;
            {data_legend},title,slug,teaser
        ',
    ],

    // Fields
    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'pid' => [
            'foreignKey' => 'tl_wem_portfolio.title',
            'relation' => ['type' => 'belongsTo', 'load' => 'eager'],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'createdAt' => [
            'default' => time(),
            'flag' => 8,
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'language' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            'eval' => ['includeBlankOption' => true, 'chosen' => true, 'feEditable' => true, 'feGroup' => 'personal', 'tl_class' => 'w50'],
            'options_callback' => static function () {
                return System::getContainer()->get('contao.intl.locales')->getLocales(null, false);
            },
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'title' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'tl_class' => 'w50', 'maxlength' => 255],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'slug' => [
            'exclude' => true,
            'inputType' => 'text',
            'save_callback' => [
                [PortfolioL10nContainer::class, 'generateSlug'],
            ],
            'eval' => ['tl_class' => 'w50', 'maxlength' => 255, 'unique' => true],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'teaser' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'textarea',
            'eval' => ['rte' => 'tinyMCE', 'helpwizard' => true, 'tl_class' => 'clr'],
            'explanation' => 'insertTags',
            'sql' => 'mediumtext NULL',
        ],
    ],
];
