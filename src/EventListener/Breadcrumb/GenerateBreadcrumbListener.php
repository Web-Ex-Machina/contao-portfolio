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

namespace WEM\PortfolioBundle\EventListener\Breadcrumb;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Environment;
use Contao\Input;
use Contao\Module;
use Exception;
use Symfony\Component\HttpFoundation\RequestStack;
use WEM\PortfolioBundle\Model\Portfolio;
use WEM\PortfolioBundle\Model\PortfolioL10n;
use WEM\PortfolioBundle\Service\PortfolioService;

class GenerateBreadcrumbListener
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly PortfolioService $service,
    ) {
    }

    #[AsHook('generateBreadcrumb', priority: 100)]
    public function onGenerateBreadcrumb(array $items, Module $module): array
    {
        // Check if we have an auto_item and if it's an Offer
        if (Input::get('item')) {
            try {
                $this->service->load(
                    Input::get('item'), 
                    $this->requestStack->getCurrentRequest()->getLocale()
                );
            } catch (Exception $e) {
                return $items;
            }

            array_pop($items);

            $items[] = [
                'isRoot' => false,
                'isActive' => true,
                'href' => Environment::get('request'),
                'title' => $this->service->getField('title'),
                'link' => $this->service->getField('title'),
                'class' => '',
            ];
        }

        return $items;
    }
}
