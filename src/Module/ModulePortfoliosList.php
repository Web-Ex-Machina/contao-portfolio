<?php

declare(strict_types=1);

/**
 * Personal Data Manager for Contao Open Source CMS
 * Copyright (c) 2015-2024 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-smartgear
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/personal-data-manager/
 */

namespace WEM\PortfolioBundle\Module;

use Contao\BackendTemplate;
use Contao\Config;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Environment;
use Contao\Input;
use Contao\Model\Collection;
use Contao\Pagination;
use Contao\System;
use WEM\PortfolioBundle\Model\Portfolio;
use WEM\UtilsBundle\Classes\StringUtil;

/**
 * Front end module "portfolios list".
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */
class ModulePortfoliosList extends ModulePortfolios
{
    protected ?array $config = [];

    protected ?int $limit = 0;

    protected ?int $offset = 0;

    protected ?array $options = [];

    protected ?array $filters = [];

    protected $strTemplate = 'mod_wem_portfolio_list';

    /**
     * Generate the module.
     */
    protected function compile(): void
    {
        global $objPage;
        $this->limit = null;
        $this->offset = (int)$this->skipFirst;

        // Maximum number of items
        if ($this->numberOfItems > 0) {
            $this->limit = $this->numberOfItems;
        }

        $this->Template->items = [];
        $this->Template->empty = $GLOBALS['TL_LANG']['WEM']['PORTFOLIO']['empty'];

        // Add pids
        $this->config = ['pid' => $this->wem_portfolio_feeds, 'published' => 1];

        // Retrieve filters
        if ($_GET !== [] || $_POST !== []) {
            foreach ($_GET as $f => $v) {
                if (false === strpos($f, 'portfolio_filter_')) {
                    continue;
                }

                if (Input::get($f)) {
                    $this->config[str_replace('portfolio_filter_', '', $f)] = Input::get($f);
                }
            }

            foreach (array_keys($_POST) as $f) {
                if (false === strpos($f, 'portfolio_filter_')) {
                    continue;
                }

                if (Input::post($f)) {
                    $this->config[str_replace('portfolio_filter_', '', $f)] = Input::post($f);
                }
            }
        }

        // Retrieve filters
        if ($this->wem_portfolio_addFilters) {
            $this->Template->filters = $this->getFrontendModule($this->wem_portfolio_filters_module);
        }

        // Get the total number of items
        $intTotal = Portfolio::countItems($this->config);

        if ($intTotal < 1) {
            return;
        }

        $total = $intTotal - $this->offset;

        // Split the results
        if ($this->perPage > 0 && (!isset($this->limit) || $this->numberOfItems > $this->perPage)) {
            // Adjust the overall limit
            if (isset($this->limit)) {
                $total = min($this->limit, $total);
            }

            // Get the current page
            $id = 'page_n' . $this->id;
            $page = Input::get($id) ?? 1;

            // Do not index or cache the page if the page number is outside the range
            if ($page < 1 || $page > max(ceil($total / $this->perPage), 1)) {
                throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
            }

            // Set limit and offset
            $this->limit = $this->perPage;
            $this->offset += (max($page, 1) - 1) * $this->perPage;
            $skip = (int)$this->skipFirst;

            // Overall limit
            if ($this->offset + $this->limit > $total + $skip) {
                $this->limit = $total + $skip - $this->offset;
            }

            // Add the pagination menu
            $objPagination = new Pagination($total, $this->perPage, Config::get('maxPaginationLinks'), $id);
            $this->Template->pagination = $objPagination->generate("\n  ");
        }

        $objItems = Portfolio::findItems($this->config, ($this->limit ?: 0), ($this->offset ?: 0));


        // Add the items
        if ($objItems instanceof Collection) {
            $this->Template->items = $this->parsePortfolios($objItems);
        }

        $this->Template->moduleId = $this->id;

        // Catch auto_item
        if (Input::get('auto_item')) {
            $objPortfolio = Portfolio::findItems(['slug' => Input::get('auto_item')], 1);

            $this->Template->openModalOnLoad = true;
            $this->Template->portfolioId = $objPortfolio->first()->id;
        }
    }

    /**
     * Display a wildcard in the back end.
     * @return string
     *
     * @throws \ErrorException
     */
    public function generate()
    {
        $scopeMatcher = System::getContainer()->get('wem.scope_matcher');
        if ($scopeMatcher->isBackend()) {
            $objTemplate = new BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### ' . strtoupper($GLOBALS['TL_LANG']['FMD']['wem_portfolio_feed_list'][0]) . ' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

            return $objTemplate->parse();
        }

        // Load datacontainer and job feeds
        $this->loadDatacontainer('tl_wem_portfolio');
        $this->loadLanguageFile('tl_wem_portfolio');
        $this->wem_portfolio_feeds = StringUtil::deserialize($this->wem_portfolio_feeds);

        // Return if there are no archives
        if (empty($this->wem_portfolio_feeds) || !\is_array($this->wem_portfolio_feeds)) {
            throw new \ErrorException('wem_portfolio_feeds not found.');
        }

        return parent::generate();
    }
}
