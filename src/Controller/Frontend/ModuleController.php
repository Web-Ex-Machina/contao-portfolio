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

use Contao\Config;
use Contao\ContentModel;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\Environment;
use Contao\FilesModel;
use Contao\Frontend;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\Model\Collection;
use Contao\Module;
use Contao\System;
use Terminal42\ChangeLanguage\PageFinder;
use Symfony\Component\HttpFoundation\RequestStack;
use WEM\PortfolioBundle\Model\Content;
use WEM\PortfolioBundle\Model\Portfolio;
use WEM\PortfolioBundle\Model\PortfolioFeed;
use WEM\PortfolioBundle\Service\PortfolioService;
use WEM\PortfolioBundle\Wrapper\PortfolioApi;
use WEM\UtilsBundle\Classes\Encryption;
use WEM\UtilsBundle\Classes\StringUtil;

/**
 * Common functions for job portfolios modules.
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */
abstract class ModuleController extends AbstractFrontendModuleController
{
    protected Encryption $encrypt;
    protected PortfolioService $service;
    protected RequestStack $request;

    public function __construct() {
        $this->encrypt = System::getContainer()->get('wem.encryption_util');
        $this->service = System::getContainer()->get('wem.portfolio.service.portfolio');
        $this->request = System::getContainer()->get('request_stack');
    }

    /**
     * Parse one or more items and return them as array.
     *
     * @throws \Exception
     */
    protected function parsePortfolios(Collection $objItems, bool $blnAddArchive = false): array
    {
        $limit = $objItems->count();

        if ($limit < 1) {
            return [];
        }

        $count = 0;
        $arrArticles = [];

        while ($objItems->next()) {
            $objItem = $objItems->current();

            $arrArticles[] = $this->parsePortfolio($objItem, $blnAddArchive, ((1 === ++$count) ? ' first' : '').(($count === $limit) ? ' last' : '').((0 === ($count % 2)) ? ' odd' : ' even'), $count);
        }

        return $arrArticles;
    }

    /**
     * Parse an item and return it as string.
     *
     * @throws \Exception
     */
    protected function parsePortfolio(Portfolio $objItem, bool $blnAddArchive = false, string $strClass = '', int $intCount = 0): string
    {
        $this->service->load($objItem);

        $objTemplate = new FrontendTemplate($this->model->wem_portfolio_template);
        $objTemplate->setData($this->service->getFields());

        if ('' !== $objItem->cssClass) {
            $strClass = ' '.$objItem->cssClass.$strClass;
        }

        $objTemplate->class = $strClass;
        $objTemplate->count = $intCount;

        // Add the meta information
        $objTemplate->date = (int) $objItem->date;
        $objTemplate->timestamp = $objItem->date;
        $objTemplate->datetime = date('c', (int) $objItem->date);

        // Retrieve item teaser
        if ($objItem->teaser) {
            $objTemplate->hasTeaser = true;
            $objTemplate->teaser = strip_tags($this->service->getField('teaser'));
        }

        // Parse the URL if we have a jumpTo configured
        if ($objItem->getRelated('pid')->jumpTo) {
            $objTemplate->jumpTo = $this->service->getUrl();
        }

        // Add an image
        if ($objItem->singleSRC) {
            $file = $this->service->getField('singleSRC');

            if ($file['fromApi']) {
                $figure = System::getContainer()
                    ->get('contao.image.studio')
                    ->createFigureBuilder()
                    ->fromUrl($file['path'])
                    ->setSize($objItem->size)
                    ->enableLightbox((bool) $objItem->fullsize)
                    ->buildIfResourceExists()
                ;
            } else {
               $figure = System::getContainer()
                    ->get('contao.image.studio')
                    ->createFigureBuilder()
                    ->fromPath($file['path'])
                    ->setSize($objItem->size)
                    ->enableLightbox((bool) $objItem->fullsize)
                    ->buildIfResourceExists()
                ;
            }

            if (null !== $figure) {
                $figure->applyLegacyTemplateData($objTemplate, $objItem->imagemargin, $objItem->floating);
            }

            // Send also the data for flexible behavior
            $objTemplate->singleSRC = $file;
        }

        // Retrieve item content
        if ($objItem->content_b64) {
            $objTemplate->text = base64_decode($objItem->content_b64);
            $objTemplate->hasText = true;
        } else {
            $objTemplate->text = $this->service->getContent();
            $objTemplate->hasText = static fn (): bool => ContentModel::countPublishedByPidAndTable($objItem->id, 'tl_wem_portfolio') > 0;
        }

        return $objTemplate->parse();
    }

    protected function getApi(): ?PortfolioApi
    {
        if (!$this->model) {
            return null;
        }

        if (!$this->model->wem_portfolio_remote_url || !$this->model->wem_portfolio_remote_apikey) {
            return null;
        }

        return new PortfolioApi(
            $this->encrypt->decrypt_b64($this->model->wem_portfolio_remote_url),
            $this->encrypt->decrypt_b64($this->model->wem_portfolio_remote_apikey),
        );
    }
}
