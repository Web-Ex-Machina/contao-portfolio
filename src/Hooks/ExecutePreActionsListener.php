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

class ExecutePreActionsListener
{
    /**
     * @Hook("executePreActions")
     */
    public function onExecutePreActions(string $action): void
    {
        if ('WemPortfolioSortItems' === $action) {
            $objDb = \Database::getInstance();
            $objDb->prepare(sprintf('UPDATE %s SET sorting = sorting+1 WHERE sorting > ?', \Input::post('table')))->execute(\Input::post('posAfter'));
            $objDb->prepare(sprintf('UPDATE %s SET sorting = ? WHERE id = ?', \Input::post('table')))->execute(\Input::post('posAfter') + 1, \Input::post('id'));
        }
    }
}
