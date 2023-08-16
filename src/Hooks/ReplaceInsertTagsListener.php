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

use WEM\PortfolioBundle\Model\Item as Item;

class ReplaceInsertTagsListener
{
    public function onReplaceInsertTags(
        string $tag,
        bool $useCache,
        string $cachedValue,
        array $flags,
        array $tags,
        array $cache,
        int $_rit,
        int $_cnt
    ) {
        $arrTag = explode('::', $tag);

        // Ignore if the tag is not for this module
        if ('wemportfolio' !== $arrTag[0]) {
            return false;
        }

        // Load the current item
        $objItem = Item::findByIdOrAlias(\Input::get('auto_item'));

        switch ($arrTag[1]) {
            case 'attr':
                if (!$arrTag[2] || !\is_array($objItem['attributes']) || !\array_key_exists($arrTag[2], $objItem['attributes'])) {
                    return false;
                }

                return $objItem->getAttribute($arrTag[2]);
            break;

            default:
                return $objItem->{$arrTag[1]} ?: '';
        }

        return false;
    }
}
