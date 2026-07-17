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
use Contao\Controller;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\FilesModel;
use Contao\Frontend;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\Model\Collection;
use Contao\Module;
use Contao\System;
use Terminal42\ChangeLanguage\PageFinder;
use WEM\PortfolioBundle\Model\Content;
use WEM\PortfolioBundle\Model\Portfolio;
use WEM\PortfolioBundle\Model\PortfolioFeed;
use WEM\PortfolioBundle\Service\PortfolioService;
use WEM\UtilsBundle\Classes\StringUtil;

/**
 * Common functions for job portfolios modules.
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */
abstract class ModuleController extends AbstractFrontendModuleController
{
    protected PortfolioService $service;

    public function __construct() 
    {
        $this->service = System::getContainer()->get('wem.portfolio.service.portfolio');
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
            // remote
            if(is_array($objItem->singleSRC)) {
                $file = $objItem->singleSRC;
            }
            // local
            else {
                $objFile = FilesModel::findByUuid($objItem->singleSRC);
                $file = $objFile->row();
            }

            $figure = System::getContainer()
                ->get('contao.image.studio')
                ->createFigureBuilder()
                ->fromPath($file['path'])
                ->setSize($objItem->size)
                ->enableLightbox((bool) $objItem->fullsize)
                ->buildIfResourceExists()
            ;

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
            $id = $objItem->id;
            $objTemplate->text = function () use ($id): string {
                $strText = '';
                $objElement = Content::findPublishedByPidAndTableAndLanguage($id, 'tl_wem_portfolio');

                if (null !== $objElement) {
                    while ($objElement->next()) {
                        $strText .= Controller::getContentElement($objElement->current());
                    }
                }

                return $strText;
            };
            $objTemplate->hasText = static fn (): bool => ContentModel::countPublishedByPidAndTable($objItem->id, 'tl_wem_portfolio') > 0;
        }

        return $objTemplate->parse();
    }

    /**
     * Find items from remote
     * 
     * @var array config
     * @var PortfolioFeed feed
     *
     * @return Collection
     * 
     * @throws \Exception
     */
    protected function findRemoteItems(array $config, PortfolioFeed $feed, int $page, int $limit, int $offset, ?string $order): Collection
    {
        $ch = curl_init();
        $params = $this->formatConfigForRemote($config, $feed);
        $url = $feed->readFromRemoteUrl . '/api/portfolio/items/' . $page . '/' . $limit . '/' . $offset;

        if ($order) {
            $url .= '/' . urlencode($order);
        }

        $url .= '?' . $params;

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $request = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($request, true);

        // We need to format a Collection of Portfolio
        $items = [];
        foreach ($data as $item) {
            unset($item['category']);
            $objModel = new Portfolio();
            $objModel->setRow($item);
            $objModel->pid = $feed->id;
            $items[] = $objModel;
        }

        $objCollection = new Collection($items, 'tl_wem_portfolio');

        return $objCollection;
    }

    /**
     * Count items from remote
     * 
     * @var array config
     * @var PortfolioFeed feed
     *
     * @return Portfolio
     * 
     * @throws \Exception
     */
    protected function countRemoteItems(array $config, PortfolioFeed $feed): int
    {
        $ch = curl_init();
        
        $params = $this->formatConfigForRemote($config, $feed);
        $url = $feed->readFromRemoteUrl . '/api/portfolio/count?' . $params;

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $request = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($request, true);

        return (int) $data['items'];
    }

    /**
     * Find a specific item from remote
     * 
     * @var mixed item (can be int or string)
     * @var PortfolioFeed feed
     *
     * @return Portfolio
     * 
     * @throws \Exception
     */
    protected function findRemoteItem(mixed $item, PortfolioFeed $feed): ?Portfolio
    {
        $ch = curl_init();
        $params = $this->formatConfigForRemote([], $feed);
        $url = $feed->readFromRemoteUrl . '/api/portfolio/item/' . $item . '?' . $params;

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $request = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($request, true);

        unset($data['category']);

        if (!$data || empty($data)) {
            return null;
        }

        $objModel = new Portfolio();
        $objModel->setRow($data);
        $objModel->remotePid = $objModel->pid;
        $objModel->pid = $feed->id;

        return $objModel;
    }

    protected function formatConfigForRemote(array $config, PortfolioFeed $feed): string
    {
        $params = $config;

        // Unset some default config settings
        if (array_key_exists('pid', $config)) {
            unset($params['pid']);
        }

        $feedParams = deserialize($feed->readFromRemoteConfig);
        if (is_iterable($feedParams)) {
            foreach ($feedParams as $c) {
                switch ($c['key']) {
                    case 'pid':
                        $params['pid'][] = $c['value'];
                    break;

                    default:
                        $params[$c['key']] = $c['value'];
                }
            }
        }

        $params['key'] = System::getContainer()->get('wem.encryption_util')->decrypt_b64($feed->readFromRemoteApiKey);

        if (!array_key_exists("lang", $params)) {
            $params['lang'] = $GLOBALS["TL_LANGUAGE"];
        }

        return http_build_query($params);
    }
}
