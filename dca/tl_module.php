<?php

/**
 * Module Portfolio for Contao Open Source CMS
 *
 * Copyright (c) 2015-2019 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

/**
 * Add palettes to tl_module
 */
$GLOBALS['TL_DCA']['tl_module']['palettes']['wem_portfolio_list']    = '{title_legend},name,headline,type;{config_legend},jumpTo,wem_portfolio_filters,numberOfItems,perPage,skipFirst;{template_legend:hide},wem_portfolio_template,customTpl;{image_legend:hide},imgSize;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['wem_portfolio_reader']  = '{title_legend},name,headline,type;{template_legend:hide},wem_portfolio_template,customTpl;{image_legend:hide},imgSize;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_template'] = array(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['wem_portfolio_template'],
    'default'                 => 'wem_portfolio_item',
    'exclude'                 => true,
    'inputType'               => 'select',
    'options_callback'        => array('tl_module_wem_portfolio', 'getPortfolioTemplates'),
    'eval'                    => array('tl_class'=>'w50'),
    'sql'                     => "varchar(64) NOT NULL default ''"
);
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_filters'] = array(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['wem_portfolio_filters'],
    'exclude'                 => true,
    'inputType'               => 'select',
    'options_callback'        => array('tl_module_wem_portfolio', 'getPortfolioFilters'),
    'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'w50', 'chosen'=>true, 'multiple'=>true),
    'sql'                     => "blob NULL"
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Web ex Machina <http://www.webexmachina.fr>
 */
class tl_module_wem_portfolio extends Backend
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
     * Return all news templates as array
     *
     * @return array
     */
    public function getPortfolioTemplates()
    {
        return $this->getTemplateGroup('wem_portfolio_');
    }

    /**
     * Return all attributes usable as filters
     *
     * @return array
     */
    public function getPortfolioFilters()
    {
        $objAttributes = \WEM\Portfolio\Model\Attribute::findItems(["useAsFilter"=>1]);

        if (!$objAttributes || 0 == $objAttributes->count()) {
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
