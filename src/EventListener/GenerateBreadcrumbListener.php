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
use Contao\Environment;
use Contao\Input;
use Contao\Module;
use WEM\PortfolioBundle\Model\Portfolio;

class GenerateBreadcrumbListener
{
    /**
     * @Hook("generateBreadcrumb", priority=100)
     */
    public function onGenerateBreadcrumb(array $items, Module $module): array
    {
        // Check if we have an auto_item and if it's an Offer
        if (Input::get('item') && $objItem = Portfolio::findByIdOrSlug(Input::get('item'))) {
            array_pop($items);

            $items[] = [
                'isRoot' => false,
                'isActive' => true,
                'href' => Environment::get('request'),
                'title' => $objItem->title,
                'link' => $objItem->title,
                'class' => '',
            ];
        }

        return $items;
    }
}
