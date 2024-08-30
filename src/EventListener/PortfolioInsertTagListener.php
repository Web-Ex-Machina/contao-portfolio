<?php

declare(strict_types=1);

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
     * @Hook("replaceInsertTags", priority=100)
     */
    public function replaceInsertTags(string $tag)
    {
        $chunks = explode('::', $tag);

        if (self::TAG !== $chunks[0]) {
            return false;
        }

        // Check if we want a specific portfolio or the current one
        $varItem = (3 === count($chunks)) ? $chunks[2] : Input::get('auto_item');
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