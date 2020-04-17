<?php

declare(strict_types=1);

/**
 * Contao Portfolio for Contao Open Source CMS
 * Copyright (c) 2015-2020 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-portfolio
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-portfolio/
 */

namespace WEM\PortfolioBundle\Hooks;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Terminal42\ServiceAnnotationBundle\ServiceAnnotationInterface;

class ExecutePreActionsListener implements ServiceAnnotationInterface
{
    /**
     * @Hook("executePreActions")
     */
    public function onExecutePreActions(string $action): void
    {
        if ('WemPortfolioSortItems' === $action) {
            $objDb = \Database::getInstance();
            foreach (\Input::post('pos') as $p => $id) {
                $objDb->prepare('UPDATE tl_wem_portfolio_item SET sorting = ? WHERE id = ?')->execute($p, $id);
            }
        }
    }
}
