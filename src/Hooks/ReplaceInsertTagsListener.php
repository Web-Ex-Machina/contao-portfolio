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

namespace WEM\PortfolioBundle\Hooks;

use WEM\PortfolioBundle\Controller\Item as PItem;

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
        $item = PItem::getItem(\Input::get('auto_item'));

        switch ($arrTag[1]) {
            case 'attr':
                if (!$arrTag[2] || !\is_array($item['attributes']) || !\array_key_exists($arrTag[2], $item['attributes'])) {
                    return false;
                }

                return $item['attributes'][$arrTag[2]][$arrTag[3] ?: 'value'];
            break;

            default:
                // Check if the value exist in portfolio table
                if (!\array_key_exists($arrTag[1], $item)) {
                    return false;
                }

                return $item[$arrTag[1]];
        }

        return false;
    }
}
