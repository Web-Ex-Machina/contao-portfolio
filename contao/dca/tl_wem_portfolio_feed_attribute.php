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

use Contao\DataContainer;
use Contao\DC_Table;
use WEM\PortfolioBundle\DataContainer\PortfolioFeedAttributeContainer;

$GLOBALS['TL_DCA']['tl_wem_portfolio_feed_attribute'] = [
    // Config
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_wem_portfolio_feed',
        'ctable' => ['tl_wem_portfolio_feed_attribute_l10n'],
        'switchToEdit' => true,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid' => 'index',
            ],
        ],
    ],

    // List
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_PARENT,
            'fields' => ['name ASC'],
            'headerFields' => ['title'],
            'panelLayout' => 'filter;sort,search,limit',
            'child_record_callback' => [PortfolioFeedAttributeContainer::class, 'listItems'],
        ],
        'global_operations' => ['all'],
        'operations' => ['edit', 'copy', 'delete', 'show'],
    ],

    // Palettes
    'palettes' => [
        '__selector__' => ['type', 'isFilter'],
        'default' => '
            {type_legend},type,name,label;
        ',
        'text' => '
            {type_legend},type,name,label;
            {config_legend},mandatory,value;
            {filter_legend},isFilter;
            {design_legend},insertInDca,insertType,class;
            {l10n_legend},translatable,translations
        ',
        'textarea' => '
            {type_legend},type,name,label;
            {config_legend},mandatory,allowHtml,helpwizard,rte,explanation;
            {design_legend},insertInDca,insertType,class;
            {l10n_legend},translatable,translations
        ',
        'select' => '
            {type_legend},type,name,label;
            {config_legend},mandatory,options,multiple,chosen;
            {filter_legend},isFilter;
            {design_legend},insertInDca,insertType,class;
            {l10n_legend},translations
        ',
        'picker' => '
            {type_legend},type,name,label;
            {config_legend},mandatory,fkey;
            {design_legend},insertInDca,insertType,class;
            {l10n_legend},translations
        ',
        'fileTree' => '
            {type_legend},type,name,label;
            {config_legend},mandatory,multiple,filesOnly,fieldType,extensions;
            {design_legend},insertInDca,insertType,class;
            {l10n_legend},translations
        ',
        'listWizard' => '
            {type_legend},type,name,label;
            {config_legend},mandatory,multiple,allowHtml,maxlength;
            {filter_legend},isFilter;
            {design_legend},insertInDca,insertType,class;
            {l10n_legend},translations
        ',
    ],

    // Subpalettes
    'subpalettes' => [
        'isFilter' => 'filterLabel',
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
            'foreignKey' => 'tl_wem_portfolio_feed.title',
            'relation' => ['type' => 'belongsTo', 'load' => 'eager'],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'createdAt' => [
            'default' => time(),
            'flag' => DataContainer::SORT_MONTH_ASC,
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'type' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            'options_callback' => [PortfolioFeedAttributeContainer::class, 'getFieldOptions'],
            'eval' => ['submitOnChange' => true, 'tl_class' => 'w50 clr'],
            'reference' => &$GLOBALS['TL_LANG']['WEM']['PORTFOLIO']['ATTRIBUTE']['TYPE'],
            'sql' => ['name' => 'type', 'type' => 'string', 'length' => 64, 'default' => 'text'],
        ],
        'name' => [
            'exclude' => true,
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'rgxp' => 'custom', 'customRgxp' => '/^[a-zA-Z0-9]*$/', 'maxlength' => 64, 'tl_class' => 'w50 clr'],
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'label' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'value' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['decodeEntities' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'options' => [
            'exclude' => true,
            'inputType' => 'optionWizard',
            'eval' => ['mandatory' => true, 'allowHtml' => true],
            'sql' => 'blob NULL',
        ],
        'fkey' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['decodeEntities' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'multiple' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'chosen' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'filesOnly' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'fieldType' => [
            'exclude' => true,
            'inputType' => 'select',
            'options' => ['radio', 'checkbox'],
            'eval' => ['tl_class' => 'w50'],
            'sql' => ['name' => 'fieldType', 'type' => 'string', 'length' => 128, 'default' => 'radio'],
        ],
        'extensions' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'maxlength' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'allowHtml' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'helpwizard' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'mandatory' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'isFilter' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50 clr'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'filterLabel' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'insertInDca' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            'options_callback' => [PortfolioFeedAttributeContainer::class, 'getFieldsAndLegends'],
            'eval' => ['tl_class' => 'w50'],
            'sql' => ['name' => 'insertInDca', 'type' => 'string', 'length' => 255, 'default' => ''],
        ],
        'insertType' => [
            'exclude' => true,
            'inputType' => 'select',
            'options' => ['POSITION_BEFORE', 'POSITION_AFTER', 'POSITION_PREPEND', 'POSITION_APPEND'],
            'eval' => ['tl_class' => 'w50 clr'],
            'sql' => ['name' => 'insertType', 'type' => 'string', 'length' => 128, 'default' => 'POSITION_APPEND'],
        ],
        'class' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'rte' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'explanation' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'translatable' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'checkbox',
            'sql' => "char(1) NOT NULL default ''",
        ],
        'translations' => [
            'inputType' => 'dcaWizard',
            'foreignTable' => 'tl_wem_portfolio_feed_attribute_l10n',
            'foreignField' => 'pid',
            'params' => [
                'do' => 'wem_portfolio_feed',
            ],
            'eval' => [
                'fields' => ['language', 'label', 'value'],
                'orderField' => 'language ASC',
                'showOperations' => true,
                'operations' => ['edit', 'delete'],
                'tl_class' => 'clr',
            ],
        ],
    ],
];
