<?php

declare(strict_types=1);

/**
 * Contao Job Portfolios for Contao Open Source CMS
 * Copyright (c) 2018-2020 Web ex Machina.
 *
 * @category ContaoBundle
 *
 * @author   Web ex Machina <contact@webexmachina.fr>
 *
 * @see     https://github.com/Web-Ex-Machina/contao-job-portfolios/
 */

namespace WEM\PortfolioBundle\Module;

use Codefog\HasteBundle\Util\InsertTag;
use Contao\Config;
use Contao\ContentModel;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\Model\Collection;
use Contao\Module;
use Contao\RequestToken;
use Contao\System;
use WEM\PortfolioBundle\Model\Portfolio;
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
        if (Input::post('TL_AJAX') && (int)$this->id === (int)Input::post('module')) {
            try {
                switch (Input::post('action')) {
                    case 'seeDetails':
                        if (!Input::post('portfolio')) {
                            throw new \Exception(sprintf($GLOBALS['TL_LANG']['WEM']['PORTFOLIO']['ERROR']['argumentMissing'], 'portfolio'));
                        }

                        $objItem = Portfolio::findByPk(Input::post('portfolio'));

                        $this->wem_portfolio_template = 'portfolio_details';
                        echo System::getContainer()->get('contao.insert_tag')->replace($this->parsePortfolio($objItem));
                        exit;
                    default:
                        throw new \Exception(sprintf($GLOBALS['TL_LANG']['WEM']['PORTFOLIO']['ERROR']['unknownRequest'], Input::post('action')));
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

            $arrArticles[] = $this->parsePortfolio($objItem, $blnAddArchive, ((1 === ++$count) ? ' first' : '') . (($count === $limit) ? ' last' : '') . ((0 === ($count % 2)) ? ' odd' : ' even'), $count);
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

        if ('' !== $objItem->cssClass) {
            $strClass = ' ' . $objItem->cssClass . $strClass;
        }

        $objTemplate->model = $objItem;
        $objTemplate->class = $strClass;
        $objTemplate->count = $intCount; // see #5708

        // Add the meta information
        $objTemplate->date = (int)$objItem->date;
        $objTemplate->timestamp = $objItem->date;
        $objTemplate->datetime = date('Y-m-d\TH:i:sP', (int)$objItem->date);

        // Add an image
        if ($objItem->singleSRC) {
            $figure = System::getContainer()
                ->get('contao.image.studio')
                ->createFigureBuilder()
                ->from($objItem->singleSRC)
                ->setSize($objItem->size)
                ->enableLightbox((bool)$objItem->fullsize)
                ->buildIfResourceExists();

            if (null !== $figure) {
                $figure->applyLegacyTemplateData($objTemplate, $objItem->imagemargin, $objItem->floating);
            }
        }

        // Retrieve item pictures
        if ($objItem->pictures = \StringUtil::deserialize($objItem->pictures)) {
            $objFiles = \FilesModel::findMultipleByUuids($objItem->pictures);
            $images = [];
            while ($objFiles->next()) {
                $images[$objFiles->path] = [
                    'id' => $objFiles->id,
                    'uuid' => $objFiles->uuid,
                    'name' => $objFile->basename,
                    'singleSRC' => $objFiles->path,
                    'filesModel' => $objFiles->current(),
                ];
            }

            if ('' !== $objItem->orderPictures) {
                $t = \StringUtil::deserialize($objItem->orderPictures);
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
                    if (!empty($images)) {
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
            $objTemplate->teaser = StringUtil::encodeEmail($objItem->teaser);
        }

        // Retrieve item content
        $id = $objItem->id;
        $objTemplate->text = function () use ($id): string {
            $strText = '';
            $objElement = ContentModel::findPublishedByPidAndTable($id, 'tl_wem_portfolio');

            if ($objElement !== null) {
                while ($objElement->next()) {
                    $strText .= $this->getContentElement($objElement->current());
                }
            }

            return $strText;
        };
        $objTemplate->hasText = static fn(): bool => ContentModel::countPublishedByPidAndTable($objItem->id, 'tl_wem_portfolio') > 0;

        // Retrieve item attributes
        $objTemplate->blnDisplayAttributes = (bool)$this->wem_portfolio_displayAttributes;
        if ((bool)$this->wem_portfolio_displayAttributes && null !== $this->wem_portfolio_attributes) {
            $objTemplate->attributes = $objItem->getAttributesFull(StringUtil::deserialize($this->wem_portfolio_attributes));
        }

        // Notice the template if we want to display the text
        if ($this->wem_portfolio_displayTeaser) {
            $objTemplate->blnDisplayText = true;
        } else {
            $objTemplate->detailsUrl = $this->addToUrl('seeDetails=' . $objItem->id, true, ['portfolio']);
        }

        // Parse the URL if we have a jumpTo configured
        if ($objTarget = $objItem->getRelated('pid')->getRelated('jumpTo')) {
            $params = (Config::get('useAutoItem') ? '/' : '/items/') . ($objItem->slug ?: $objItem->id);
            $objTemplate->jumpTo = $objTarget->getFrontendUrl($params);
        }

        return $objTemplate->parse();
    }
}
