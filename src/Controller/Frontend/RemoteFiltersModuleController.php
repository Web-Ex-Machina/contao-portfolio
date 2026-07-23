<?php

declare(strict_types=1);

/**
 * Contao Portfolio for Contao Open Source CMS
 * Copyright (c) 2015-2024 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-portfolio
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-portfolio/
 */

namespace WEM\PortfolioBundle\Controller\Frontend;

use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\Environment;
use Contao\Input;
use Contao\Model\Collection;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\Template;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WEM\PortfolioBundle\Model\Portfolio;
use WEM\PortfolioBundle\Model\PortfolioFeed;
use WEM\PortfolioBundle\Model\PortfolioFeedAttribute;
use WEM\UtilsBundle\Classes\StringUtil;

#[AsFrontendModule(
    RemoteFiltersModuleController::TYPE, 
    category: 'wem_portfolio',
    template: 'mod_wem_portfolio_filters'
)]
class RemoteFiltersModuleController extends FiltersModuleController
{
    public const TYPE = 'wem_portfolio_remote_filters';

    /**
     * List filters.
     *
     * @var array<string>
     */
    protected $filters = [];

    /**
     * List attributes.
     *
     * @var array<string>
     */
    protected $attributes = [];

    public function __construct(
        private readonly ContentUrlGenerator $contentUrlGenerator
    ) {
        parent::__construct($contentUrlGenerator);
    }

    /**
     * Retrieve filters.
     */
    protected function getFilters(): array
    {
        return StringUtil::deserialize($this->model->wem_portfolio_remote_filters);
    }

    /**
     * Retrieve a specific filter
     */
    protected function getFilter(string $f): PortfolioFeedAttribute
    {
        $api = $this->getApi();
        
        if (null === $api) {
            throw new Exception("The API is not reachable");
        }

        $attributes = $api->searchAttributes(['pid' => $this->config['pid'], 'name' => $f]);

        if (!$attributes) {
            throw new Exception(\sprintf("Filter %s not found", $f));
        }

        $attr = $attributes->current();

        // Store attribute to avoid API calls
        $this->attributes[$attr->name] = $attr;

        return $attr;
    }

    /**
     * Retrieve a specific filter config
     */
    protected function getFilterConfig(string $f): array
    {
        return $this->attributes[$f]->dcaConfig;
    }

    /**
     * Retrieve filter options
     */
    protected function getFilterOptions(string $f): Collection
    {
        return $this->findRemoteOptions($this->baseConfig, $f);
    }

    /**
     * Retrieve filter label
     */
    protected function getFilterLabel(PortfolioFeedAttribute $attr): string
    {
        return $attr->label;
    }

    /**
     * Retrieve filter select options
     */
    protected function getFilterSelectOptions(PortfolioFeedAttribute $attr): array
    {
        return StringUtil::deserialize($attr->options);
    }

    protected function findRemoteOptions(array $config, string $groupby): Collection
    {
        $api = $this->getApi();
        
        if (null === $api) {
            throw new Exception("The API is not reachable");
        }

        $config['options'] = [
            'order' => $groupby . '-asc',
            'group' => $groupby,
        ];

        return $api->getItems($config);
    }
}
