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

/*
 * Add palettes to tl_module.
 */
$GLOBALS['TL_DCA']['tl_module']['palettes']['wem_portfolio_list'] = '{title_legend},name,headline,type;{config_legend},wem_portfolio_categories,wem_portfolio_filters,numberOfItems,perPage,skipFirst;{template_legend:hide},wem_portfolio_template,customTpl;{image_legend:hide},imgSize;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['wem_portfolio_reader'] = '{title_legend},name,headline,type;{template_legend:hide},wem_portfolio_template,customTpl;{image_legend:hide},imgSize;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_categories'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['wem_portfolio_categories'],
    'inputType' => 'checkbox',
    'foreignKey' => 'tl_wem_portfolio_category.title',
    'eval' => ['multiple' => true, 'tl_class' => 'clr', 'mandatory' => true],
    'sql' => 'blob NULL',
    'relation' => ['type' => 'hasMany', 'load' => 'lazy'],
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_template'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['wem_portfolio_template'],
    'default' => 'wem_portfolio_item',
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => ['tl_module_wem_portfolio', 'getPortfolioTemplates'],
    'eval' => ['tl_class' => 'w50'],
    'sql' => "varchar(64) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_filters'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['wem_portfolio_filters'],
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => ['tl_module_wem_portfolio', 'getPortfolioFilters'],
    'eval' => ['doNotCopy' => true, 'tl_class' => 'clr', 'chosen' => true, 'multiple' => true],
    'sql' => 'blob NULL',
];

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Web ex Machina <http://www.webexmachina.fr>
 */
class tl_module_wem_portfolio extends Backend
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
     * Return all news templates as array.
     *
     * @return array
     */
    public function getPortfolioTemplates()
    {
        return $this->getTemplateGroup('wem_portfolio_');
    }

    /**
     * Return all attributes usable as filters.
     *
     * @return array
     */
    public function getPortfolioFilters()
    {
        $objAttributes = \WEM\PortfolioBundle\Model\Attribute::findItems(['useAsFilter' => 1]);

        if (!$objAttributes || 0 === $objAttributes->count()) {
            \Message::addInfo($GLOBALS['TL_LANG']['WEM']['PORTFOLIO']['noFiltersAvailable']);

            return [];
        }

        $arrFilters = [];
        while ($objAttributes->next()) {
            $arrFilters[$objAttributes->id] = $objAttributes->title;
        }

        return $arrFilters;
    }
}
