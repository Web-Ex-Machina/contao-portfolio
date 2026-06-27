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

namespace WEM\PortfolioBundle\Controller\Frontend;

use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Environment;
use Contao\Input;
use Contao\Model\Collection;
use Contao\ModuleModel;
use Contao\Pagination;
use Contao\System;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WEM\PortfolioBundle\Model\Portfolio;
use WEM\PortfolioBundle\Model\PortfolioFeed;
use WEM\UtilsBundle\Classes\StringUtil;

#[AsFrontendModule(
    ListModuleController::TYPE, 
    category: 'wem_portfolio',
    template: 'mod_wem_portfolio_list'
)]
class ListModuleController extends ModuleController
{
    public const TYPE = 'wem_portfolio_list';

    protected ?array $config = [];

    protected ?int $limit = 0;

    protected ?int $offset = 0;

    protected array $options = [];

    protected ?array $filters = [];

    protected bool $readFromRemote = false;
    protected ?PortfolioFeed $readFromRemoteFeed = null;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Generate the module.
     */
    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $this->loadDatacontainer('tl_wem_portfolio');
        $this->loadLanguageFile('tl_wem_portfolio');
        $this->wem_portfolio_feeds = StringUtil::deserialize($this->wem_portfolio_feeds);

        // Return if there are no archives
        if (empty($this->wem_portfolio_feeds) || !\is_array($this->wem_portfolio_feeds)) {
            throw new \Exception('wem_portfolio_feeds not found.');
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

        $template->items = [];
        $template->empty = $GLOBALS['TL_LANG']['WEM']['PORTFOLIO']['empty'];

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
            $template->filters = $this->getFrontendModule($this->wem_portfolio_filters_module);
        }

        // Get the total number of items
        if ($this->readFromRemote) {
            $intTotal = $this->countRemoteItems($this->config, $this->readFromRemoteFeed);
        } else {
            $intTotal = Portfolio::countItems($this->config);
        }

        if ($intTotal < 1) {
            return $template->getResponse();
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
            $template->pagination = $objPagination->generate("\n  ");
        }

        if ($this->readFromRemote) {
            $objItems = $this->findRemoteItems($this->config, $this->readFromRemoteFeed, (int) $page ?: 1, (int) $this->limit ?: 0, (int) $this->offset ?: 0, $this->options['order'] ?: "");
        } else {
            $objItems = Portfolio::findItems($this->config, (int) $this->limit ?: 0, (int) $this->offset ?: 0, $this->options);
        }

        // Add the items
        if ($objItems instanceof Collection) {
            $template->items = $this->parsePortfolios($objItems);
        }

        $template->moduleId = $this->id;

        // Catch auto_item
        if (Input::get('auto_item')) {
            $objPortfolio = Portfolio::findItems(['slug' => Input::get('auto_item')], 1);

            $template->openModalOnLoad = true;
            $template->portfolioId = $objPortfolio->first()->id;
        }

        return $template->getResponse();
    }
}
