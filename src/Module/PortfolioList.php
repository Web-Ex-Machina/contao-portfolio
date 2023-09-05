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

namespace WEM\PortfolioBundle\Module;

use Contao\CoreBundle\Exception\PageNotFoundException;
use Exception;
use WEM\PortfolioBundle\Model\Item;

/**
 * Front end module "portfolio list".
 */
class PortfolioList extends Portfolio
{
    /**
     * List of categories
     *
     * @var array
     */
    protected $arrCategories = [];

    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'mod_wem_portfolio_list';

    /**
     * Display a wildcard in the back end.
     *
     * @return string
     */
    public function generate()
    {
        if (TL_MODE === 'BE') {
            /** @var BackendTemplate|object $objTemplate */
            $objTemplate = new \BackendTemplate('be_wildcard');

            $objTemplate->wildcard = '### '.utf8_strtoupper($GLOBALS['TL_LANG']['FMD']['wem_portfolio_list'][0]).' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id='.$this->id;

            return $objTemplate->parse();
        }

        return parent::generate();
    }

    /**
     * Generate the module.
     */
    protected function compile(): void
    {
        try {
            $limit = null;
            $offset = (int) ($this->skipFirst);
            $arrOptions = [];
            $arrOptions['order'] = $this->getSortingValue($this->wem_portfolio_item_sort);
            $bundles = \System::getContainer()->getParameter('kernel.bundles');

            // Load categories
            if ($this->wem_portfolio_categories) {
                foreach (deserialize($this->wem_portfolio_categories) as $c) {
                    $this->arrCategories[] = $this->getCategory($c);
                }
            }

            // Maximum number of items
            if ($this->numberOfItems > 0) {
                $limit = $this->numberOfItems;
            }

            // If we want filters
            if ($this->wem_portfolio_filters) {
                $this->filters = $this->getAvailableFilters();
            }

            $this->Template->articles = [];
            $this->Template->rt = \RequestToken::get();
            $this->Template->request = \Environment::get('request');
            $this->Template->empty = $GLOBALS['TL_LANG']['WEM']['PORTFOLIO']['empty'];
            $this->Template->filterBy = $GLOBALS['TL_LANG']['WEM']['PORTFOLIO']['filterBy'];

            global $objPage;
            $arrConfig['published'] = 1;
            $arrConfig['categories'] = deserialize($this->wem_portfolio_categories);

            // Adjust the config
            if ($this->filters) {
                foreach ($this->filters as $filter) {
                    foreach ($filter['options'] as $option) {
                        if ($option['selected']) {
                            $arrConfig['attributes'][] = ['attribute' => $filter['id'], 'value' => $option['value']];
                        }
                    }
                }
            }

            // Get the total number of items
            $intTotal = Item::countItems($arrConfig, $arrOptions);

            if ($intTotal < 1) {
                return;
            }

            $total = $intTotal - $offset;

            // Split the results
            if ($this->perPage > 0 && (!isset($limit) || $this->numberOfItems > $this->perPage)) {
                // Adjust the overall limit
                if (isset($limit)) {
                    $total = min($limit, $total);
                }

                // Get the current page
                $id = 'page_n'.$this->id;
                $page = (null !== \Input::get($id)) ? \Input::get($id) : 1;

                // Do not index or cache the page if the page number is outside the range
                if ($page < 1 || $page > max(ceil($total / $this->perPage), 1)) {
                    throw new PageNotFoundException('Page not found: '.\Environment::get('uri'));
                }

                // Set limit and offset
                $limit = $this->perPage;
                $offset += (max($page, 1) - 1) * $this->perPage;
                $skip = (int) ($this->skipFirst);

                // Overall limit
                if ($offset + $limit > $total + $skip) {
                    $limit = $total + $skip - $offset;
                }

                // Add the pagination menu
                $objPagination = new \Pagination($total, $this->perPage, \Config::get('maxPaginationLinks'), $id);
                $this->Template->pagination = $objPagination->generate("\n  ");
            }

            $objItems = Item::findItems($arrConfig, ($limit ?: 0), $offset, $arrOptions);

            // Add the filters
            if ($this->wem_portfolio_filters && !empty($this->filters)) {
                $this->Template->filters = $this->filters;
            }

            if (null !== $objItems) {
                $this->Template->items = $this->parseItems($objItems->fetchAll(), $this->wem_portfolio_item_template);
            }
        } catch (Exception $e) {
            $this->Template->error = true;
            $this->Template->message = $e->getMessage();
            $this->Template->trace = $e->getTrace();
        }
    }
}
