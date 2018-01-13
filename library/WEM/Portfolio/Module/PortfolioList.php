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
 * Front end module "portfolio list".
 *
 * @property array  $portfolio_categories
 *
 * @author Web ex Machina <http://www.webexmachina.fr>
 */
class PortfolioList extends Portfolio
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_wem_portfolio_list';

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

			$objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['wem_portfolio_list'][0]) . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}

		return parent::generate();
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		$limit = null;
		$offset = intval($this->skipFirst);
		$arrOptions = array();

		// Maximum number of items
		if($this->numberOfItems > 0)
			$limit = $this->numberOfItems;

		$this->Template->articles = array();
		$this->Template->empty = $GLOBALS['TL_LANG']['MSC']['emptyList'];

		global $objPage;
		$arrConfig["page"] = $objPage->id;

		// Get the total number of items
		$intTotal = Item::countItems($arrConfig, $arrOptions);

		if($intTotal < 1)
			return;

		$total = $intTotal - $offset;

		// Split the results
		if ($this->perPage > 0 && (!isset($limit) || $this->numberOfItems > $this->perPage))
		{
			// Adjust the overall limit
			if (isset($limit))
			{
				$total = min($limit, $total);
			}

			// Get the current page
			$id = 'page_n' . $this->id;
			$page = (\Input::get($id) !== null) ? \Input::get($id) : 1;

			// Do not index or cache the page if the page number is outside the range
			if ($page < 1 || $page > max(ceil($total/$this->perPage), 1))
			{
				throw new PageNotFoundException('Page not found: ' . \Environment::get('uri'));
			}

			// Set limit and offset
			$limit = $this->perPage;
			$offset += (max($page, 1) - 1) * $this->perPage;
			$skip = intval($this->skipFirst);

			// Overall limit
			if ($offset + $limit > $total + $skip)
			{
				$limit = $total + $skip - $offset;
			}

			// Add the pagination menu
			$objPagination = new \Pagination($total, $this->perPage, \Config::get('maxPaginationLinks'), $id);
			$this->Template->pagination = $objPagination->generate("\n  ");
		}

		if($this->jumpTo && $objRedirectPage = \PageModel::findByPk($this->jumpTo))
		{
			$this->jumpTo = $objRedirectPage;
		}

		$objItems = Item::getItems($arrConfig, ($limit ?: 0), $offset, $arrOptions);

		// Add the articles
		if ($objItems !== null)
		{
			$this->Template->items = $this->parseItems($objItems, $this->wem_portfolio_template);
		}
	}
}