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

use Contao\Input;
use WEM\PortfolioBundle\Model\Item;

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
        int $_cnt //TODO : many useless var no ?
    ) {
        $arrTag = explode('::', $tag);

        // Ignore if the tag is not for this module
        if ('wemportfolio' !== $arrTag[0]) {
            return false;
        }

        // Load the current item
        $objItem = Item::findByIdOrAlias(Input::get('auto_item'));

        if ($arrTag[1] == 'attr') {
            if (!$arrTag[2] || !\is_array($objItem['attributes']) || !\array_key_exists($arrTag[2], $objItem['attributes'])) {
                return false;
            }

            return $objItem->getAttribute($arrTag[2]);
        }
        return $objItem->{$arrTag[1]} ?: '';
    }
}
