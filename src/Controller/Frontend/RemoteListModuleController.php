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
use Contao\Pagination;
use Contao\System;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WEM\PortfolioBundle\Model\Portfolio;
use WEM\PortfolioBundle\Model\PortfolioFeed;
use WEM\UtilsBundle\Classes\StringUtil;

#[AsFrontendModule(
    RemoteListModuleController::TYPE, 
    category: 'wem_portfolio',
    template: 'mod_wem_portfolio_list'
)]
class RemoteListModuleController extends ListModuleController
{
    public const TYPE = 'wem_portfolio_remote_list';

    public function __construct() 
    {
        parent::__construct();
    }

    protected function getFeeds(): array
    {
        return StringUtil::deserialize($this->model->wem_portfolio_remote_feeds);
    }

    protected function getFilters(): string
    {
        if ($this->model->wem_portfolio_remote_addFilters) {
            return Controller::getFrontendModule($this->model->wem_portfolio_remote_filters_module);
        }

        return '';
    }

    protected function countItems(): int
    {
        $api = $this->getApi();
        
        if (null === $api) {
            throw new Exception("The API is not reachable");
        }

        return $api->countItems($this->config);
    }

    protected function findItems(): Collection
    {
        $api = $this->getApi();
        
        if (null === $api) {
            throw new Exception("The API is not reachable");
        }

        $this->options['order'] = str_replace(" ", "-", $this->options['order']);

        $config = $this->config;
        $config['limit'] = $this->limit;
        $config['offset'] = $this->offset;
        $config['options'] = $this->options;

        return $api->getItems($this->config);
    }
}
