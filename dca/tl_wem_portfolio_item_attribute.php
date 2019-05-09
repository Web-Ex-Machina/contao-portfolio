<?php

/**
 * Module Portfolio for Contao Open Source CMS
 *
 * Copyright (c) 2015-2018 Web ex Machina
 *
 * @author Web ex Machina <http://www.webexmachina.fr>
 */

/**
 * Table tl_wem_portfolio_item_attribute
 */
$GLOBALS['TL_DCA']['tl_wem_portfolio_item_attribute'] = array(

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
            'fields'                  => array('attribute DESC'),
            'headerFields'            => array('title', 'pid', 'tstamp', 'date', 'published'),
            'panelLayout'             => 'filter;sort,search,limit',
            'child_record_callback'   => array('tl_wem_portfolio_item_attribute', 'listItemAttributes'),
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
                'label'               => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item_attribute']['edit'],
                'href'                => 'act=edit',
                'icon'                => 'edit.svg'
            ),
            'copy' => array(
                'label'               => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item_attribute']['copy'],
                'href'                => 'act=paste&amp;mode=copy',
                'icon'                => 'copy.svg'
            ),
            'delete' => array(
                'label'               => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item_attribute']['delete'],
                'href'                => 'act=delete',
                'icon'                => 'delete.svg',
                'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
            ),
            'show' => array(
                'label'               => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item_attribute']['show'],
                'href'                => 'act=show',
                'icon'                => 'show.svg'
            )
        )
    ),

    // Palettes
    'palettes' => array(
        'default'                     => '{title_legend},attribute,value'
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
            'label'                   => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item_attribute']['created_on'],
            'default'                 => time(),
            'flag'                    => 8,
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'tstamp' => array(
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),

        'attribute' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item_attribute']['attribute'],
            'exclude'                 => true,
            'filter'                  => true,
            'flag'                    => 11,
            'inputType'               => 'select',
            'foreignKey'              => 'tl_wem_portfolio_attribute.title',
            'eval'                    => array('chosen'=>true, 'mandatory'=>true, 'includeBlankOption'=>true, 'tl_class'=>'w50'),
            'sql'                     => "int(10) unsigned NOT NULL default '0'",
            'relation'                => array('type'=>'hasOne', 'load'=>'eager')
        ),
        'value' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item_attribute']['value'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => array('mandatory'=>true, 'decodeEntities'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
    ),
);

/**
 * Handle Portfolio DCA functions
 *
 * @author Web ex Machina <contact@webexmachina.fr>
 */
class tl_wem_portfolio_item_attribute extends Backend
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
    public function listItemAttributes($arrRow)
    {
        $objAttribute = $this->Database->prepare("SELECT * FROM tl_wem_portfolio_attribute WHERE id = ?")->limit(1)->execute($arrRow['attribute']);
        return sprintf("%s -> %s", $objAttribute->title, $arrRow['value']);
    }
}
