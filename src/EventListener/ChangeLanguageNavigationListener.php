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

use Contao\Input;
use Terminal42\ChangeLanguage\Event\ChangelanguageNavigationEvent;
use WEM\PortfolioBundle\Model\Portfolio;
use WEM\PortfolioBundle\Model\PortfolioL10n;

class ChangeLanguageNavigationListener
{
    public function onChangelanguageNavigation(ChangelanguageNavigationEvent $event): void
    {
        // The target root page for current event
        $targetRoot = $event->getNavigationItem()->getRootPage();
        $language = $targetRoot->rootLanguage; // The target language

        $objPortfolio = Portfolio::findByIdOrSlug(Input::get('item'));

        if (null === $objPortfolio) {
            $objL10nItem = PortfolioL10n::findByIdOrSlug(Input::get('item'));
            $objPortfolio = $objL10nItem->getRelated('pid');
        }

        if ($objPortfolio) {
            $event->getUrlParameterBag()->setUrlAttribute('item', $objPortfolio->getL10nLabel('slug', $language));
        }
    }
}
