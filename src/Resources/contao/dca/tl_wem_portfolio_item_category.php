<?php

declare(strict_types=1);

/**
 * Contao Portfolio for Contao Open Source CMS
 * Copyright (c) 2015-2020 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-portfolio
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-portfolio/
 */

use WEM\PortfolioBundle\Model\ItemCategory;

/*
 * Table tl_wem_portfolio_item_category.
 */
$GLOBALS['TL_DCA']['tl_wem_portfolio_item_category'] = [
    // Config
    'config' => [
        'dataContainer' => 'Table',
        'switchToEdit' => true,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'category' => 'index',
                'item' => 'index',
            ],
        ],
    ],

    // List
    'list' => [
        'sorting' => [
            'mode' => 4,
            'fields' => ['sorting'],
            'headerFields' => ['title', 'pid', 'tstamp', 'createdAt'],
            'panelLayout' => 'filter;search,limit',
            'child_record_callback' => ['tl_wem_portfolio_item_category', 'listItemAttributes'],
            'child_record_class' => 'no_padding',
            'disableGrouping' => true,
        ],
        'global_operations' => [
            'all' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations' => [
            'cut' => [
                'href' => 'act=paste&amp;mode=cut',
                'icon' => 'cut.svg',
                'attributes' => 'onclick="Backend.getScrollOffset()"',
            ],
            'delete' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item_attribute']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\''.$GLOBALS['TL_LANG']['MSC']['deleteConfirm'].'\'))return false;Backend.getScrollOffset()"',
            ],
            'show' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item_attribute']['show'],
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],
        ],
    ],

    // Palettes
    'palettes' => [
        'default' => '{title_legend},pid,item',
    ],

    // Fields
    'fields' => [
        'id' => [
            'label' => ['ID'],
            'search' => true,
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'createdAt' => [
            'default' => time(),
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'sorting' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],

        'pid' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item_attribute']['pid'],
            'exclude' => true,
            'filter' => true,
            'flag' => 11,
            'inputType' => 'select',
            'foreignKey' => 'tl_wem_portfolio_category.title',
            'eval' => ['chosen' => true, 'mandatory' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
            'sql' => "int(10) unsigned NOT NULL default '0'",
            'relation' => ['type' => 'hasOne', 'load' => 'eager'],
        ],
        'item' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item_attribute']['item'],
            'exclude' => true,
            'filter' => true,
            'flag' => 11,
            'inputType' => 'select',
            'foreignKey' => 'tl_wem_portfolio_item.title',
            'eval' => ['chosen' => true, 'mandatory' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
            'sql' => "int(10) unsigned NOT NULL default '0'",
            'relation' => ['type' => 'hasOne', 'load' => 'eager'],
        ],
    ],
];

/**
 * Handle Portfolio DCA functions.
 *
 * @author Web ex Machina <contact@webexmachina.fr>
 */
class tl_wem_portfolio_item_category extends Backend
{
    /**
     * Import the back end user object.
     */
    public function __construct()
    {
        parent::__construct();
        $this->import('BackendUser', 'User');
    }

    /**
     * Parse row.
     *
     * @param [Array] $row
     *
     * @return [String]
     */
    public function listItemCategories($row)
    {
        $objItemCategory = ItemCategory::findByPk($row['id']);

        return sprintf('%s', $objItemCategory->getRelated('item')->title);
    }
}
