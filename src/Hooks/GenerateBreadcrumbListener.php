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

use WEM\PortfolioBundle\Model\Category;
use WEM\PortfolioBundle\Model\Item;

class GenerateBreadcrumbListener
{
    /**
     * @Hook("generateBreadcrumb")
     */
    public function onGenerateBreadcrumb(array $items, \Module $module): array
    {
        // Check if we have an auto_item and if it's a Portfolio Category
        if (\Input::get('auto_item') && $objCategory = Category::findByIdOrAlias(\Input::get('auto_item'))) {
            global $objPage;

            // Update the last item
            end($items);
            $items[key($items)]['isActive'] = false;

            $items[] = [
                'isRoot' => false,
                'isActive' => true,
                'href' => \Environment::get('request'),
                'title' => $objCategory->title,
                'link' => $objCategory->title,
                'class' => '',
            ];
        } elseif (\Input::get('auto_item') && $objItem = Item::findByIdOrAlias(\Input::get('auto_item'))) {
            global $objPage;

            // Update the last item
            end($items);
            $items[key($items)]['title'] = $objItem->title;
            $items[key($items)]['link'] = $objItem->title;
        }

        return $items;
    }
}
