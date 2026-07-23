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

/*
 * Add palettes to tl_module.
 */

use WEM\PortfolioBundle\DataContainer\ModuleContainer;

$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'wem_portfolio_addFilters';
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'wem_portfolio_remote_addFilters';
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'wem_portfolio_addConstraints';

$GLOBALS['TL_DCA']['tl_module']['palettes']['wem_portfolio_filters'] = '
    {title_legend},name,headline,type;
    {config_legend},jumpTo,wem_portfolio_feeds,wem_portfolio_filters,wem_portfolio_addSearch,wem_portfolio_hideFiltersWithNoResults;
    {template_legend:hide},customTpl;
    {expert_legend:hide},guests,cssID
';
$GLOBALS['TL_DCA']['tl_module']['palettes']['wem_portfolio_list'] =
    '{title_legend},name,headline,type;
    {config_legend},wem_portfolio_feeds,wem_portfolio_sort,numberOfItems,perPage,skipFirst;
    {filters_legend},wem_portfolio_addFilters;
    {constraints_legend},wem_portfolio_addConstraints;
    {template_legend:hide},wem_portfolio_template,customTpl;
    {image_legend:hide},imgSize;
    {protected_legend:hide},protected;
    {expert_legend:hide},guests,cssID
';
$GLOBALS['TL_DCA']['tl_module']['palettes']['wem_portfolio_reader'] = '
    {title_legend},name,headline,type;
    {config_legend},wem_portfolio_feeds,overviewPage,customLabel;
    {template_legend:hide},wem_portfolio_template,customTpl;
    {image_legend:hide},imgSize;
    {protected_legend:hide},protected;
    {expert_legend:hide},guests,cssID
';
$GLOBALS['TL_DCA']['tl_module']['palettes']['wem_portfolio_remote_filters'] = '
    {title_legend},name,headline,type;
    {config_legend},jumpTo,wem_portfolio_remote_url,wem_portfolio_remote_apikey,wem_portfolio_remote_feeds,wem_portfolio_remote_filters,wem_portfolio_addSearch,wem_portfolio_hideFiltersWithNoResults;
    {template_legend:hide},customTpl;
    {expert_legend:hide},guests,cssID
';
$GLOBALS['TL_DCA']['tl_module']['palettes']['wem_portfolio_remote_list'] =
    '{title_legend},name,headline,type;
    {config_legend},wem_portfolio_remote_url,wem_portfolio_remote_apikey,wem_portfolio_remote_feeds,wem_portfolio_sort,numberOfItems,perPage,skipFirst;
    {filters_legend},wem_portfolio_remote_addFilters;
    {constraints_legend},wem_portfolio_addConstraints;
    {template_legend:hide},wem_portfolio_template,customTpl;
    {image_legend:hide},imgSize;
    {protected_legend:hide},protected;
    {expert_legend:hide},guests,cssID
';
$GLOBALS['TL_DCA']['tl_module']['palettes']['wem_portfolio_remote_reader'] = '
    {title_legend},name,headline,type;
    {config_legend},wem_portfolio_remote_url,wem_portfolio_remote_apikey,wem_portfolio_remote_feeds,overviewPage,customLabel;
    {template_legend:hide},wem_portfolio_template,customTpl;
    {image_legend:hide},imgSize;
    {protected_legend:hide},protected;
    {expert_legend:hide},guests,cssID
';

$GLOBALS['TL_DCA']['tl_module']['subpalettes']['wem_portfolio_addFilters'] = 'wem_portfolio_filters_module';
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['wem_portfolio_remote_addFilters'] = 'wem_portfolio_remote_filters_module';
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['wem_portfolio_addConstraints'] = 'wem_portfolio_constraints';

$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_feeds'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['multiple' => true, 'mandatory' => true],
    'sql' => 'blob NULL',
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_sort'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options' => ['order_date_asc', 'order_date_desc', 'order_headline_asc', 'order_headline_desc'],
    'reference' => &$GLOBALS['TL_LANG']['tl_module']['wem_portfolio_sort'],
    'eval' => ['chosen' => true, 'mandatory' => true, 'tl_class' => 'w50'],
    'sql' => 'blob NULL',
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_addFilters'] = [
    'exclude' => true,
    'flag' => 1,
    'inputType' => 'checkbox',
    'eval' => ['submitOnChange' => true, 'doNotCopy' => true, 'tl_class' => 'clr'],
    'sql' => "char(1) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_filters'] = [
    'exclude' => true,
    'inputType' => 'select',
    'eval' => ['chosen' => true, 'multiple' => true, 'mandatory' => true, 'tl_class' => 'w50'],
    'sql' => 'blob NULL',
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_addSearch'] = [
    'exclude' => true,
    'flag' => 1,
    'inputType' => 'checkbox',
    'eval' => ['doNotCopy' => true, 'tl_class' => 'clr'],
    'sql' => "char(1) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_hideFiltersWithNoResults'] = [
    'exclude' => true,
    'flag' => 1,
    'inputType' => 'checkbox',
    'eval' => ['doNotCopy' => true, 'tl_class' => 'clr'],
    'sql' => "char(1) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_template'] = [
    'default' => 'wem_portfolio_item_default',
    'exclude' => true,
    'inputType' => 'select',
    'eval' => ['tl_class' => 'w50'],
    'sql' => "varchar(64) NOT NULL default 'wem_portfolio_item_default'",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_filters_module'] = [
    'exclude' => true,
    'inputType' => 'select',
    'foreignKey' => 'tl_module.name',
    'eval' => ['mandatory' => true],
    'sql' => 'int(10) unsigned NOT NULL default 0',
    'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_addConstraints'] = [
    'exclude' => true,
    'flag' => 1,
    'inputType' => 'checkbox',
    'eval' => ['submitOnChange' => true, 'doNotCopy' => true, 'tl_class' => 'clr'],
    'sql' => "char(1) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_constraints'] = [
    'exclude' => true,
    'inputType' => 'listWizard',
    'eval' => ['multiple' => true, 'allowHtml' => true, 'tl_class' => 'clr'],
    'sql' => 'blob NULL',
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_remote_url'] = [
    'exclude' => true,
    'inputType' => 'text',
    'load_callback' => [
        ['wem.encryption_util', 'decrypt_b64'],
    ],
    'save_callback' => [
        ['wem.encryption_util', 'encrypt_b64'],
    ],
    'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
    'sql' => 'text NULL',
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_remote_addFilters'] = [
    'exclude' => true,
    'flag' => 1,
    'inputType' => 'checkbox',
    'eval' => ['submitOnChange' => true, 'doNotCopy' => true, 'tl_class' => 'clr'],
    'sql' => "char(1) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_remote_apikey'] = [
    'exclude' => true,
    'inputType' => 'text',
    'load_callback' => [
        ['wem.encryption_util', 'decrypt_b64'],
    ],
    'save_callback' => [
        ['wem.encryption_util', 'encrypt_b64'],
    ],
    'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
    'sql' => 'text NULL',
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_remote_feeds'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['multiple' => true],
    'sql' => 'blob NULL',
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_remote_filters'] = [
    'exclude' => true,
    'inputType' => 'select',
    'eval' => ['chosen' => true, 'multiple' => true, 'tl_class' => 'w50'],
    'sql' => 'blob NULL',
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_remote_filters_module'] = [
    'exclude' => true,
    'inputType' => 'select',
    'foreignKey' => 'tl_module.name',
    'eval' => ['mandatory' => true],
    'sql' => 'int(10) unsigned NOT NULL default 0',
    'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
];