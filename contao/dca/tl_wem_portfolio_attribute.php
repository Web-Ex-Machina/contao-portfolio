<?php

declare(strict_types=1);

/**
 * Contao Portfolio for Contao Open Source CMS
 * Copyright (c) 2015-2025 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-portfolio
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-portfolio/
 */

use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_wem_portfolio_attribute'] = [
    // Config
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_wem_portfolio',
        'switchToEdit' => true,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid' => 'index',
            ],
        ],
    ],

    // Fields
    'fields' => [
        'id' => [
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
        'ptable' => [
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'pid' => [
            'foreignKey' => 'tl_wem_portfolio.title',
            'relation' => ['type' => 'belongsTo', 'load' => 'eager'],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'ftable' => [
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'fcolumn' => [
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'value_char' => [
            'sql' => "char(1) NOT NULL default ''",
        ],
        'value_varchar' => [
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'value_int' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'value_text' => [
            'sql' => 'mediumtext NULL',
        ],
        'value_binary' => [
            'sql' => 'binary(16) NULL',
        ],
        'value_blob' => [
            'sql' => 'blob NULL',
        ],
    ],
];
