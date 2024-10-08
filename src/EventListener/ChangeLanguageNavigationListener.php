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

class ChangeLanguageNavigationListener
{
    public function onChangelanguageNavigation(ChangelanguageNavigationEvent $event): void
    {
        // The target root page for current event
        $targetRoot = $event->getNavigationItem()->getRootPage();
        $language = $targetRoot->rootLanguage; // The target language
        $currentPage = $event->getNavigationItem()->getTargetPage();
        $key = 'items';

        switch($currentPage->alias) {
            case 'task':
                $alias = Input::get('auto_item');
                break;
            default:
                return;
        }

        // Pass the new alias to ChangeLanguage
        $event->getUrlParameterBag()->setUrlAttribute($key, $alias);
    }
}