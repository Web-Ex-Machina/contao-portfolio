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
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\ContaoPageSchema;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;
use Contao\CoreBundle\Util\UrlUtil;
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
    protected ModuleModel $model;

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

        $this->model = $model;
        $this->feed = PortfolioFeed::findByIdOrAlias(Input::get('category'));

        $this->portfolio = $this->findItem();

        if (!$this->portfolio) {
            throw new PageNotFoundException('Page not found: '.Environment::get('uri'));
        }

        if ($this->model->overviewPage && ($overviewPage = PageModel::findById($this->model->overviewPage))) {
            $template->referer = $this->contentUrlGenerator->generate($overviewPage);
            $template->back = $this->model->customLabel ?: $GLOBALS['TL_LANG']['MSC']['newsOverview'];
        }

        // Overwrite the page metadata
        $responseContext = System::getContainer()->get('contao.routing.response_context_accessor')->getResponseContext();

        if ($responseContext?->has(HtmlHeadBag::class)) {
            $htmlHeadBag = $responseContext->get(HtmlHeadBag::class);
            $htmlDecoder = System::getContainer()->get('contao.string.html_decoder');

            if ($this->portfolio->pageTitle) {
                $htmlHeadBag->setTitle($this->portfolio->pageTitle);
            } elseif ($this->portfolio->title) {
                $htmlHeadBag->setTitle($this->portfolio->title);
            }

            if ($this->portfolio->description) {
                $htmlHeadBag->setMetaDescription($htmlDecoder->inputEncodedToPlainText($this->portfolio->description));
            } elseif ($this->portfolio->teaser) {
                $htmlHeadBag->setMetaDescription($htmlDecoder->htmlToPlainText($this->portfolio->teaser));
            }

            if ($this->portfolio->robots) {
                $htmlHeadBag->setMetaRobots($this->portfolio->robots);
            }

            if ($this->portfolio->canonicalLink) {
                $url = System::getContainer()->get('contao.insert_tag.parser')->replaceInline($this->portfolio->canonicalLink);

                // Ensure absolute links
                if (!preg_match('#^https?://#', $url)) {
                    if (!$request = System::getContainer()->get('request_stack')->getCurrentRequest()) {
                        throw new \RuntimeException('The request stack did not contain a request');
                    }

                    $url = UrlUtil::makeAbsolute($url, $request->getUri());
                }

                $htmlHeadBag->setCanonicalUri($url);
            }
        }

        // Update the JSON+LD "searchIndexer" setting
        $pageSchema = $responseContext->get(JsonLdManager::class)->getGraphForSchema(JsonLdManager::SCHEMA_CONTAO)->get(ContaoPageSchema::class);

        if ($this->portfolio->searchIndexer) {
            $pageSchema['searchIndexer'] = $this->portfolio->searchIndexer;
        }

        // Add the articles
        $template->portfolio = $this->parsePortfolio($this->portfolio);
        $template->moduleId = $this->model->id;

        return $template->getResponse();
    }

    protected function findItem(): ?Portfolio
    {
        $portfolio = Portfolio::findByIdOrSlug(Input::get('item'));

        if ($portfolio) {
            return $portfolio;
        }

        $l10n = PortfolioL10n::findByIdOrSlug(Input::get('item'));

        if ($l10n) {
            return $l10n->getRelated('pid');
        }

        return null;
    }
}
