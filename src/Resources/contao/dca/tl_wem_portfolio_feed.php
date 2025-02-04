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

use WEM\PortfolioBundle\DataContainer\PortfolioFeedContainer;

$GLOBALS['TL_DCA']['tl_wem_portfolio_feed'] = [
    // Config
    'config' => [
        'dataContainer' => 'Table',
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
            'mode' => 1,
            'fields' => ['title'],
            'flag' => 1,
            'panelLayout' => 'filter;search,limit',
        ],
        'label' => [
            'fields' => ['title'],
            'format' => '%s',
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
                'href' => 'table=tl_wem_portfolio',
                'icon' => 'edit.svg',
            ],
            'editheader' => [
                'href' => 'act=edit',
                'icon' => 'header.svg',
            ],
            'attributes' => [
                'href' => 'table=tl_wem_portfolio_feed_attribute',
                'icon' => 'modules.svg',
            ],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\''.$GLOBALS['TL_LANG']['MSC']['deleteConfirm'].'\'))return false;Backend.getScrollOffset()"',
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],
        ],
    ],

    // Palettes
    'palettes' => [
        '__selector__' => ['readFromRemote'],
        'default' => '
            {title_legend},title,alias;
            {config_legend},jumpTo;
            {remote_legend},readFromRemote;
            {attributes_legend},attributes
        ',
    ],

    // Subpalettes
    'subpalettes' => [
        'readFromRemote' => 'readFromRemoteUrl,readFromRemoteApiKey,readFromRemoteConfig'
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
        'readFromRemote' => [
            'exclude' => true,
            'filter' => true,
            'flag' => 1,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true, 'submitOnChange' => true],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'readFromRemoteUrl' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>2048, 'tl_class'=>'w50'],
            'sql' => "text NULL"
        ],
        'readFromRemoteApiKey' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['tl_class'=>'w50'],
            'load_callback' => [
                ['wem.encryption_util', 'decrypt_b64'],
            ],
            'save_callback' => [
                ['wem.encryption_util', 'encrypt_b64'],
            ],
            'sql' => "text NULL"
        ],
        'readFromRemoteConfig' => [
            'exclude' => true,
            'inputType' => 'keyValueWizard',
            'eval' => ['tl_class'=>'clr'],
            'sql' => 'blob NULL',
        ],
        'attributes' => [
            'inputType' => 'dcaWizard',
            'foreignTable' => 'tl_wem_portfolio_feed_attribute',
            'foreignField' => 'pid',
            'params' => [
                'do' => 'wem_portfolio_feed',
            ],
            'eval' => [
                'fields' => ['name', 'label', 'type', 'isFilter', 'isAlertCondition'],
                'orderField' => 'name ASC',
                'showOperations' => true,
                'operations' => ['edit', 'delete'],
                'tl_class' => 'clr',
            ],
        ],
    ],
];
