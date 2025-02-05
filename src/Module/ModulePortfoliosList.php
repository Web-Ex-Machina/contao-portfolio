<?php

declare(strict_types=1);

/**
 * Contao Portfolio for Contao Open Source CMS
 * Copyright (c) 2015-2025 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-portfolio
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-portfolio/
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
use WEM\PortfolioBundle\Model\PortfolioFeed;
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

    protected bool $readFromRemote = false;
    protected ?PortfolioFeed $readFromRemoteFeed = null;

    /**
     * Display a wildcard in the back end.
     *
     * @throws \ErrorException
     */
    public function generate(): string
    {
        $scopeMatcher = System::getContainer()->get('wem.scope_matcher');
        if ($scopeMatcher->isBackend()) {
            $objTemplate = new BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### '.strtoupper($GLOBALS['TL_LANG']['FMD']['wem_portfolio_feed_list'][0]).' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id='.$this->id;

            return $objTemplate->parse();
        }

        $this->loadDatacontainer('tl_wem_portfolio');
        $this->loadLanguageFile('tl_wem_portfolio');
        $this->wem_portfolio_feeds = StringUtil::deserialize($this->wem_portfolio_feeds);

        // Return if there are no archives
        if (empty($this->wem_portfolio_feeds) || !\is_array($this->wem_portfolio_feeds)) {
            throw new \ErrorException('wem_portfolio_feeds not found.');
        }

        // Check if we have remote feeds
        foreach ($this->wem_portfolio_feeds as $f) {
            $objFeed = PortfolioFeed::findByPk($f);

            // If we have one remote feed, consider we must
            // get everything from remote, to improve later
            if ($objFeed->readFromRemote) {
                $this->readFromRemote = true;
                $this->readFromRemoteFeed = $objFeed;

                break;
            }
        }

        return parent::generate();
    }

    /**
     * Generate the module.
     */
    protected function compile(): void
    {
        global $objPage;
        $this->limit = null;
        $this->offset = (int) $this->skipFirst;

        switch ($this->wem_portfolio_sort) {
            case 'order_date_asc': $this->options['order'] = 'date ASC';
                break;
            case 'order_date_desc': $this->options['order'] = 'date DESC';
                break;
            case 'order_headline_asc': $this->options['order'] = 'title ASC';
                break;
            case 'order_headline_desc': $this->options['order'] = 'title DESC';
                break;
        }

        // Maximum number of items
        if ($this->numberOfItems > 0) {
            $this->limit = $this->numberOfItems;
        }

        $this->Template->items = [];
        $this->Template->empty = $GLOBALS['TL_LANG']['WEM']['PORTFOLIO']['empty'];

        // Add pids
        $this->config = [
            'pid' => $this->wem_portfolio_feeds,
            'language' => System::getContainer()->get('request_stack')->getCurrentRequest()->getLocale(),
            'published' => 1,
        ];

        // Retrieve filters
        if ([] !== $_GET || [] !== $_POST) {
            foreach (array_keys($_GET) as $f) {
                if (!str_contains($f, 'portfolio_filter_')) {
                    continue;
                }

                if (Input::get($f)) {
                    $this->config[str_replace('portfolio_filter_', '', $f)] = Input::get($f);
                }
            }

            foreach (array_keys($_POST) as $f) {
                if (!str_contains($f, 'portfolio_filter_')) {
                    continue;
                }

                if (Input::post($f)) {
                    $this->config[str_replace('portfolio_filter_', '', $f)] = Input::post($f);
                }
            }
        }

        // Check if we have constraints to adjust config
        if ($this->wem_portfolio_addConstraints) {
            $arrWheres = StringUtil::deserialize($this->wem_portfolio_constraints);

            if (!empty($arrWheres)) {
                foreach ($arrWheres as $w) {
                    $this->config['where'][] = html_entity_decode($w);
                }
            }
        }

        // Retrieve filters
        if ($this->wem_portfolio_addFilters) {
            $this->Template->filters = $this->getFrontendModule($this->wem_portfolio_filters_module);
        }

        // Get the total number of items
        if ($this->readFromRemote) {
            $intTotal = $this->countRemoteItems($this->config, $this->readFromRemoteFeed);
        } else {
            $intTotal = Portfolio::countItems($this->config);
        }

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
            $id = 'page_n'.$this->id;
            $page = Input::get($id) ?? 1;

            // Do not index or cache the page if the page number is outside the range
            if ($page < 1 || $page > max(ceil($total / $this->perPage), 1)) {
                throw new PageNotFoundException('Page not found: '.Environment::get('uri'));
            }

            // Set limit and offset
            $this->limit = $this->perPage;
            $this->offset += (max($page, 1) - 1) * $this->perPage;
            $skip = (int) $this->skipFirst;

            // Overall limit
            if ($this->offset + $this->limit > $total + $skip) {
                $this->limit = $total + $skip - $this->offset;
            }

            // Add the pagination menu
            $objPagination = new Pagination($total, $this->perPage, Config::get('maxPaginationLinks'), $id);
            $this->Template->pagination = $objPagination->generate("\n  ");
        }

        if ($this->readFromRemote) {
            $objItems = $this->findRemoteItems($this->config, $this->readFromRemoteFeed, $page ?: 1, $this->limit ?: 0);
        } else {
            $objItems = Portfolio::findItems($this->config, $this->limit ?: 0, $this->offset ?: 0, $this->options);
        }

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
}
