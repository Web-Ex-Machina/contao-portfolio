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

use Contao\CoreBundle\ServiceAnnotation\Hook;
use WEM\PortfolioBundle\Model\Item;

class GetSearchablePagesListener
{
    /**
     * @Hook("getSearchablePages")
     * @throws \Exception
     */
    public function onGetSearchablePages(array $pages, int $root = null, bool $isSitemap = false, string $language = null): array
    {
        // Retrieve all published items
        $objItems = Item::findItems(['published' => 1]);

        if (!$objItems || 0 === $objItems->count()) {
            return $pages;
        }

        while ($objItems->next()) {
            $pages[] = $objItems->current()->getUrl(true);
        }

        return $pages;
    }
}
