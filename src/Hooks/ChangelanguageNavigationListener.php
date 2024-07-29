<?php declare(strict_types=1);

namespace WEM\PortfolioBundle\Hooks;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Terminal42\ChangeLanguage\EventListener\Navigation\AbstractNavigationListener;

/**
 * @Hook("changelanguageNavigation")
 */
class ChangelanguageNavigationListener extends AbstractNavigationListener
{


    protected function getUrlKey()
    {
    }

    protected function findCurrent()
    {
    }

    protected function findPublishedBy(array $columns, array $values = [], array $options = [])
    {
    }
}