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
use Contao\Combiner;
use Contao\Config;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Environment;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\Pagination;
use Contao\System;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use WEM\PortfolioBundle\Model\Portfolio as PortfolioModel;
use WEM\UtilsBundle\Classes\StringUtil;

/**
 * Front end module "portfolios list".
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */
class ModulePortfoliosList extends ModulePortfolios
{
    /**
     * List config.
     */
    protected ?array $config = [];

    /**
     * List limit.
     */
    protected ?int $limit = 0;

    /**
     * List offset.
     */
    protected ?int $offset = 0;

    /**
     * List options.
     */
    protected ?array $options = [];

    /**
     * List filters.
     */
    protected ?array $filters = [];

    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'mod_portfolioslist';

    private CsrfTokenManagerInterface $csrfTokenManager;

    private string $csrfTokenName;

    private SessionInterface $session;

    public function __construct($objModule, $csrfTokenManager, $csrfTokenName, SessionInterface $session, $strColumn = 'main')
    {
        parent::__construct($objModule, $strColumn);
        $this->csrfTokenManager = $csrfTokenManager;
        $this->csrfTokenName = $csrfTokenName;
        $this->session = $session;
    }

    /**
     * Generate the module.
     */
    protected function compile(): void
    {

        // Init session
        $objSession = $this->session;

        // If we have setup a form, allow module to use it later
        if ($this->portfolio_applicationForm) {
            $this->blnDisplayApplyButton = true;
        }

        // Catch Ajax requets
        if (Input::post('TL_AJAX') && (int)$this->id === (int)Input::post('module')) {
            try {
                switch (Input::post('action')) {
                    case 'seeDetails':
                        if (!Input::post('portfolio')) {
                            throw new \Exception(sprintf($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['argumentMissing'], 'portfolio'));
                        }

                        $objItem = PortfolioModel::findByPk(Input::post('portfolio'));

                        $this->portfolio_template = 'portfolio_details';// TODO : InsertTag
                        System::getContainer()->get('contao.insert_tag')->replace($this->parsePortfolio($objItem));
                        exit;

                    case 'apply':
                        if (!Input::post('portfolio')) {
                            throw new \Exception(sprintf($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['argumentMissing'], 'portfolio'));
                        }

                        // Put the portfolio in session
                        $objSession->set('wem_portfolio', Input::post('portfolio'));
                        System::getContainer()->get('contao.insert_tag')->replace($this->getApplicationForm((int)Input::post('portfolio')));
                        exit;

                    default:
                        throw new \Exception(sprintf($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['unknownRequest'], Input::post('action')));
                }
            } catch (\Exception $e) {
                $arrResponse = ['status' => 'error', 'msg' => $e->getResponse(), 'trace' => $e->getTrace()];
            }

            // Add Request Token to JSON answer and return
            $arrResponse['rt'] = $this->csrfTokenManager->getToken($this->csrfTokenName)->getValue();
            echo json_encode($arrResponse);
            exit;
        }

        if ($this->portfolio_applicationForm
            && '' !== $objSession->get('wem_portfolio')
        ) {
            $strForm = $this->getApplicationForm($objSession->get('wem_portfolio'));

            // Fetch the application form if defined
            if (Input::post('FORM_SUBMIT')) {
                try {
                    $this->Template->openModalOnLoad = true;
                    $this->Template->openModalOnLoadContent = json_encode($strForm);
                } catch (\Exception $e) {
                    $this->Template->openModalOnLoad = true;
                    $this->Template->openModalOnLoadContent = json_encode('"' . $e->getResponse() . '"');
                }
            }
        }

        global $objPage;
        $this->limit = null;
        $this->offset = (int)$this->skipFirst;

        // Maximum number of items
        if ($this->numberOfItems > 0) {
            $this->limit = $this->numberOfItems;
        }

        $this->Template->articles = [];
        $this->Template->empty = $GLOBALS['TL_LANG']['WEM']['OFFERS']['empty'];

        // assets
        $strVersion = $this->getCustomPackageVersion('webexmachina/contao-portfolios');
        $objCssCombiner = new Combiner();
        $objCssCombiner->add('bundles/portfolios/css/styles.scss', $strVersion);

        $GLOBALS['TL_HEAD'][] = sprintf('<link rel="stylesheet" href="%s">', $objCssCombiner->getCombinedFile());
        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/portfolios/js/scripts.js';

        // Add pids
        $this->config = ['pid' => $this->portfolio_feeds, 'published' => 1];

        // Retrieve filters
        $this->buildFilters();
        $this->Template->filters = $this->filters;

        // Get the total number of items
        $intTotal = PortfolioModel::countItems($this->config);

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

        $objArticles = PortfolioModel::findItems($this->config, ($this->limit ?: 0), ($this->offset ?: 0));

        // Add the articles
        if ($objArticles instanceof \Contao\Model\Collection) {
            $this->Template->articles = $this->parsePortfolios($objArticles);
        }

        $this->Template->moduleId = $this->id;

        // Catch auto_item
        if (Input::get('auto_item')) {
            $objPortfolio = PortfolioModel::findItems(['code' => Input::get('auto_item')], 1);

            $this->Template->openModalOnLoad = true;
            $this->Template->portfolioId = $objPortfolio->first()->id;
        }
    }

    /**
     * Parse and return an application form for a job.
     *
     * @param int $intId [Job ID]
     * @param string $strTemplate [Template name]
     */
    protected function getApplicationForm(int $intId, string $strTemplate = 'portfolio_apply'): string
    {
        if (!$this->portfolio_applicationForm) {
            return '';
        }

        $strForm = $this->getForm($this->portfolio_applicationForm);

        $objItem = PortfolioModel::findByPk($intId);

        $objTemplate = new FrontendTemplate($strTemplate);
        $objTemplate->id = $objItem->id;
        $objTemplate->code = $objItem->code;
        $objTemplate->title = $objItem->title;
        $objTemplate->recipient = $GLOBALS['TL_ADMIN_EMAIL'];
        $objTemplate->time = time();
        $objTemplate->token = $this->csrfTokenManager->getToken($this->csrfTokenName)->getValue();
        $objTemplate->form = $strForm;

        return $objTemplate->parse();
    }

    /**
     * Retrieve list filters.
     *
     * @throws \Exception
     */
    protected function buildFilters(): void
    {
        if (!$this->portfolio_addFilters) {
            exit();
        }

        // Retrieve and format dropdowns filters
        $filters = StringUtil::deserialize($this->portfolio_filters);
        if (\is_array($filters) && $filters !== []) {
            foreach ($filters as $f) {
                $field = $GLOBALS['TL_DCA']['tl_wem_portfolio']['fields'][$f];

                $filter = [
                    'type' => $field['inputType'],
                    'name' => $field['eval']['multiple'] ? $f . '[]' : $f,
                    'label' => $field['label'][0] ?: $GLOBALS['TL_LANG']['tl_wem_portfolio'][$f][0],
                    'value' => Input::get($f) ?: '',
                    'options' => [],
                    'multiple' => (bool)$field['eval']['multiple'],
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
                                        'selected' => (null !== Input::get($f) && (Input::get($f) === $subValue || (\is_array(Input::get($f)) && \in_array($subValue, Input::get($f), true)))),
                                    ];
                                }
                            } else {
                                $filter['options'][] = [
                                    'value' => $value,
                                    'label' => $label,
                                    'selected' => (null !== Input::get($f) && (Input::get($f) === $value || (\is_array(Input::get($f)) && \in_array($value, Input::get($f), true)))),
                                ];
                            }
                        }

                        break;

                    case 'listWizard':
                        $objOptions = PortfolioModel::findItemsGroupByOneField($f);

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
                                        'selected' => $filter['multiple']
                                            ? (null !== Input::get($f) && \in_array($subOption, Input::get($f ?? []), true))
                                            : (null !== Input::get($f) && Input::get($f) === $subOption),
                                    ];
                                }
                            }
                        }

                        break;

                    case 'text':
                    default:
                        $objOptions = PortfolioModel::findItemsGroupByOneField($f);

                        if ($objOptions && 0 < $objOptions->count()) {
                            $filter['type'] = 'select';
                            while ($objOptions->next()) {
                                if (!$objOptions->{$f}) {
                                    continue;
                                }

                                $filter['options'][] = [
                                    'value' => $objOptions->{$f},
                                    'label' => $objOptions->{$f},
                                    'selected' => (null !== Input::get($f) && Input::get($f) === $objOptions->{$f}),
                                ];
                            }
                        }

                        break;
                }

                if ('select' === $filter['type'] && 1 >= \count($filter['options'])) {
                    continue;
                }

                if (null !== Input::get($f) && '' !== Input::get($f)) {
                    $this->config[$f] = Input::get($f);
                }

                $this->filters[] = $filter;
            }
        }

        // Add fulltext search if asked
        if ($this->portfolio_addSearch) {
            $this->filters[] = [
                'type' => 'text',
                'name' => 'search',
                'label' => $GLOBALS['TL_LANG']['WEM']['OFFERS']['search'],
                'placeholder' => $GLOBALS['TL_LANG']['WEM']['OFFERS']['searchPlaceholder'],
                'value' => Input::get('search') ?: '',
            ];

            if ('' !== Input::get('search') && null !== Input::get('search')) {
                $this->config['search'] = StringUtil::formatKeywords(Input::get('search'));
            }
        }
    }

    /**
     * Display a wildcard in the back end.
     */
    public function generate(): string
    {
        if (TL_MODE === 'BE') {
            $objTemplate = new BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### ' . strtoupper($GLOBALS['TL_LANG']['FMD']['portfolioslist'][0]) . ' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

            return $objTemplate->parse();
        }

        // Load datacontainer and job feeds
        $this->loadDatacontainer('tl_wem_portfolio');
        $this->loadLanguageFile('tl_wem_portfolio');
        $this->portfolio_feeds = StringUtil::deserialize($this->portfolio_feeds);

        // Return if there are no archives
        if (empty($this->portfolio_feeds) || !\is_array($this->portfolio_feeds)) {
            return '';
        }

        return parent::generate();
    }
}
