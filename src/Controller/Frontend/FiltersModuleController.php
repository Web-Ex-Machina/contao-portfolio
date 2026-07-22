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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WEM\PortfolioBundle\Model\Portfolio;
use WEM\PortfolioBundle\Model\PortfolioFeed;
use WEM\PortfolioBundle\Model\PortfolioFeedAttribute;
use WEM\UtilsBundle\Classes\StringUtil;

#[AsFrontendModule(
    FiltersModuleController::TYPE, 
    category: 'wem_portfolio',
    template: 'mod_wem_portfolio_filters'
)]
class FiltersModuleController extends ModuleController
{
    public const TYPE = 'wem_portfolio_filters';

    protected ?array $baseConfig = [];

    /**
     * List filters.
     *
     * @var array<string>
     */
    protected $filters = [];

    public function __construct(
        private readonly ContentUrlGenerator $contentUrlGenerator
    ) {
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

        // Add pids
        $this->config = [
            'pid' => $this->model->wem_portfolio_feeds, 
            'published' => 1
        ];
        $this->baseConfig = $this->config;

        // Retrieve filters
        $this->buildFilters();

        // Add search filter
        $this->addSearchFilter();

        $template->filters = $this->filters;
        $template->moduleId = $this->model->id;

        // Define where the form is redirected
        if ($this->model->jumpTo) {
            $page = PageModel::findById($this->model->jumpTo);
            $template->formAction = $this->contentUrlGenerator->generate($page);
        } else {
            $template->formAction = $request->getRequestUri();
        }

        return $template->getResponse();
    }

    /**
     * Retrieve filters.
     */
    protected function getFilters(): array
    {
        return StringUtil::deserialize($this->model->wem_portfolio_filters);
    }

    /**
     * Retrieve a specific filter
     */
    protected function getFilter(string $f): PortfolioFeedAttribute
    {
        $objAttributes = PortfolioFeedAttribute::findItems(['pid' => $this->config['pid'], 'name' => $f], 1);

        return $objAttributes->current();
    }

    /**
     * Retrieve a specific filter config
     */
    protected function getFilterConfig(string $f): array
    {
        Controller::loadDataContainer('tl_wem_portfolio');

        return $GLOBALS['TL_DCA']['tl_wem_portfolio']['fields'][$f];
    }

    /**
     * Retrieve filter options
     */
    protected function getFilterOptions(string $f): Collection
    {
        return Portfolio::findItems($this->baseConfig, 0, 0, ['group' => $f]);
    }

    /**
     * Retrieve filter label
     */
    protected function getFilterLabel(PortfolioFeedAttribute $attr): string
    {
        return $attr->getL10nLabel('filterLabel') ?: $attr->getL10nLabel('label');
    }

    /**
     * Retrieve filter select options
     */
    protected function getFilterSelectOptions(PortfolioFeedAttribute $attr): array
    {
        return StringUtil::deserialize($attr->getL10nLabel('options'));
    }

    /**
     * Retrieve list filters.
     */
    protected function buildFilters(): void
    {
        // Retrieve and format dropdowns filters
        $filters = $this->getFilters();

        if (\is_array($filters) && [] !== $filters) {
            foreach ($filters as $f) {
                if ($this->shouldBeSkipped($f . ' != ""')) {
                    continue;
                }

                $this->addFilter($f);
            }
        }
    }

    protected function addFilter(string $f): void
    {
        $attribute = $this->getFilter($f);
        $field = $this->getFilterConfig($f);

        $fName = \sprintf(
            'portfolio_filter_%s%s', 
            $f, 
            array_key_exists('multiple', $field['eval']) && true === $field['eval']['multiple'] ? '[]' : ''
        );
        $fGet = \sprintf('portfolio_filter_%s', $f);

        $filter = [
            'type' => $field['inputType'],
            'name' => $fName,
            'label' => $this->getFilterLabel($attribute),
            'value' => Input::get($fGet) ?: '',
            'options' => [],
            'multiple' => $field['eval']['multiple'] ?? false,
        ];

        switch ($field['inputType']) {
            case 'select':
                if (\is_array($field['options_callback'])) {
                    $strClass = $field['options_callback'][0];
                    $strMethod = $field['options_callback'][1];

                    $this->import($strClass);
                    $options = $this->$strClass->$strMethod($this);
                } elseif (\is_callable($field['options_callback'])) {
                    $options = $field['options_callback']($this);
                } else {
                    $opts = $this->getFilterSelectOptions($attribute);

                    if (is_array($opts) && !empty($opts)) {
                        foreach($opts as $opt) {
                            $options[$opt['value']] = $opt['label'];
                        }
                    }
                }

                foreach ($options as $value => $label) {
                    if (\is_array($label)) {
                        foreach ($label as $subValue => $subLabel) {

                            $statement = $field['eval']['multiple'] 
                                ? $f . ' LIKE "%%'. $subValue .'%%"' 
                                : $f . ' = "'. $subValue .'"'
                            ;

                            if ($this->shouldBeSkipped($statement)) {
                                return;
                            }

                            $filter['options'][$value]['options'][] = [
                                'value' => $subValue,
                                'label' => $subLabel,
                                'selected' => $this->isOptionSelected($fGet, $subValue, $filter['multiple']),
                            ];
                        }
                    } else {
                        $statement = $field['eval']['multiple'] 
                            ? $f . ' LIKE "%%'. $value .'%%"' 
                            : $f . ' = "'. $value .'"'
                        ;
                        
                        if ($this->shouldBeSkipped($statement)) {
                            return;
                        }

                        $filter['options'][] = [
                            'value' => $value,
                            'label' => $label,
                            'selected' => $this->isOptionSelected($fGet, $value, $filter['multiple']),
                        ];
                    }
                }

                break;

            case 'listWizard':
                $objOptions = $this->getFilterOptions($f);

                if ($objOptions) {
                    $filter['type'] = 'select';
                    if ($filter['multiple']) {
                        $filter['name'] .= '[]';
                    }

                    while ($objOptions->next()) {
                        if (!$objOptions->{$f}) {
                            return;
                        }

                        $subOptions = StringUtil::deserialize($objOptions->{$f});
                        foreach ($subOptions as $subOption) {
                            $statement = $field['eval']['multiple'] 
                                ? $f . ' LIKE "%%'. $subOption .'%%"' 
                                : $f . ' = "'. $subOption .'"'
                            ;
                            
                            if ($this->shouldBeSkipped($statement)) {
                                return;
                            }

                            $filter['options'][$subOption] = [
                                'value' => $subOption,
                                'label' => $subOption,
                                'selected' => $this->isOptionSelected($fGet, $subOption, $filter['multiple']),
                            ];
                        }
                    }
                }

                break;

            case 'text':
            default:
                $objOptions = $this->getFilterOptions($f);


                if ($objOptions && 0 < $objOptions->count()) {
                    $filter['type'] = 'select';
                    while ($objOptions->next()) {
                        if (!$objOptions->{$f}) {
                            continue;
                        }

                        if ($this->shouldBeSkipped($f . ' = "'. $objOptions->{$f} .'"')) {
                            continue;
                        }

                        $filter['options'][] = [
                            'value' => $objOptions->{$f},
                            'label' => $objOptions->{$f},
                            'selected' => $this->isOptionSelected($fGet, $objOptions->{$f}),
                        ];
                    }
                }

                break;
        }

        if ('select' === $filter['type'] && 1 >= \count($filter['options'])) {
            return;
        }

        if (null !== Input::get($fName) && '' !== Input::get($fName)) {
            $this->config[$f] = Input::get($fName);
        }


        $this->filters[] = $filter;
    }

    protected function isOptionSelected(string $f, string $v, bool $multiple = false) {
        return $multiple
            ? (null !== Input::get($f) && \in_array($v, Input::get($f ?? []), true))
            : (null !== Input::get($f) && Input::get($f) === $v)
        ;
    }

    // Add fulltext search if asked
    protected function addSearchFilter(): void
    {
        if ($this->model->portfolio_addSearch) {
            $this->filters[] = [
                'type' => 'text',
                'name' => 'portfolio_filter_search',
                'label' => $GLOBALS['TL_LANG']['WEM']['PORTFOLIO']['search'],
                'placeholder' => $GLOBALS['TL_LANG']['WEM']['PORTFOLIO']['searchPlaceholder'],
                'value' => Input::get('portfolio_filter_search') ?: '',
            ];

            if ('' !== Input::get('portfolio_filter_search') && null !== Input::get('portfolio_filter_search')) {
                $this->config['portfolio_filter_search'] = StringUtil::formatKeywords(Input::get('portfolio_filter_search'));
            }
        }
    }

    protected function shouldBeSkipped($statement): bool
    {
        if (!$this->model->wem_portfolio_hideFiltersWithNoResults) {
            return false;
        }

        $config = $this->config;
        $config['where'][] = $statement;

        return 0 === $this->countItems($config);
    }
}
