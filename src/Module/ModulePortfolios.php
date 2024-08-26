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
                        if (!Input::post('offer')) {
                            throw new \Exception(sprintf($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['argumentMissing'], 'offer'));
                        }
                        $objItem = Portfolio::findByPk(Input::post('offer'));

                        $this->offer_template = 'offer_details';
                        echo System::getContainer()->get('contao.insert_tag')->replace($this->parsePortfolio($objItem));
                        exit;
                        break;
                    default:
                        throw new \Exception(sprintf($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['unknownRequest'], Input::post('action')));
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
        $objTemplate = new FrontendTemplate($this->portfolio_template);
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
        if ($objItem->addImage) {
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

        // Retrieve item teaser
        if ($objItem->teaser) {
            $objTemplate->hasTeaser = true;
            $objTemplate->teaser = StringUtil::encodeEmail($objItem->teaser);
        }

        // Retrieve item content
        $id = $objItem->id;

        $objTemplate->text = function () use ($id) {
            $strText = '';
            $objElement = ContentModel::findPublishedByPidAndTable($id, 'tl_wem_portfolio');

            if ($objElement !== null) {
                while ($objElement->next()) {
                    $strText .= $this->getContentElement($objElement->current());
                }
            }

            return $strText;
        };

        $objTemplate->hasText = static fn() => ContentModel::countPublishedByPidAndTable($objItem->id, 'tl_wem_portfolio') > 0;

        // Retrieve item attributes
        $objTemplate->blnDisplayAttributes = (bool)$this->portfolio_displayAttributes;

        if ((bool)$this->portfolio_displayAttributes && null !== $this->portfolio_attributes) {
            $objTemplate->attributes = $objItem->getAttributesFull(StringUtil::deserialize($this->portfolio_attributes));
        }

        // Notice the template if we want/can display apply button
        if ($this->blnDisplayApplyButton) {
            $objTemplate->blnDisplayApplyButton = true;
            $objTemplate->applyUrl = $this->addToUrl('apply=' . $objItem->id, true, ['portfolio']);
        }

        // Notice the template if we want to display the text
        if ($this->portfolio_displayTeaser) {
            $objTemplate->blnDisplayText = true;
        } else {
            $objTemplate->detailsUrl = $this->addToUrl('seeDetails=' . $objItem->id, true, ['portfolio']);
        }

        // Parse the URL if we have a jumpTo configured
        if ($objTarget = $objItem->getRelated('pid')->getRelated('jumpTo')) {
            $params = (Config::get('useAutoItem') ? '/' : '/items/') . ($objItem->code ?: $objItem->id);
            $objTemplate->jumpTo = $objTarget->getFrontendUrl($params);
        }

        return $objTemplate->parse();
    }

    /**
     * Get a package's version.
     *
     * @param string $package The package name
     *
     * @return string|null The package version if found, null otherwise
     */
    protected function getCustomPackageVersion(string $package): ?string
    {
        $packages = json_decode(file_get_contents('./../../vendor/composer/installed.json'));

        foreach ($packages->packages as $p) {
            $p = (array)$p;
            if ($package === $p['name']) {
                return $p['version'];
            }
        }

        return null;
    }
}
