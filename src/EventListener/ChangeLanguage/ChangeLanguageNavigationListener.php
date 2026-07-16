<?php

declare(strict_types=1);

/**
 * Contao Portfolio for Contao Open Source CMS
 * Copyright (c) 2015-2025 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-portfolio
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-portfolio/
 */

namespace WEM\PortfolioBundle\EventListener\ChangeLanguage;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Input;
use Terminal42\ChangeLanguage\Event\ChangelanguageNavigationEvent;
use WEM\PortfolioBundle\Model\Portfolio;
use WEM\PortfolioBundle\Model\PortfolioL10n;
use WEM\PortfolioBundle\Service\PortfolioService;

class ChangeLanguageNavigationListener
{
    public function __construct(
        private readonly PortfolioService $service
    ) {
    }

    #[AsHook('changelanguageNavigation', priority: 100)]
    public function onChangelanguageNavigation(ChangelanguageNavigationEvent $event): void
    {
        if (!Input::get('item')) {
            return;
        }

        // The target root page for current event
        $targetRoot = $event->getNavigationItem()->getRootPage();
        $language = $targetRoot->rootLanguage; // The target language

        $this->service->load(Input::get('item'));

        $event->getUrlParameterBag()->setUrlAttribute(
            'item', 
            $this->service->getField('slug', $language)
        );
    }
}
