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

namespace WEM\PortfolioBundle\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\FilesModel;
use Contao\Input;
use WEM\PortfolioBundle\Model\Portfolio;

class PortfolioInsertTagListener
{
    public const TAG = 'portfolio';

    /**
     * Examples:
     * {{portfolio::title}}
     * {{portfolio::title::1}}
     *
     * @Hook("replaceInsertTags", priority=100)
     */
    public function replaceInsertTags(string $tag)
    {
        $chunks = explode('::', $tag);

        if (self::TAG !== $chunks[0]) {
            return false;
        }

        // Check if we want a specific portfolio or the current one
        $varItem = (3 === \count($chunks)) ? $chunks[2] : Input::get('item');
        $objItem = Portfolio::findByIdOrSlug($varItem);

        // If objItem does not exist, return empty string
        // We can't throw an Exception that can break a website just because an ID is wrong, can't we?
        if (null === $objItem) {
            return '';
        }

        // Specific behavior for singleSRC
        if ('singleSRC' === $chunks[1]) {
            $objFile = FilesModel::findByUuid($objItem->{$chunks[1]});

            return $objFile->path;
        }

        return $objItem->getAttributeValue($chunks[1]) ?: $objItem->{$chunks[1]};
    }
}
