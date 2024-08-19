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

/*
 * Add palettes to tl_module.
 */

use WEM\PortfolioBundle\DataContainer\ModuleContainer;

$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'portfolio_addFilters';
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'portfolio_displayAttributes';

$GLOBALS['TL_DCA']['tl_module']['palettes']['wem_portfolio_list'] =
    '{title_legend},name,headline,type;
    {config_legend},wem_portfolio_categories,wem_portfolio_filters,wem_portfolio_sort,numberOfItems,perPage,skipFirst;
    {template_legend:hide},wem_portfolio_template,customTpl;
    {image_legend:hide},imgSize;
    {protected_legend:hide},protected;
    {expert_legend:hide},guests,cssID';

$GLOBALS['TL_DCA']['tl_module']['palettes']['wem_portfolio_reader'] =
    '{title_legend},name,headline,type;
    {template_legend:hide}
    ,wem_portfolio_template,customTpl;
    {image_legend:hide},imgSize;
    {protected_legend:hide},protected;
    {expert_legend:hide},guests,cssID';

$GLOBALS['TL_DCA']['tl_module']['subpalettes']['portfolio_addFilters'] = 'portfolio_filters,portfolio_addSearch';
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['portfolio_displayAttributes'] = 'portfolio_attributes';

$GLOBALS['TL_DCA']['tl_module']['fields']['portfolio_feed'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => [ModuleContainer::class, 'getFeeds'],
    'foreignKey' => 'tl_wem_portfolio_feed.title',
    'eval' => ['mandatory' => true],
    'sql' => 'int(10) unsigned NOT NULL default 0',
    'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
];
$GLOBALS['TL_DCA']['tl_module']['fields']['portfolio_feeds'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'options_callback' => [ModuleContainer::class, 'getFeeds'],
    'eval' => ['multiple' => true, 'mandatory' => true],
    'sql' => 'blob NULL',
];
$GLOBALS['TL_DCA']['tl_module']['fields']['portfolio_displayTeaser'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['doNotCopy' => true, 'tl_class' => 'clr'],
    'sql' => "char(1) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['portfolio_alertTeaser'] = [
    'exclude' => true,
    'search' => true,
    'inputType' => 'textarea',
    'eval' => ['rte' => 'tinyMCE', 'helpwizard' => true, 'tl_class' => 'clr'],
    'explanation' => 'insertTags',
    'sql' => 'mediumtext NULL',
];

$GLOBALS['TL_DCA']['tl_module']['fields']['portfolio_addFilters'] = [
    'exclude' => true,
    'filter' => true,
    'flag' => 1,
    'inputType' => 'checkbox',
    'eval' => ['submitOnChange' => true, 'doNotCopy' => true, 'tl_class' => 'clr'],
    'sql' => "char(1) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['portfolio_filters'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => [ModuleContainer::class, 'getFiltersOptions'],
    'eval' => ['chosen' => true, 'multiple' => true, 'mandatory' => true, 'tl_class' => 'w50'],
    'sql' => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_module']['fields']['portfolio_addSearch'] = [
    'exclude' => true,
    'filter' => true,
    'flag' => 1,
    'inputType' => 'checkbox',
    'eval' => ['doNotCopy' => true, 'tl_class' => 'clr'],
    'sql' => "char(1) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['portfolio_applicationForm'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => ['tl_content', 'getForms'],
    'eval' => ['includeBlankOption' => true, 'chosen' => true, 'submitOnChange' => true, 'tl_class' => 'w50 wizard'],
    'wizard' => [
        ['tl_content', 'editForm'],
    ],
    'sql' => "int(10) unsigned NOT NULL default '0'",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['portfolio_template'] = [
    'default' => 'portfolio_default',
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => [ModuleContainer::class, 'getTemplates'],
    'eval' => ['tl_class' => 'w50'],
    'sql' => "varchar(64) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['portfolio_pageGdpr'] = [
    'exclude' => true,
    'inputType' => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval' => ['fieldType' => 'radio', 'tl_class' => 'clr'],
    'sql' => 'int(10) unsigned NOT NULL default 0',
    'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
];
$GLOBALS['TL_DCA']['tl_module']['fields']['portfolio_pageSubscribe'] = [
    'exclude' => true,
    'inputType' => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval' => ['fieldType' => 'radio', 'tl_class' => 'clr'],
    'sql' => 'int(10) unsigned NOT NULL default 0',
    'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
];
$GLOBALS['TL_DCA']['tl_module']['fields']['portfolio_pageUnsubscribe'] = [
    'exclude' => true,
    'inputType' => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval' => ['fieldType' => 'radio', 'tl_class' => 'clr'],
    'sql' => 'int(10) unsigned NOT NULL default 0',
    'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
];
$GLOBALS['TL_DCA']['tl_module']['fields']['portfolio_displayAttributes'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['doNotCopy' => true, 'tl_class' => 'clr'],
    'sql' => "char(1) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['portfolio_attributes'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => [ModuleContainer::class, 'getAttributesOptions'],
    'eval' => ['chosen' => true, 'multiple' => true, 'mandatory' => true, 'tl_class' => 'w50'],
    'sql' => 'blob NULL',
];