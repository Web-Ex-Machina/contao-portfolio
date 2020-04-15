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

use WEM\PortfolioBundle\Model\CategoryItem;

/*
 * Table tl_wem_portfolio_category.
 */
$GLOBALS['TL_DCA']['tl_wem_portfolio_category'] = [
    // Config
    'config' => [
        'dataContainer' => 'Table',
        'ctable' => ['tl_wem_portfolio_category_item'],
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
            'fields' => ['title ASC'],
            'flag' => 3,
            'panelLayout' => 'filter;sort,search,limit',
        ],
        'label' => [
            'fields' => ['title'],
            'format' => '%s',
            'label_callback' => ['tl_wem_portfolio_category', 'addItems'],
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
                'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_category']['edit'],
                'href' => 'act=edit',
                'icon' => 'edit.svg',
            ],
            'copy' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_category']['copy'],
                'href' => 'act=copy',
                'icon' => 'copy.svg',
                'attributes' => 'onclick="Backend.getScrollOffset()"',
            ],
            'delete' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_category']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\''.$GLOBALS['TL_LANG']['MSC']['deleteConfirm'].'\'))return false;Backend.getScrollOffset()"',
            ],
            'show' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_category']['show'],
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],
            'items' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_category']['items'],
                'href' => 'table=tl_wem_portfolio_category_item',
                'icon' => 'bundles/wemportfolio/portfolio_16.png',
            ],
        ],
    ],

    // Palettes
    'palettes' => [
        'default' => '
            {title_legend},title,alias,jumpTo;
            {media_legend},picture,teaser;
            {attributes_legend},attributes
        ',
    ],

    // Fields
    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'createdAt' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_category']['createdAt'],
            'default' => time(),
            'flag' => 5,
            'eval' => ['rgxp' => 'datim'],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],

        'title' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_category']['title'],
            'exclude' => true,
            'inputType' => 'text',
            'search' => true,
            'sorting' => true,
            'flag' => 1,
            'eval' => ['mandatory' => true, 'decodeEntities' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'alias' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_category']['alias'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'alias', 'doNotCopy' => true, 'unique' => true, 'maxlength' => 128, 'tl_class' => 'w50'],
            'save_callback' => [
                ['tl_wem_portfolio_category', 'generateAlias'],
            ],
            'sql' => "varchar(128) BINARY NOT NULL default ''",
        ],
        'jumpTo' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_category']['jumpTo'],
            'exclude' => true,
            'inputType' => 'pageTree',
            'foreignKey' => 'tl_page.title',
            'eval' => ['mandatory' => true, 'fieldType' => 'radio', 'tl_class' => 'clr'],
            'sql' => 'int(10) unsigned NOT NULL default 0',
            'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
        ],

        'picture' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_category']['picture'],
            'exclude' => true,
            'inputType' => 'fileTree',
            'eval' => ['files' => true, 'extensions' => Config::get('validImageTypes'), 'fieldType' => 'radio'],
            'sql' => 'binary(16) NULL',
        ],
        'teaser' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_category']['teaser'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'textarea',
            'eval' => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
            'sql' => 'text NULL',
        ],

        'attributes' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_category']['attributes'],
            'inputType' => 'checkbox',
            'foreignKey' => 'tl_wem_portfolio_attribute.title',
            'eval' => ['multiple' => true, 'tl_class' => 'clr'],
            'sql' => 'blob NULL',
            'relation' => ['type' => 'hasMany', 'load' => 'lazy'],
        ],
    ],
];

/**
 * Handle Portfolio Items DCA functions.
 *
 * @author Web ex Machina <contact@webexmachina.fr>
 */
class tl_wem_portfolio_category extends Backend
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
     * Add the number of items found for this category.
     *
     * @param array         $row
     * @param string        $label
     * @param DataContainer $dc
     * @param string        $imageAttribute
     * @param bool          $blnReturnImage
     * @param bool          $blnProtected
     *
     * @return string
     */
    public function addItems($row, $label, DataContainer $dc = null, $imageAttribute = '', $blnReturnImage = false, $blnProtected = false)
    {
        $intItems = CategoryItem::countItems(['pid' => $row['id']]);

        return $label.sprintf(' (%s items)', $intItems);
    }

    /**
     * Auto-generate an article alias if it has not been set yet.
     *
     * @throws Exception
     *
     * @return string
     */
    public function generateAlias($varValue, DataContainer $dc)
    {
        $autoAlias = false;

        // Generate an alias if there is none
        if ('' === $varValue) {
            $autoAlias = true;
            $slugOptions = [];

            $varValue = System::getContainer()->get('contao.slug.generator')->generate(StringUtil::prepareSlug($dc->activeRecord->title), $slugOptions);

            // Prefix numeric aliases (see #1598)
            if (is_numeric($varValue)) {
                $varValue = 'id-'.$varValue;
            }
        }

        $objAlias = $this->Database->prepare('SELECT id FROM tl_wem_portfolio_category WHERE id=? OR alias=?')
                                   ->execute($dc->id, $varValue)
        ;

        // Check whether the page alias exists
        if ($objAlias->numRows > 1) {
            if (!$autoAlias) {
                throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
            }

            $varValue .= '-'.$dc->id;
        }

        return $varValue;
    }
}
