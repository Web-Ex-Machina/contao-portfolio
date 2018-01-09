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
$GLOBALS['TL_DCA']['tl_module']['palettes']['wem_portfolio_list']    = '{title_legend},name,headline,type;{config_legend},jumpTo,numberOfItems,perPage,skipFirst;{template_legend:hide},customTpl;{image_legend:hide},imgSize;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

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
}