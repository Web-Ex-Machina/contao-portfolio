<?php

declare(strict_types=1);

/**
 * Contao Portfolio for Contao Open Source CMS
 * Copyright (c) 2015-2020 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-portfolio
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-portfolio/
 */

namespace WEM\Portfolio\Hooks;

use WEM\Portfolio\Model\Item as PItemModel;

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

            $bundles = \System::getContainer()->getParameter('kernel.bundles');
            if (\array_key_exists('VerstaerkerI18nl10nBundle', $bundles)) {
                $objInstance = \Verstaerker\I18nl10nBundle\Classes\I18nl10n::getInstance();
                $objTranslations = \Database::getInstance()->prepare('
                    SELECT tpi.*
                    FROM tl_page_i18nl10n AS tpi
                    LEFT JOIN tl_page AS tp ON tpi.pid = tp.id
                    WHERE tp.id = ?
                ')->execute($p->id);

                if (!$objTranslations || 0 === $objTranslations->count()) {
                    continue;
                }

                while ($objTranslations->next()) {
                    if (!\array_key_exists($objTranslations->language, $arrPages)) {
                        $arrPages[$objTranslations->language] = [];
                    }

                    $pl = $objInstance->findL10nWithDetails($p->id, $objTranslations->language);
                    $pl->forceRowLanguage = true;

                    $arrPages[$objTranslations->language][] = $pl;
                }
            }
        }

        while ($objItems->next()) {
            foreach ($arrPages as $l => $arrPage) {
                foreach ($arrPage as $k => $pl) {
                    if ($objItems->i18nl10n_lang && $l !== $objItems->i18nl10n_lang) {
                        continue;
                    }

                    $pages[] = \Environment::get('base').$pl->getFrontendUrl('/'.$objItems->alias, $l);
                }
            }
        }

        return $pages;
    }
}
