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

use Contao\BackendTemplate;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Environment;
use Contao\Input;
use Contao\PageModel;
use Contao\ModuleModel;
use Contao\System;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WEM\PortfolioBundle\Model\Portfolio;
use WEM\PortfolioBundle\Model\PortfolioFeed;
use WEM\PortfolioBundle\Model\PortfolioL10n;
use WEM\UtilsBundle\Classes\StringUtil;

#[AsFrontendModule(
    ReaderModuleController::TYPE, 
    category: 'wem_portfolio',
    template: 'mod_wem_portfolio_reader'
)]
class ReaderModuleController extends ModuleController
{
    public const TYPE = 'wem_portfolio_reader';

    protected ?Portfolio $portfolio = null;
    protected ?PortfolioFeed $feed = null;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Generate the module.
     */
    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        if ((!Input::get('category') || !Input::get('item')) && Input::get('auto_item')) {
            $objItem = Portfolio::findByIdOrSlug(Input::get('auto_item'));

            if (!$objItem) {
                $objL10nItem = PortfolioL10n::findByIdOrSlug(Input::get('auto_item'));

                if (!$objL10nItem) {
                    throw new PageNotFoundException('Page not found: '.Environment::get('uri'));
                }

                $objItem = $objL10nItem->getRelated('pid');
            }

            global $objPage;
            $this->redirect($objPage->getFrontendUrl('/category/'.$objItem->getRelated('pid')->alias.'/item/'.Input::get('auto_item')), 301);
            exit;
        }

        $this->feed = PortfolioFeed::findByIdOrAlias(Input::get('category'));

        if ($this->feed->readFromRemote) {
            $this->portfolio = $this->findRemoteItem(Input::get('item'), $this->feed);
        } else {
            $this->portfolio = Portfolio::findByIdOrSlug(Input::get('item'));

            if (!$this->portfolio) {
                $objL10nItem = PortfolioL10n::findByIdOrSlug(Input::get('item'));

                if (!$objL10nItem) {
                    throw new PageNotFoundException('Page not found: '.Environment::get('uri'));
                }

                $this->portfolio = $objL10nItem->getRelated('pid');
            }
        }

        if (!$this->portfolio) {
            throw new PageNotFoundException('Page not found: '.Environment::get('uri'));
        }

        if ($this->overviewPage) {
            $template->referer = PageModel::findById($this->overviewPage)->getFrontendUrl();
            $template->back = $this->customLabel ?: $GLOBALS['TL_LANG']['MSC']['newsOverview'];
        }

        // Catch Ajax requets
        $this->catchAjaxRequests();

        global $objPage;

        $objPage->pageTitle = $this->portfolio->title.' | '.$this->portfolio->slug;
        $objPage->description = StringUtil::substr($this->portfolio->teaser, 300);

        // Add the articles
        $template->portfolio = $this->parsePortfolio($this->portfolio);
        $template->moduleId = $this->id;

        return $template->getResponse();
    }
}
