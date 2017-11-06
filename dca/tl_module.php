<?php

/**
 * Module Portfolio for Contao Open Source CMS
 *
 * Copyright (c) 2015-2017 Web ex Machina
 *
 * @author Web ex Machina <http://www.webexmachina.fr>
 */

/**
 * Add palettes to tl_module
 */
$GLOBALS['TL_DCA']['tl_module']['palettes']['wem_portfolio_list']    = '{title_legend},name,headline,type;{config_legend},wem_portfolio_categories,numberOfItems,perPage,skipFirst;{template_legend:hide},customTpl;{image_legend:hide},imgSize;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

/**
 * Add fields to tl_module
 */
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_portfolio_categories'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['wem_portfolio_categories'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'options_callback'        => array('tl_module_wem_portfolio', 'getPortfolioCategories'),
	'eval'                    => array('multiple'=>true, 'mandatory'=>true),
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
	 * Get all news archives and return them as array
	 *
	 * @return array
	 */
	public function getPortfolioCategories()
	{
		/*if (!$this->User->isAdmin && !is_array($this->User->news))
		{
			return array();
		}*/

		$arrCategories = array();
		$objCategories = $this->Database->execute("SELECT id, title FROM tl_wem_portfolio_category ORDER BY pid, sorting");

		while ($objCategories->next())
		{
			/*if ($this->User->hasAccess($objCategories->id, 'news'))
			{*/
				$arrCategories[$objCategories->id] = $objCategories->title;
			//}
		}

		return $arrCategories;
	}
}