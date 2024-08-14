<?php

declare(strict_types=1);

/**
 * Contao Portfolio for Contao Open Source CMS
 * Copyright (c) 2015-2023 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-portfolio
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-portfolio/
 */

use Contao\Config;
use WEM\PortfolioBundle\DataContainer\PortfolioItem;

/*
 * Table tl_wem_portfolio_item.
 */
$GLOBALS['TL_DCA']['tl_wem_portfolio_item'] = [
    // Config
    'config' => [
        'dataContainer' => \Contao\DC_Table::class,
        'ctable' => ['tl_wem_portfolio_item_attribute', 'tl_content'],
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
            'mode' => 1,
            'fields' => ['date'],
            'flag' => 1,
            'panelLayout' => 'filter;sort,search,limit'
        ],
        'label' => [
            'fields' => ['title', 'date'],
            'format' => '%s <span style="color:#999;padding-left:3px">[%s]</span>',
            'label_callback' => [PortfolioItem::class, 'addIcon'],
        ],
        'global_operations' => [
            'all' => [
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations' => [
            'edit' => [
                'href' => 'table=tl_content',
                'icon' => 'edit.svg',
            ],
            'header' => [
                'href' => 'act=edit',
                'icon' => 'header.svg',
            ],
            'copy' => [
                'href' => 'act=copy',
                'icon' => 'copy.svg',
                'attributes' => 'onclick="Backend.getScrollOffset()"',
            ],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\''.$GLOBALS['TL_LANG']['MSC']['deleteConfirm'].'\'))return false;Backend.getScrollOffset()"',
            ],
            'toggle' => [
                'icon' => 'visible.svg',
                'attributes' => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
                'button_callback' => [PortfolioItem::class, 'toggleIcon'],
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],
        ],
    ],

    // Palettes
    'palettes' => [
        'default' => '
            {title_legend},title,alias,date,categories;
            {media_legend},pictures;
            {details_legend},teaser;
            {attributes_legend},attributes;
            {publish_legend},published,start,stop
        ',
    ],

    // Fields
    'fields' => [
        'id' => [
            'search' => true,
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'createdAt' => [
            'default' => time(),
            'flag' => 8,
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],

        'title' => [
            'exclude' => true,
            'inputType' => 'text',
            'search' => true,
            'sorting' => true,
            'flag' => 1,
            'eval' => ['mandatory' => true, 'decodeEntities' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'alias' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'alias', 'doNotCopy' => true, 'unique' => true, 'maxlength' => 128, 'tl_class' => 'w50'],
            'save_callback' => [
                [PortfolioItem::class, 'generateAlias'],
            ],
            'sql' => "varchar(128) BINARY NOT NULL default ''",
        ],
        'date' => [
            'default' => time(),
            'exclude' => true,
            'filter' => true,
            'sorting' => true,
            'flag' => 8,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'date', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'categories' => [
            'inputType' => 'checkbox',
            'foreignKey' => 'tl_wem_portfolio_category.title',
            'options_callback' => [PortfolioItem::class, 'getCategories'],
            'eval' => ['multiple' => true, 'tl_class' => 'clr', 'mandatory' => true, 'submitOnChange' => true],
            'sql' => 'blob NULL',
            'save_callback' => [
                [PortfolioItem::class, 'saveCategories'],
            ],
            'relation' => ['type' => 'hasMany', 'load' => 'lazy'],
        ],
        'pictures' => [
            'exclude' => true,
            'inputType' => 'fileTree',
            'eval' => ['files' => true, 'extensions' => Config::get('validImageTypes'), 'multiple' => true, 'fieldType' => 'checkbox', 'orderField' => 'orderPictures'],
            'sql' => 'blob NULL',
        ],
        'orderPictures' => [
            'sql' => 'blob NULL',
        ],
        'teaser' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'textarea',
            'eval' => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
            'sql' => 'text NULL',
        ],

        'attributes' => [
            'inputType' => 'dcaWizard',
            'foreignTable' => 'tl_wem_portfolio_item_attribute',
            'foreignField' => 'pid',
            'params' => [
                'do' => 'wem_portfolio_item',
            ],
            'eval' => [
                'fields' => ['name', 'label', 'type', 'isFilter', 'isAlertCondition'],
                'orderField' => 'name ASC',
                'showOperations' => true,
                'operations' => ['edit', 'delete'],
                'tl_class' => 'clr',
            ],
        ],

        'published' => [
            'exclude' => true,
            'filter' => true,
            'flag' => 1,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'start' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''",
        ],
        'stop' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''",
        ],
    ],
];
