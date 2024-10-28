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

namespace WEM\PortfolioBundle\Module;

use Contao\BackendTemplate;
use Contao\Input;
use Contao\System;
use WEM\PortfolioBundle\Model\Portfolio;
use WEM\PortfolioBundle\Model\PortfolioFeedAttribute;
use WEM\UtilsBundle\Classes\StringUtil;

/**
 * Front end module "portfolios filters".
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */
class ModulePortfoliosFilters extends ModulePortfolios
{
    /**
     * List filters.
     *
     * @var array<string>
     */
    protected $filters = [];

    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'mod_wem_portfolio_filters';

    /**
     * Display a wildcard in the back end.
     *
     * @return string
     */
    public function generate()
    {
        $scopeMatcher = System::getContainer()->get('wem.scope_matcher');
        if ($scopeMatcher->isBackend()) {
            $objTemplate = new BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### '.strtoupper($GLOBALS['TL_LANG']['FMD']['wem_portfolio_filters'][0]).' ###';
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
        // Catch Ajax requets
        $this->catchAjaxRequests();

        // Add pids
        $this->config = ['pid' => $this->wem_portfolio_feeds, 'published' => 1];

        // Retrieve filters
        $this->buildFilters();

        $this->Template->filters = $this->filters;
        $this->Template->moduleId = $this->id;
    }

    /**
     * Retrieve list filters.
     */
    protected function buildFilters(): void
    {
        // Retrieve and format dropdowns filters
        $filters = StringUtil::deserialize($this->wem_portfolio_filters);
        $this->loadDataContainer('tl_wem_portfolio');

        if (\is_array($filters) && [] !== $filters) {
            foreach ($filters as $f) {
                $objFeedAttribute = PortfolioFeedAttribute::findOneByName($f);

                $field = $GLOBALS['TL_DCA']['tl_wem_portfolio']['fields'][$f];
                $fName = \sprintf('portfolio_filter_%s%s', $f, $field['eval']['multiple'] ? '[]' : '');
                $fGet = \sprintf('portfolio_filter_%s', $f);

                $filter = [
                    'type' => $field['inputType'],
                    'name' => $fName,
                    'label' => $objFeedAttribute->getL10nLabel('filterLabel') ?: $objFeedAttribute->getL10nLabel('label'),
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
                        } elseif (\is_array($field['options'])) {
                            $options = $field['options'];
                        }

                        foreach ($options as $value => $label) {
                            if (\is_array($label)) {
                                foreach ($label as $subValue => $subLabel) {
                                    $filter['options'][$value]['options'][] = [
                                        'value' => $subValue,
                                        'label' => $subLabel,
                                        'selected' => null !== Input::get($fGet) && (Input::get($fGet) === $subValue || (\is_array(Input::get($fGet)) && \in_array($subValue, Input::get($fGet), true))),
                                    ];
                                }
                            } else {
                                $filter['options'][] = [
                                    'value' => $value,
                                    'label' => $label,
                                    'selected' => null !== Input::get($fGet) && (Input::get($fGet) === $value || (\is_array(Input::get($fGet)) && \in_array($value, Input::get($fGet), true))),
                                ];
                            }
                        }

                        break;

                    case 'listWizard':
                        $objOptions = Portfolio::findItemsGroupByOneField($f);

                        if ($objOptions) {
                            $filter['type'] = 'select';
                            if ($filter['multiple']) {
                                $filter['name'] .= '[]';
                            }

                            while ($objOptions->next()) {
                                if (!$objOptions->{$f}) {
                                    continue;
                                }

                                $subOptions = StringUtil::deserialize($objOptions->{$f});
                                foreach ($subOptions as $subOption) {
                                    $filter['options'][$subOption] = [
                                        'value' => $subOption,
                                        'label' => $subOption,
                                        'selected' => !$filter['multiple']
                                            ? (null !== Input::get($fName) && Input::get($fName) === $subOption)
                                            : (null !== Input::get($fName) && \in_array($subOption, Input::get($f ?? []), true)),
                                    ];
                                }
                            }
                        }

                        break;

                    case 'text':
                    default:
                        $objOptions = Portfolio::findItemsGroupByOneField($f);

                        if ($objOptions && 0 < $objOptions->count()) {
                            $filter['type'] = 'select';
                            while ($objOptions->next()) {
                                if (!$objOptions->{$f}) {
                                    continue;
                                }

                                $filter['options'][] = [
                                    'value' => $objOptions->{$f},
                                    'label' => $objOptions->{$f},
                                    'selected' => (null !== Input::get($fName) && Input::get($fName) === $objOptions->{$f}),
                                ];
                            }
                        }

                        break;
                }

                if ('select' === $filter['type'] && 1 >= \count($filter['options'])) {
                    continue;
                }

                if (null !== Input::get($fName) && '' !== Input::get($fName)) {
                    $this->config[$f] = Input::get($fName);
                }

                $this->filters[] = $filter;
            }
        }

        // Add fulltext search if asked
        if ($this->portfolio_addSearch) {
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
}
