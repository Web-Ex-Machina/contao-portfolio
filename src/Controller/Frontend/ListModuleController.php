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
use Contao\Controller;
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

    protected ?int $page = 0;

    protected ?int $limit = 0;

    protected ?int $offset = 0;

    protected array $options = [];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Generate the module.
     */
    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $this->model = $model;
        $this->model->wem_portfolio_feeds = $this->getFeeds();

        // Return if there are no archives
        if (empty($this->model->wem_portfolio_feeds) || !\is_array($this->model->wem_portfolio_feeds)) {
            throw new \Exception('wem_portfolio_feeds not found.');
        }

        $this->limit = null;
        $this->offset = (int) $this->model->skipFirst;

        switch ($this->model->wem_portfolio_sort) {
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
        if ($this->model->numberOfItems > 0) {
            $this->limit = $this->model->numberOfItems;
        }

        $template->items = [];
        $template->empty = $GLOBALS['TL_LANG']['WEM']['PORTFOLIO']['empty'];

        // Add pids
        $this->config = [
            'pid' => $this->model->wem_portfolio_feeds,
            'language' => $request->getLocale(),
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
        if ($this->model->wem_portfolio_addConstraints) {
            $arrWheres = StringUtil::deserialize($this->model->wem_portfolio_constraints);

            if (!empty($arrWheres)) {
                foreach ($arrWheres as $w) {
                    $this->config['where'][] = html_entity_decode($w);
                }
            }
        }

        // Retrieve filters
        $template->filters = $this->getFilters();

        // Get the total number of items
        $intTotal = $this->countItems();

        if ($intTotal < 1) {
            return $template->getResponse();
        }

        $this->page = 1;
        $total = $intTotal - $this->model->offset;

        // Split the results
        if ($this->model->perPage > 0 && (!isset($this->model->limit) || $this->model->numberOfItems > $this->model->perPage)) {
            // Adjust the overall limit
            if (isset($this->limit)) {
                $total = min($this->model->limit, $total);
            }

            // Get the current page
            $id = 'page_n'.$this->model->id;
            $this->page = Input::get($id) ?? 1;

            // Do not index or cache the page if the page number is outside the range
            if ($this->page < 1 || $this->page > max(ceil($total / $this->model->perPage), 1)) {
                throw new PageNotFoundException('Page not found: '.Environment::get('uri'));
            }

            // Set limit and offset
            $this->limit = $this->model->perPage;
            $this->offset += (max($this->page, 1) - 1) * $this->model->perPage;
            $skip = (int) $this->model->skipFirst;

            // Overall limit
            if ($this->model->offset + $this->model->limit > $total + $skip) {
                $this->model->limit = $total + $skip - $this->model->offset;
            }

            // Add the pagination menu
            $objPagination = new Pagination($total, $this->model->perPage, Config::get('maxPaginationLinks'), $id);
            $template->pagination = $objPagination->generate("\n  ");
        }

        $objItems = $this->findItems();

        // Add the items
        if ($objItems instanceof Collection) {
            $template->items = $this->parsePortfolios($objItems);
        }

        $template->moduleId = $this->model->id;

        return $template->getResponse();
    }

    protected function getFilters(): string
    {
        if ($this->model->wem_portfolio_addFilters) {
            return Controller::getFrontendModule($this->model->wem_portfolio_filters_module);
        }

        return '';
    }
}
