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

namespace WEM\PortfolioBundle\DataContainer;

use Contao\Backend;
use Contao\DataContainer;
use Contao\System;

class PortfolioFeedContainer extends Backend
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Auto-generate an article alias if it has not been set yet.
     *
     * @throws \Exception
     */
    public function generateAlias($varValue, DataContainer $dc): string
    {
        $aliasExists = fn (string $alias): bool => $this->Database->prepare('SELECT id FROM tl_wem_portfolio_feed WHERE alias=? AND id!=?')->execute($alias, $dc->id)->numRows > 0;

        // Generate an alias if there is none
        if (!$varValue) {
            $varValue = System::getContainer()->get('contao.slug')->generate($dc->activeRecord->title, $dc->activeRecord->id, $aliasExists);
        } elseif ($aliasExists($varValue)) {
            throw new \Exception(\sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
        }

        return $varValue;
    }

    /**
     * Get Notification Choices for this kind of modules.
     *
     * @return array [Array]
     */
    public function getAlertEmailNotificationChoices(): array
    {
        $arrChoices = [];
        $objNotifications = $this->Database->execute("SELECT id,title FROM tl_nc_notification WHERE type='wem_portfolios_alerts_email' ORDER BY title");

        while ($objNotifications->next()) {
            $arrChoices[$objNotifications->id] = $objNotifications->title;
        }

        return $arrChoices;
    }
}
