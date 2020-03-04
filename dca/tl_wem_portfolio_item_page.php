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
        'sql' => array(
            'keys' => array(
                'id' => 'primary',
                'pid' => 'index'
            )
        )
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
            'foreignKey'              => 'tl_page.title',
            'sql'                     => "int(10) unsigned NOT NULL default '0'",
            'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
        )
    ),
);