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

use Contao\Backend;
use WEM\PortfolioBundle\Model\CategoryItem;
use WEM\UtilsBundle\Classes\StringUtil;

/*
 * Table tl_wem_portfolio_category_item.
 */
$GLOBALS['TL_DCA']['tl_wem_portfolio_category_item'] = [
    // Config
    'config' => [
        'dataContainer' => 'Table',
        'ptable' => 'tl_wem_portfolio_category',
        'switchToEdit' => true,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid' => 'index',
                'item' => 'index',
            ],
        ],
    ],

    // List
    'list' => [
        'sorting' => [
            'mode' => 4,
            'fields' => ['sorting'],
            'headerFields' => ['title', 'createdAt'],
            'panelLayout' => 'filter;search,limit',
            'child_record_callback' => ['tl_wem_portfolio_category_item', 'listItems'],
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
            'edit' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_category_item']['edit'],
                'href' => 'act=edit',
                'icon' => 'edit.svg',
                'attributes' => 'onclick="Backend.getScrollOffset()"',
                'button_callback' => ['tl_wem_portfolio_category_item', 'generateEditItemHref'],
            ],
            'cut' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_category_item']['cut'],
                'href' => 'act=paste&amp;mode=cut',
                'icon' => 'cut.svg',
                'attributes' => 'onclick="Backend.getScrollOffset()"',
            ],
            'delete' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_category_item']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\''.$GLOBALS['TL_LANG']['MSC']['deleteConfirm'].'\'))return false;Backend.getScrollOffset()"',
            ],
            'show' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_category_item']['show'],
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
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'createdAt' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_category_item']['createdAt'],
            'default' => time(),
            'flag' => 5,
            'eval' => ['rgxp'=>'datim'],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'sorting' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],

        'pid' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_category_item']['pid'],
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
            'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_category_item']['item'],
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
class tl_wem_portfolio_category_item extends Backend // TODO : move this function ??
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
     * @return string [String]
     * @throws Exception
     */
    public function listItems(array $row): string
    {
        $objCategoryItem = CategoryItem::findByPk($row['id']);

        return sprintf('%s', $objCategoryItem->getRelated('item')->title);
    }

    public function generateEditItemHref(array $row, string $href, string $title, string $icon, ?string $label, ?string $attributes): string
    {
        return sprintf(
            '<a href="%s" title="%s"%s>%s</a>',
            str_replace("tl_wem_portfolio_category_item", "tl_wem_portfolio_item", $this->addToUrl($href . '&amp;id=' . $row['item'])),
            $attributes,
            StringUtil::specialchars($title),
            Contao\Image::getHtml($icon, $label),
        );
    }
}
