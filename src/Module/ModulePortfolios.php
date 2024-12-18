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

use Contao\Config;
use Contao\ContentModel;
use Contao\FilesModel;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\Model\Collection;
use Contao\Module;
use Contao\System;
use Terminal42\ChangeLanguage\PageFinder;
use WEM\PortfolioBundle\Model\Content;
use WEM\PortfolioBundle\Model\Portfolio;
use WEM\PortfolioBundle\Model\PortfolioFeed;
use WEM\UtilsBundle\Classes\StringUtil;

/**
 * Common functions for job portfolios modules.
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */
abstract class ModulePortfolios extends Module
{
    protected function catchAjaxRequests(): void
    {
        if (Input::post('TL_AJAX') && (int) $this->id === (int) Input::post('module')) {
            try {
                switch (Input::post('action')) {
                    case 'seeDetails':
                        if (!Input::post('portfolio')) {
                            throw new \Exception(\sprintf($GLOBALS['TL_LANG']['WEM']['PORTFOLIO']['ERROR']['argumentMissing'], 'portfolio'));
                        }

                        $objItem = Portfolio::findByPk(Input::post('portfolio'));

                        $this->wem_portfolio_template = 'portfolio_details';
                        echo System::getContainer()->get('contao.insert_tag.parser')->replace($this->parsePortfolio($objItem));
                        exit;
                    default:
                        throw new \Exception(\sprintf($GLOBALS['TL_LANG']['WEM']['PORTFOLIO']['ERROR']['unknownRequest'], Input::post('action')));
                }
            } catch (\Exception $e) {
                $arrResponse = ['status' => 'error', 'msg' => $e->getResponse(), 'trace' => $e->getTrace()];
            }

            // Add Request Token to JSON answer and return
            $arrResponse['rt'] = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();
            echo json_encode($arrResponse);
            exit;
        }
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
        $objTemplate = new FrontendTemplate($this->wem_portfolio_template);
        $objTemplate->setData($objItem->row());

        $objTemplate->title = $objItem->getL10nLabel('title');

        if ('' !== $objItem->cssClass) {
            $strClass = ' '.$objItem->cssClass.$strClass;
        }

        $objTemplate->model = $objItem;
        $objTemplate->class = $strClass;
        $objTemplate->count = $intCount; // see #5708

        // Add the meta information
        $objTemplate->date = (int) $objItem->date;
        $objTemplate->timestamp = $objItem->date;
        $objTemplate->datetime = date('Y-m-d\TH:i:sP', (int) $objItem->date);

        // Add an image
        if ($objItem->singleSRC) {
            $figure = System::getContainer()
                ->get('contao.image.studio')
                ->createFigureBuilder()
                ->from($objItem->singleSRC)
                ->setSize($objItem->size)
                ->enableLightbox((bool) $objItem->fullsize)
                ->buildIfResourceExists()
            ;

            if (null !== $figure) {
                $figure->applyLegacyTemplateData($objTemplate, $objItem->imagemargin, $objItem->floating);
            }
        }

        // Retrieve item pictures
        if ($objItem->pictures = StringUtil::deserialize($objItem->pictures)) {
            $objFiles = FilesModel::findMultipleByUuids($objItem->pictures);
            $images = [];
            while ($objFiles->next()) {
                $images[$objFiles->path] = [
                    'id' => $objFiles->id,
                    'uuid' => $objFiles->uuid,
                    'name' => $objFiles->basename,
                    'singleSRC' => $objFiles->path,
                    'filesModel' => $objFiles->current(),
                ];
            }

            if ('' !== $objItem->orderPictures) {
                $t = StringUtil::deserialize($objItem->orderPictures);
                if (!empty($t) && \is_array($t)) {
                    // Remove all values
                    $arrOrder = array_map(function (): void {
                    }, array_flip($t));

                    // Move the matching elements to their position in $arrOrder
                    foreach ($images as $k => $v) {
                        if (\array_key_exists($v['uuid'], $arrOrder)) {
                            $arrOrder[$v['uuid']] = $v;
                            unset($images[$k]);
                        }
                    }

                    // Append the left-over images at the end
                    if ([] !== $images) {
                        $arrOrder = array_merge($arrOrder, array_values($images));
                    }

                    // Remove empty (unreplaced) entries
                    $images = array_values(array_filter($arrOrder));
                    unset($arrOrder);
                }
            }

            $objTemplate->pictures = $images;
        }

        // Retrieve item teaser
        if ($objItem->teaser) {
            $objTemplate->hasTeaser = true;
            $objTemplate->teaser = StringUtil::encodeEmail($objItem->getL10nLabel('teaser'));
        }

        // Retrieve item content
        $id = $objItem->id;
        $objTemplate->text = function () use ($id): string {
            $strText = '';
            $objElement = Content::findPublishedByPidAndTableAndLanguage($id, 'tl_wem_portfolio');

            if (null !== $objElement) {
                while ($objElement->next()) {
                    $strText .= $this->getContentElement($objElement->current());
                }
            }

            return $strText;
        };
        $objTemplate->hasText = static fn (): bool => ContentModel::countPublishedByPidAndTable($objItem->id, 'tl_wem_portfolio') > 0;

        // Retrieve item attributes
        $objTemplate->blnDisplayAttributes = (bool) $this->wem_portfolio_displayAttributes;
        if ((bool) $this->wem_portfolio_displayAttributes && null !== $this->wem_portfolio_attributes) {
            $objTemplate->attributes = $objItem->getAttributesFull(StringUtil::deserialize($this->wem_portfolio_attributes));
        }

        // Notice the template if we want to display the text
        if ($this->wem_portfolio_displayTeaser) {
            $objTemplate->blnDisplayText = true;
        } else {
            $objTemplate->detailsUrl = $this->addToUrl('seeDetails='.$objItem->id, true, ['portfolio']);
        }

        // Parse the URL if we have a jumpTo configured
        $objTemplate->jumpTo = $objItem->getUrl();

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
    protected function findRemoteItems($config, $feed, $limit, $offset, $options): Collection
    {
        $ch = curl_init();
        $params = $config;
        $params['key'] = System::getContainer()->get('wem.encryption_util')->decrypt_b64($feed->readFromRemoteApiKey);
        $url = $feed->readFromRemoteUrl . '/api/portfolio/items?' . http_build_query($params);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $request = curl_exec($ch);
        curl_close($ch);

        dump($request); die;
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
    protected function countRemoteItems($config, $feed): int
    {
        $ch = curl_init();
        
        $params = $this->formatConfigForRemote($config, $feed);
        $url = $feed->readFromRemoteUrl . '/api/portfolio/count?' . $params;

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $request = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($request, true);

        return $data['items'];
    }

    /**
     * Find a specific item from remote
     * 
     * @var mixed item
     * @var PortfolioFeed feed
     *
     * @return Portfolio
     * 
     * @throws \Exception
     */
    protected function findRemoteItem($item, $feed): Portfolio
    {
        $ch = curl_init();
        $params = $config;
        $params['key'] = System::getContainer()->get('wem.encryption_util')->decrypt_b64($feed->readFromRemoteApiKey);
        $url = $feed->readFromRemoteUrl . '/api/portfolio/item/' . $item;

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $request = curl_exec($ch);
        curl_close($ch);

        dump($request); die;
    }

    protected function formatConfigForRemote($config, $feed): string
    {
        $params = $config;

        // Unset some default config settings
        unset($params['pid']);

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

        return http_build_query($params);
    }

    /**
     * Transform a JSON object into a Portfolio Model
     * Useful for API calls
     * 
     * @var string $json
     * 
     * @return WEM\PortfolioBundle\Model\Portfolio
     * 
     * @throws \Exception
     */
    protected function jsonToModel(string $json): Portfolio
    {
        $data = json_decode($json, true);

        if (empty($data)) {
            throw new \Exception("JSON data is empty");
        }

        $objModel = new Portfolio();

        foreach ($data as $c => $v) {
            $objModel->{$c} = $v;
        }

        return $objModel;
    }
}
