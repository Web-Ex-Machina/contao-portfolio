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

$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'wem_portfolio_addFilters';
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'wem_portfolio_displayAttributes';

$GLOBALS['TL_DCA']['tl_module']['palettes']['wem_portfolio_filters'] = '
    {title_legend},name,headline,type;
    {config_legend},jumpTo,wem_portfolio_filters,wem_portfolio_addSearch;
    {template_legend:hide},customTpl;
    {expert_legend:hide},guests,cssID
';
$GLOBALS['TL_DCA']['tl_module']['palettes']['wem_portfolio_list'] =
    '{title_legend},name,headline,type;
    {config_legend},wem_portfolio_feeds,wem_portfolio_sort,numberOfItems,perPage,skipFirst;
    {filters_legend},wem_portfolio_addFilters;
    {attributes_legend},wem_portfolio_displayAttributes;
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

$GLOBALS['TL_DCA']['tl_module']['subpalettes']['wem_portfolio_addFilters'] = 'wem_portfolio_filters_module';
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['wem_portfolio_displayAttributes'] = 'wem_portfolio_attributes';

$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_feeds'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'options_callback' => [ModuleContainer::class, 'getFeeds'],
    'eval' => ['multiple' => true, 'mandatory' => true],
    'sql' => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_addFilters'] = [
    'exclude' => true,
    'filter' => true,
    'flag' => 1,
    'inputType' => 'checkbox',
    'eval' => ['submitOnChange' => true, 'doNotCopy' => true, 'tl_class' => 'clr'],
    'sql' => "char(1) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_filters'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => [ModuleContainer::class, 'getFiltersOptions'],
    'eval' => ['chosen' => true, 'multiple' => true, 'mandatory' => true, 'tl_class' => 'w50'],
    'sql' => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_addSearch'] = [
    'exclude' => true,
    'filter' => true,
    'flag' => 1,
    'inputType' => 'checkbox',
    'eval' => ['doNotCopy' => true, 'tl_class' => 'clr'],
    'sql' => "char(1) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_template'] = [
    'default' => 'wem_portfolio_default',
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => [ModuleContainer::class, 'getTemplates'],
    'eval' => ['tl_class' => 'w50'],
    'sql' => "varchar(64) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_displayAttributes'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['doNotCopy' => true, 'tl_class' => 'clr', 'submitOnChange' => true],
    'sql' => "char(1) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_attributes'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => [ModuleContainer::class, 'getAttributesOptions'],
    'eval' => ['chosen' => true, 'multiple' => true, 'mandatory' => true, 'tl_class' => 'w50'],
    'sql' => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_filters_module'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => [WEM\PortfolioBundle\DataContainer\ModuleContainer::class, 'getFiltersModules'],
    'foreignKey' => 'tl_module.name',
    'eval' => ['mandatory' => true],
    'sql' => 'int(10) unsigned NOT NULL default 0',
    'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
];