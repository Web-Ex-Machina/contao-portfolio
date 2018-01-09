<?php

/**
 * Module Portfolio for Contao Open Source CMS
 *
 * Copyright (c) 2015-2018 Web ex Machina
 *
 * @author Web ex Machina <http://www.webexmachina.fr>
 */

namespace WEM\Portfolio\Module;

use \RuntimeException as Exception;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Patchwork\Utf8;

/**
 * Front end module "portfolio reader".
 *
 * @property array  $portfolio_categories
 *
 * @author Web ex Machina <http://www.webexmachina.fr>
 */
class PortfolioReader extends \Module
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_wem_portfolio_reader';

	/**
	 * Display a wildcard in the back end
	 *
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			/** @var BackendTemplate|object $objTemplate */
			$objTemplate = new \BackendTemplate('be_wildcard');

			$objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['wem_portfolio_reader'][0]) . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}

		$this->portfolio_categories = \StringUtil::deserialize($this->portfolio_categories);

		// Return if there are no archives
		if (!is_array($this->portfolio_categories) || empty($this->portfolio_categories))
		{
			return '';
		}

		return parent::generate();
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		try
		{

		}
		catch(Exception $e)
		{
			$this->Template->blnError = true;
			$this->Template->strError = "Une erreur est survenue : ".$e->getMessage();
		}
	}
}