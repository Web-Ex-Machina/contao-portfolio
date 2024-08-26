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
use Contao\Model\Collection;
use Contao\Pagination;
use Contao\System;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use WEM\PortfolioBundle\Model\Portfolio;
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
        $this->catchAjaxRequests();

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

        $this->Template->items = [];
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
        if (!empty($_GET) || !empty($_POST)) {
            foreach ($_GET as $f => $v) {
                if (false === strpos($f, 'offer_filter_')) {
                    continue;
                }

                if (Input::get($f)) {
                    $this->config[str_replace('offer_filter_', '', $f)] = Input::get($f);
                }
            }

            foreach ($_POST as $f => $v) {
                if (false === strpos($f, 'offer_filter_')) {
                    continue;
                }

                if (Input::post($f)) {
                    $this->config[str_replace('offer_filter_', '', $f)] = Input::post($f);
                }
            }
        }

        // Retrieve filters
        if ($this->offer_addFilters) {
            $this->Template->filters = $this->getFrontendModule($this->offer_filters_module);
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
            $objPortfolio = Portfolio::findItems(['code' => Input::get('auto_item')], 1);

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

        $objItem = Portfolio::findByPk($intId);

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
     * Display a wildcard in the back end.
     */
    public function generate(): string
    {
        $scopeMatcher = System::getContainer()->get('wem.scope_matcher');
        if ($scopeMatcher->isBackend()) {
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
