<?php

declare(strict_types=1);

/**
 * Metal Store Bundle for Contao Open Source CMS
 * Copyright (c) 2021-2021 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/metalstore-contao-bundle
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/metalstore-contao-bundle/
 */

namespace WEM\PortfolioBundle\EventListener;

use Contao\Input;
use Terminal42\ChangeLanguage\Event\ChangelanguageNavigationEvent;
use WEM\PortfolioBundle\Model\Portfolio;

class ChangeLanguageNavigationListener
{
    public function __invoke(ChangelanguageNavigationEvent $event): void
    {

        // The target root page for current event
        $targetRoot = $event->getNavigationItem()->getRootPage();

        $language = $targetRoot->rootLanguage; // The target language
        $currentPage = $event->getNavigationItem()->getTargetPage();

        $item = $this->getPortfolioItem();
        if (!$item){
           return;
        }
        // Pass the new alias to ChangeLanguage
        $event->getUrlParameterBag()->setUrlAttribute("items", $item->slug);
    }

    private function getPortfolioItem(): ?Portfolio
    {
        $slug = Input::get('auto_item');

        return ($slug)?Portfolio::findByIdOrSlug($slug):null;
    }
}