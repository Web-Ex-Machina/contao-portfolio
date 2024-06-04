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

use Contao\Backend;
use Contao\DataContainer;
use Contao\Model\Collection;
use WEM\UtilsBundle\Classes\StringUtil;

$GLOBALS['TL_DCA']['tl_module']['palettes']['wem_portfolio_list_categories'] = '{title_legend},name,headline,type;{config_legend},wem_portfolio_category_sort,numberOfItems,perPage,skipFirst;{list_legend},wem_portfolio_list_module;{template_legend:hide},wem_portfolio_category_template,customTpl;{image_legend:hide},imgSize;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['wem_portfolio_list'] = '{title_legend},name,headline,type;{config_legend},wem_portfolio_categories,wem_portfolio_filters,wem_portfolio_item_sort,numberOfItems,perPage,skipFirst;{template_legend:hide},wem_portfolio_item_template,customTpl;{image_legend:hide},imgSize;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['wem_portfolio_reader'] = '{title_legend},name,headline,type;{template_legend:hide},wem_portfolio_item_template,customTpl;{image_legend:hide},imgSize;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_categories'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['wem_portfolio_categories'],
    'inputType' => 'checkbox',
    'foreignKey' => 'tl_wem_portfolio_category.title',
    'eval' => ['multiple' => true, 'tl_class' => 'clr', 'mandatory' => true],
    'sql' => 'blob NULL',
    'relation' => ['type' => 'hasMany', 'load' => 'lazy'],
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_item_template'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['wem_portfolio_item_template'],
    'default' => 'wem_portfolio_item_default',
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => ['tl_module_wem_portfolio', 'getPortfolioItemTemplates'],
    'eval' => ['tl_class' => 'w50'],
    'sql' => "varchar(64) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_category_template'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['wem_portfolio_category_template'],
    'default' => 'wem_portfolio_category_default',
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => ['tl_module_wem_portfolio', 'getPortfolioCategoriesTemplates'],
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
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_category_sort'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['wem_portfolio_category_sort'],
    'default' => 'global',
    'exclude' => true,
    'inputType' => 'select',
    'reference' => $GLOBALS['TL_LANG']['tl_module']['wem_portfolio_category_sort'],
    'options' => ['title_ASC', 'title_DESC'],
    'eval' => ['tl_class' => 'w50'],
    'sql' => "varchar(64) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_item_sort'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['wem_portfolio_item_sort'],
    'default' => 'global',
    'exclude' => true,
    'inputType' => 'select',
    'reference' => $GLOBALS['TL_LANG']['tl_module']['wem_portfolio_item_sort'],
    'options_callback' => ['tl_module_wem_portfolio', 'getSortingCategories'],
    'save_callback' => [
        ['tl_module_wem_portfolio', 'checkIfMultiCategories'],
    ],
    'eval' => ['tl_class' => 'w50'],
    'sql' => "varchar(64) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_list_module'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['wem_portfolio_list_module'],
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => ['tl_module_wem_portfolio', 'getListModules'],
    'reference' => &$GLOBALS['TL_LANG']['tl_module'],
    'eval' => ['includeBlankOption' => true, 'tl_class' => 'w50'],
    'sql' => 'int(10) unsigned NOT NULL default 0',
];

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Web ex Machina <http://www.webexmachina.fr>
 */
class tl_module_wem_portfolio extends Backend // @todo: move to DataContainer namespace
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
     * Get all portfolio list modules and return them as array.
     *
     * @return array
     */
    public function getListModules(): array
    {
        $arrModules = [];
        $objModules = $this->Database->execute("SELECT m.id, m.name, t.name AS theme FROM tl_module m LEFT JOIN tl_theme t ON m.pid=t.id WHERE m.type='wem_portfolio_list' ORDER BY t.name, m.name");

        while ($objModules->next()) {
            $arrModules[$objModules->theme][$objModules->id] = $objModules->name.' (ID '.$objModules->id.')';
        }

        return $arrModules;
    }

    /**
     * Remove "category" option from sorting options if we have several categories to display.
     *
     * @param DataContainer $dc
     * @return array
     */
    public function getSortingCategories(DataContainer $dc): array
    {
        $arrOptions = ['date_ASC', 'date_DESC', 'title_ASC', 'title_DESC'];

        if ($dc->activeRecord->wem_portfolio_categories && 1 < \count(StringUtil::deserialize($dc->activeRecord->wem_portfolio_categories))) {
            unset($arrOptions[array_search('category', $arrOptions, true)]);
        }

        $options = [];
        foreach ($arrOptions as $o) {
            $options[$o] = $GLOBALS['TL_LANG']['tl_module']['wem_portfolio_item_sort'][$o];
        }

        return $options;
    }

    /**
     * Throw an exception when we want a sorting by category if we have several categories.
     *
     * @param mixed         $varValue [Value saved]
     * @param DataContainer $dc       [Datacontainer]
     *
     * @throws \Exception
     *
     * @return mixed [Value to save]
     */
    public function checkIfMultiCategories($varValue, DataContainer $dc)
    {
        if ('category' === $varValue && $dc->activeRecord->wem_portfolio_categories && 1 < \count(StringUtil::deserialize($dc->activeRecord->wem_portfolio_categories))) {
            throw new \Exception($GLOBALS['TL_LANG']['WEM']['PORTFOLIO']['cannotUseCategorySorting']);
        }

        return $varValue;
    }

    /**
     * Return all news templates as array.
     *
     * @return array
     */
    public function getPortfolioItemTemplates(): array
    {
        return $this->getTemplateGroup('wem_portfolio_item_');
    }

    /**
     * Return all news templates as array.
     *
     * @return array
     */
    public function getPortfolioCategoriesTemplates(): array
    {
        return $this->getTemplateGroup('wem_portfolio_category_');
    }

    /**
     * Return all attributes usable as filters.
     *
     * @return array
     * @throws Exception
     */
    public function getPortfolioFilters(): array
    {
        $arrFilters = ['category' => 'Catégorie']; // @todo: translate
        
        $objAttributes = \WEM\PortfolioBundle\Model\Attribute::findItems(['useAsFilter' => 1]);
        if ($objAttributes instanceof Collection) {
            while ($objAttributes->next()) {
                $arrFilters[$objAttributes->alias] = $objAttributes->title;
            }
        }

        return $arrFilters;
    }
}
