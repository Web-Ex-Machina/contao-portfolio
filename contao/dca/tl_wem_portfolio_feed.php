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
use WEM\PortfolioBundle\DataContainer\PortfolioFeedContainer;

$GLOBALS['TL_DCA']['tl_wem_portfolio_feed'] = [
    // Config
    'config' => [
        'dataContainer' => DC_Table::class,
        'ctable' => ['tl_wem_portfolio', 'tl_wem_portfolio_feed_attribute'],
        'switchToEdit' => true,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'alias' => 'index',
            ],
        ],
    ],

    // List
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTED,
            'fields' => ['title'],
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'filter;search,limit',
        ],
        'label' => [
            'fields' => ['title'],
            'format' => '%s',
        ],
        'global_operations' => ['all'],
        'operations' => ['edit', 'children', 'delete', 'show'],
    ],

    // Palettes
    'palettes' => [
        'default' => '
            {title_legend},title,alias;
            {config_legend},jumpTo;
            {attributes_legend},attributes
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
        'createdAt' => [
            'default' => time(),
            'flag' => 8,
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'title' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'tl_class' => 'w50', 'maxlength' => 255],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'alias' => [
            'exclude' => true,
            'inputType' => 'text',
            'search' => true,
            'eval' => ['rgxp' => 'alias', 'doNotCopy' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'save_callback' => [
                [PortfolioFeedContainer::class, 'generateAlias'],
            ],
            'sql' => "varchar(255) BINARY NOT NULL default ''",
        ],
        'jumpTo' => [
            'inputType' => 'pageTree',
            'foreignKey' => 'tl_page.title',
            'eval' => ['fieldType' => 'radio'],
            'sql' => 'int(10) unsigned NOT NULL default 0',
            'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
        ],
        'attributes' => [
            'inputType' => 'dcaWizard',
            'foreignTable' => 'tl_wem_portfolio_feed_attribute',
            'foreignField' => 'pid',
            'params' => [
                'do' => 'wem_portfolio_feed',
            ],
            'eval' => [
                'fields' => ['name', 'label', 'type', 'isFilter'],
                'orderField' => 'name ASC',
                'showOperations' => true,
                'operations' => ['edit', 'delete'],
                'tl_class' => 'clr',
            ],
        ],
    ],
];
