<?php

declare(strict_types=1);

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
        if (Input::get('auto_item') && $objOffer = Portfolio::findByIdOrCode(Input::get('auto_item'))) {
            array_pop($items);

            $items[] = [
                'isRoot' => false,
                'isActive' => true,
                'href' => Environment::get('request'),
                'title' => $objOffer->title,
                'link' => $objOffer->title,
                'class' => '',
            ];
        }

        return $items;
    }
}