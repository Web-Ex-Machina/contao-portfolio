<?php

/**
 * Module Portfolio for Contao Open Source CMS
 *
 * Copyright (c) 2015-2018 Web ex Machina
 *
 * @author Web ex Machina <http://www.webexmachina.fr>
 */

/**
 * Table tl_wem_portfolio_item_page
 */
$GLOBALS['TL_DCA']['tl_wem_portfolio_item_page'] = array(

    // Config
    'config' => array(
        'dataContainer'               => 'Table',
        'ptable'                      => 'tl_wem_portfolio_item',
        'switchToEdit'                => true,
        'enableVersioning'            => true,
        'sql' => array(
            'keys' => array(
                'id' => 'primary',
                'pid' => 'index'
            )
        )
    ),

    // List
    'list' => array(
        'sorting' => array(
            'mode'                    => 4,
            'fields'                  => array('page DESC'),
            'headerFields'            => array('title', 'pid', 'tstamp', 'date', 'published'),
            'panelLayout'             => 'filter;sort,search,limit',
            'child_record_callback'   => array('tl_wem_portfolio_item_page', 'listItemPages'),
            'child_record_class'      => 'no_padding',
            'disableGrouping'         => true,
        ),
        'global_operations' => array(
            'all' => array(
                'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href'                => 'act=select',
                'class'               => 'header_edit_all',
                'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            )
        ),
        'operations' => array(
            'edit' => array(
                'label'               => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item_page']['edit'],
                'href'                => 'act=edit',
                'icon'                => 'edit.svg'
            ),
            'delete' => array(
                'label'               => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item_page']['delete'],
                'href'                => 'act=delete',
                'icon'                => 'delete.svg',
                'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
            ),
            'show' => array(
                'label'               => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item_page']['show'],
                'href'                => 'act=show',
                'icon'                => 'show.svg'
            )
        )
    ),

    // Palettes
    'palettes' => array(
        'default'                     => '{title_legend},page'
    ),

    // Fields
    'fields' => array(
        'id' => array(
            'label'                   => array('ID'),
            'search'                  => true,
            'sql'                     => "int(10) unsigned NOT NULL auto_increment"
        ),
        'pid' => array(
            'foreignKey'              => 'tl_wem_portfolio_item.title',
            'sql'                     => "int(10) unsigned NOT NULL default '0'",
            'relation'                => array('type'=>'belongsTo', 'load'=>'lazy')
        ),
        'created_on' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item_page']['created_on'],
            'default'                 => time(),
            'flag'                    => 8,
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'tstamp' => array(
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),

        'page' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item_page']['page'],
            'exclude'                 => true,
            'filter'                  => true,
            'flag'                    => 11,
            'inputType'               => 'select',
            'foreignKey'              => 'tl_wem_portfolio_attribute.title',
            'eval'                    => array('chosen'=>true, 'mandatory'=>true, 'includeBlankOption'=>true, 'tl_class'=>'w50'),
            'sql'                     => "int(10) unsigned NOT NULL default '0'",
            'relation'                => array('type'=>'hasOne', 'load'=>'eager')
        )
    ),
);

/**
 * Handle Portfolio DCA functions
 *
 * @author Web ex Machina <contact@webexmachina.fr>
 */
class tl_wem_portfolio_item_page extends Backend
{

    /**
     * Import the back end user object
     */
    public function __construct()
    {
        parent::__construct();
        $this->import('BackendUser', 'User');
    }

    /**
     * Parse row
     * @param  [Array] $arrRow
     * @return [String]
     */
    public function listItemPages($arrRow)
    {
        $objAttribute = $this->Database->prepare("SELECT * FROM tl_page WHERE id = ?")->limit(1)->execute($arrRow['page']);
        return sprintf("%s", $objAttribute->title);
    }
}
