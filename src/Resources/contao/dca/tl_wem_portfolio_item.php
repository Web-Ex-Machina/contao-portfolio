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

use WEM\PortfolioBundle\Model\Category;
use WEM\PortfolioBundle\Model\CategoryItem;
use WEM\PortfolioBundle\Model\Item;

/*
 * Table tl_wem_portfolio_item.
 */
$GLOBALS['TL_DCA']['tl_wem_portfolio_item'] = [
    // Config
    'config' => [
        'dataContainer' => 'Table',
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
            'label_callback' => ['tl_wem_portfolio_item', 'addIcon'],
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
                'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item']['edit'],
                'href' => 'table=tl_content',
                'icon' => 'edit.svg',
            ],
            'header' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item']['header'],
                'href' => 'act=edit',
                'icon' => 'header.svg',
            ],
            'copy' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item']['copy'],
                'href' => 'act=copy',
                'icon' => 'copy.svg',
                'attributes' => 'onclick="Backend.getScrollOffset()"',
            ],
            'delete' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\''.$GLOBALS['TL_LANG']['MSC']['deleteConfirm'].'\'))return false;Backend.getScrollOffset()"',
            ],
            'toggle' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item']['toggle'],
                'icon' => 'visible.svg',
                'attributes' => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
                'button_callback' => ['tl_wem_portfolio_item', 'toggleIcon'],
            ],
            'show' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item']['show'],
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
            'label' => ['ID'],
            'search' => true,
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'createdAt' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item']['createdAt'],
            'default' => time(),
            'flag' => 8,
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],

        'title' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item']['title'],
            'exclude' => true,
            'inputType' => 'text',
            'search' => true,
            'sorting' => true,
            'flag' => 1,
            'eval' => ['mandatory' => true, 'decodeEntities' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'alias' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item']['alias'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'alias', 'doNotCopy' => true, 'unique' => true, 'maxlength' => 128, 'tl_class' => 'w50'],
            'save_callback' => [
                ['tl_wem_portfolio_item', 'generateAlias'],
            ],
            'sql' => "varchar(128) BINARY NOT NULL default ''",
        ],
        'date' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item']['date'],
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
            'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_category']['categories'],
            'inputType' => 'checkbox',
            'foreignKey' => 'tl_wem_portfolio_category.title',
            'options_callback' => ['tl_wem_portfolio_item', 'getCategories'],
            'eval' => ['multiple' => true, 'tl_class' => 'clr', 'mandatory' => true, 'submitOnChange' => true],
            'sql' => 'blob NULL',
            'save_callback' => [
                ['tl_wem_portfolio_item', 'saveCategories'],
            ],
            'relation' => ['type' => 'hasMany', 'load' => 'lazy'],
        ],
        'pictures' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item']['pictures'],
            'exclude' => true,
            'inputType' => 'fileTree',
            'eval' => ['files' => true, 'extensions' => Config::get('validImageTypes'), 'multiple' => true, 'fieldType' => 'checkbox', 'orderField' => 'orderPictures'],
            'sql' => 'blob NULL',
        ],
        'orderPictures' => [
            'label' => &$GLOBALS['TL_LANG']['MSC']['sortOrder'],
            'sql' => 'blob NULL',
        ],
        'teaser' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item']['teaser'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'textarea',
            'eval' => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
            'sql' => 'text NULL',
        ],

        'attributes' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item']['attributes'],
            'inputType' => 'wemPortfolioAttributeWizard',
            'eval' => ['tl_class' => 'clr'],
        ],

        'published' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item']['published'],
            'exclude' => true,
            'filter' => true,
            'flag' => 1,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'start' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item']['start'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''",
        ],
        'stop' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item']['stop'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''",
        ],
    ],
];

/**
 * Handle Portfolio Items DCA functions.
 *
 * @author Web ex Machina <contact@webexmachina.fr>
 */
class tl_wem_portfolio_item extends Backend
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
     * Add an image to each item in the tree.
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
    public function addIcon($row, $label, DataContainer $dc = null, $imageAttribute = '', $blnReturnImage = false, $blnProtected = false)
    {
        return '<img src="assets/contao/images/iconJPG.svg" width="18" height="18" alt="image/jpeg" style="margin-right:3px"><span style="vertical-align:-1px">'.$label.'</span>';
    }

    /**
     * Add an icon to access categories sorting.
     *
     * @param DataContainer $dc [description]
     *
     * @return [String] [Categories DCA]
     */
    public function getCategories(DataContainer $dc)
    {
        $objCategories = Category::findItems();

        if (!$objCategories || 0 === $objCategories->count()) {
            return [];
        }

        \System::loadLanguageFile('tl_wem_portfolio_category');

        $arrData = [];
        while ($objCategories->next()) {
            $strTitle = sprintf($GLOBALS['TL_LANG']['tl_wem_portfolio_category']['items'][1], $objCategories->id);
            $strHref = sprintf(
                'contao?do=wem_portfolio_category&table=tl_wem_portfolio_category_item&id=%s&popup=1&rt=%s&ref=%s',
                $objCategories->id,
                REQUEST_TOKEN,
                \Input::get('ref')
            );

            $href = sprintf(
                ' <a href="%s" title="%s" onclick="%s"><img src="%s" alt="%s" /></a>',
                $strHref,
                $strTitle,
                "Backend.openModalIframe({'title':'".$strTitle."','url':this.href});return false",
                'bundles/wemportfolio/portfolio_16.png',
                $strTitle
            );

            $arrData[$objCategories->id] = $objCategories->title.$href;
        }

        return $arrData;
    }

    /**
     * Save item categories in the child table
     * ci stands for CategoryItem.
     *
     * @param [Mixed] $varValue [Item value]
     * @param [Array] $dc       [Datacontainer]
     *
     * @return [Array] [Understandable values]
     */
    public function saveCategories($varValue, $dc)
    {
        if ($varValue) {
            $arrSavedAttrs = [];
            $arrCategories = unserialize($varValue);
            $objCategoryItems = CategoryItem::findItems(['item' => $dc->activeRecord->id]);

            if ($objCategoryItems && 0 < $objCategoryItems->count()) {
                while ($objCategoryItems->next()) {
                    if (!\in_array($objCategoryItems->pid, $arrCategories, true)) {
                        $objCategoryItems->delete();
                    }
                }
            }

            $lastSorting = CategoryItem::findItems(['pid' => $c], 1);
            $intSorting = $lastSorting->sorting ?: 0;

            foreach ($arrCategories as $c) {
                $ci = CategoryItem::findItems(['item' => $dc->activeRecord->id, 'pid' => $c], 1);

                // If we did not found an CategoryItem, create it
                if (!$ci) {
                    $intSorting += 256;

                    $ci = new CategoryItem();
                    $ci->createdAt = time();
                    $ci->sorting = $intSorting;
                    $ci->pid = $c;
                    $ci->item = $dc->activeRecord->id;
                }

                $ci->tstamp = time();
                $ci->save();
            }
        } else {
            \Database::getInstance()->prepare('DELETE FROM tl_wem_portfolio_category_item WHERE item = ?')->execute($dc->activeRecord->id);
        }

        return $varValue;
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

        $objAlias = $this->Database->prepare('SELECT id FROM tl_wem_portfolio_item WHERE id=? OR alias=?')
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

    /**
     * Return the "toggle visibility" button.
     *
     * @param array  $row
     * @param string $href
     * @param string $label
     * @param string $title
     * @param string $icon
     * @param string $attributes
     *
     * @return string
     */
    public function toggleIcon($row, $href, $label, $title, $icon, $attributes)
    {
        if (Input::get('tid') && \strlen(Input::get('tid'))) {
            $this->toggleVisibility(Input::get('tid'), (1 === Input::get('state')), (@func_get_arg(12) ?: null));
            $this->redirect($this->getReferer());
        }

        // Check permissions AFTER checking the tid, so hacking attempts are logged
        if (!$this->User->hasAccess('tl_wem_portfolio_item::published', 'alexf')) {
            return '';
        }

        $href .= '&amp;tid='.$row['id'].'&amp;state='.($row['published'] ? '' : 1);

        if (!$row['published']) {
            $icon = 'invisible.svg';
        }

        return '<a href="'.$this->addToUrl($href).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label, 'data-state="'.($row['published'] ? 1 : 0).'"').'</a> ';
    }

    /**
     * Disable/enable a user group.
     *
     * @param int           $intId
     * @param bool          $blnVisible
     * @param DataContainer $dc
     *
     * @throws Contao\CoreBundle\Exception\AccessDeniedException
     */
    public function toggleVisibility($intId, $blnVisible, DataContainer $dc = null): void
    {
        // Set the ID and action
        Input::setGet('id', $intId);
        Input::setGet('act', 'toggle');

        if ($dc) {
            $dc->id = $intId;
        } // see #8043

        // Trigger the onload_callback
        if (\is_array($GLOBALS['TL_DCA']['tl_wem_portfolio_item']['config']['onload_callback'])) {
            foreach ($GLOBALS['TL_DCA']['tl_wem_portfolio_item']['config']['onload_callback'] as $callback) {
                if (\is_array($callback)) {
                    $this->import($callback[0]);
                    $this->{$callback[0]}->{$callback[1]}($dc);
                } elseif (\is_callable($callback)) {
                    $callback($dc);
                }
            }
        }

        // Check the field access
        if (!$this->User->hasAccess('tl_wem_portfolio_item::published', 'alexf')) {
            throw new Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to publish/unpublish article ID "'.$intId.'".');
        }

        // Set the current record
        if ($dc) {
            $objRow = $this->Database->prepare('SELECT * FROM tl_wem_portfolio_item WHERE id=?')->limit(1)->execute($intId);

            if ($objRow->numRows) {
                $dc->activeRecord = $objRow;
            }
        }

        $objVersions = new Versions('tl_wem_portfolio_item', $intId);
        $objVersions->initialize();

        // Trigger the save_callback
        if (\is_array($GLOBALS['TL_DCA']['tl_wem_portfolio_item']['fields']['published']['save_callback'])) {
            foreach ($GLOBALS['TL_DCA']['tl_wem_portfolio_item']['fields']['published']['save_callback'] as $callback) {
                if (\is_array($callback)) {
                    $this->import($callback[0]);
                    $blnVisible = $this->{$callback[0]}->{$callback[1]}($blnVisible, $dc);
                } elseif (\is_callable($callback)) {
                    $blnVisible = $callback($blnVisible, $dc);
                }
            }
        }

        $time = time();

        // Update the database
        $this->Database->prepare("UPDATE tl_wem_portfolio_item SET tstamp=$time, published='".($blnVisible ? '1' : '')."' WHERE id=?")->execute($intId);

        if ($dc) {
            $dc->activeRecord->tstamp = $time;
            $dc->activeRecord->published = ($blnVisible ? '1' : '');
        }

        // Trigger the onsubmit_callback
        if (\is_array($GLOBALS['TL_DCA']['tl_wem_portfolio_item']['config']['onsubmit_callback'])) {
            foreach ($GLOBALS['TL_DCA']['tl_wem_portfolio_item']['config']['onsubmit_callback'] as $callback) {
                if (\is_array($callback)) {
                    $this->import($callback[0]);
                    $this->{$callback[0]}->{$callback[1]}($dc);
                } elseif (\is_callable($callback)) {
                    $callback($dc);
                }
            }
        }

        $objVersions->create();
    }
}
