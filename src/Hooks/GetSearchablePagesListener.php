<?php

declare(strict_types=1);

/**
 * Contao Portfolio for Contao Open Source CMS
 * Copyright (c) 2015-2023 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-portfolio
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-portfolio/
 */

namespace WEM\PortfolioBundle\Hooks;

use WEM\PortfolioBundle\Model\Item as PItemModel;

class GetSearchablePagesListener
{
    /**
     * @Hook("getSearchablePages")
     */
    public function onGetSearchablePages(array $pages, int $root = null, bool $isSitemap = false, string $language = null): array
    {
        // Find all the pages with the portfolio reader as content
        $objPages = \Database::getInstance()->prepare("
            SELECT tp.id 
            FROM tl_content AS tc
            LEFT JOIN tl_article AS ta ON tc.pid = ta.id
            LEFT JOIN tl_page AS tp ON ta.pid = tp.id
            WHERE tc.type = 'module'
            AND tc.module IN (
                SELECT tm.id 
                FROM tl_module AS tm
                WHERE tm.type = 'wem_portfolio_reader'
            )
        ")->execute();

        // Return if there is no pages
        if (!$objPages || 0 === $objPages->count()) {
            return $pages;
        }

        // Retrieve all the portfolio items
        $objItems = PItemModel::findItems();

        // Return if there is no items
        if (!$objItems || 0 === $objItems->count()) {
            return $pages;
        }

        // Cache the pages models
        $arrPages = [];
        while ($objPages->next()) {
            $p = \PageModel::findByPk($objPages->id);

            if (!\array_key_exists($p->language, $arrPages)) {
                $arrPages[$p->language] = [];
            }
            $p->forceRowLanguage = true;
            $arrPages[$p->language][] = $p->loadDetails();
        }

        while ($objItems->next()) {
            foreach ($arrPages as $l => $arrPage) {
                foreach ($arrPage as $k => $pl) {
                    $pages[] = \Environment::get('base').$pl->getFrontendUrl('/'.$objItems->alias, $l);
                }
            }
        }

        return $pages;
    }
}
