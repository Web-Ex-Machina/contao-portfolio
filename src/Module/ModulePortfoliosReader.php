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
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Environment;
use Contao\Input;
use Contao\PageModel;
use Contao\System;
use WEM\PortfolioBundle\Model\Portfolio;
use WEM\UtilsBundle\Classes\StringUtil;

/**
 * Front end module "portfolios list".
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */
class ModulePortfoliosReader extends ModulePortfolios
{
    protected ?Portfolio $objPortfolio = null;

    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'mod_wem_portfolio_reader';

    /**
     * Display a wildcard in the back end.
     */
    public function generate(): string
    {
        $scopeMatcher = System::getContainer()->get('wem.scope_matcher');
        if ($scopeMatcher->isBackend()) {
            $objTemplate = new BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### '.strtoupper($GLOBALS['TL_LANG']['FMD']['wem_portfolio_feed_reader'][0]).' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id='.$this->id;

            return $objTemplate->parse();
        }

        $this->portfolio = Portfolio::findByIdOrSlug(Input::get('auto_item'));

        if (!$this->portfolio) {
            throw new PageNotFoundException('Page not found: '.Environment::get('uri'));
        }

        return parent::generate();
    }

    /**
     * Generate the module.
     */
    protected function compile(): void
    {
        if ($this->overviewPage) {
            $this->Template->referer = PageModel::findById($this->overviewPage)->getFrontendUrl();
            $this->Template->back = $this->customLabel ?: $GLOBALS['TL_LANG']['MSC']['newsOverview'];
        }

        // Catch Ajax requets
        $this->catchAjaxRequests();

        global $objPage;

        $objPage->pageTitle = $this->portfolio->title.' | '.$this->portfolio->slug;
        $objPage->description = StringUtil::substr($this->portfolio->teaser, 300);

        // Add the articles
        $this->Template->portfolio = $this->parsePortfolio($this->portfolio);
        $this->Template->moduleId = $this->id;
    }
}
