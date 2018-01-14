<?php

/**
 * Module Portfolio for Contao Open Source CMS
 *
 * Copyright (c) 2015-2018 Web ex Machina
 *
 * @author Web ex Machina <http://www.webexmachina.fr>
 */

/**
 * Add palettes to tl_module
 */
$GLOBALS['TL_DCA']['tl_module']['palettes']['wem_portfolio_list']    = '{title_legend},name,headline,type;{config_legend},jumpTo,wem_portfolio_attributes,wem_portfolio_tags,numberOfItems,perPage,skipFirst;{template_legend:hide},wem_portfolio_template,customTpl;{image_legend:hide},imgSize;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['wem_portfolio_reader']  = '{title_legend},name,headline,type;{config_legend},wem_portfolio_attributes,wem_portfolio_tags;{template_legend:hide},wem_portfolio_template,customTpl;{image_legend:hide},imgSize;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_template'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['wem_portfolio_template'],
	'default'                 => 'wem_portfolio_item',
	'exclude'                 => true,
	'inputType'               => 'select',
	'options_callback'        => array('tl_module_wem_portfolio', 'getPortfolioTemplates'),
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "varchar(64) NOT NULL default ''"
);
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_attributes'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['wem_portfolio_attributes'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'w50'),
	'sql'                     => "char(1) NOT NULL default ''"
);
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_tags'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['wem_portfolio_tags'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'w50'),
	'sql'                     => "char(1) NOT NULL default ''"
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
}