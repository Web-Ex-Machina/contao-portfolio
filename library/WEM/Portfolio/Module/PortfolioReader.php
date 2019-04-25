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

use WEM\Portfolio\Controller\Item;

/**
 * Front end module "portfolio reader".
 *
 * @property array  $portfolio_categories
 *
 * @author Web ex Machina <http://www.webexmachina.fr>
 */
class PortfolioReader extends Portfolio
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
		if (TL_MODE == 'BE'){
			/** @var BackendTemplate|object $objTemplate */
			$objTemplate = new \BackendTemplate('be_wildcard');

			$objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['wem_portfolio_reader'][0]) . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}

		// Set the item from the auto_item parameter
		if (!isset($_GET['items']) && \Config::get('useAutoItem') && isset($_GET['auto_item']))
			\Input::setGet('items', \Input::get('auto_item'));

		// Do not index or cache the page if no news item has been specified
		if (!\Input::get('items')){
			/** @var PageModel $objPage */
			global $objPage;

			$objPage->noSearch = 1;
			$objPage->cache = 0;

			return '';
		}

		return parent::generate();
	}

	/**
	 * Generate the module
	 */
	protected function compile(){
		/** @var PageModel $objPage */
		global $objPage;

		$this->Template->articles = '';
		$this->Template->referer = 'javascript:history.go(-1)';
		$this->Template->back = $GLOBALS['TL_LANG']['MSC']['goBack'];

		// Get the portfolio item
		$arrConfig = [];
		$arrConfig['getCategory'] = true;
		$arrItem = Item::getItem(\Input::get('items'), $arrConfig);

		if (null === $arrItem)
			throw new PageNotFoundException('Page not found: ' . \Environment::get('uri'));

		$this->Template->articles = $this->parseItem($arrItem, $this->wem_portfolio_template);

		// Overwrite the page title (see #2853 and #4955)
		if ($arrItem['title'] != '')
			$objPage->pageTitle = strip_tags(\StringUtil::stripInsertTags($arrItem['title']));

		// Overwrite the page description
		if ($arrItem['teaser'] != '')
			$objPage->description = $this->prepareMetaDescription($arrItem['teaser']);
	}
}