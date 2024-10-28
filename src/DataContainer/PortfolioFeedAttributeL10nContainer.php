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

class PortfolioFeedAttributeL10nContainer extends Backend
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Format items list.
     */
    public function listItems(array $r): string
    {
        return \sprintf(
            '%s <span style="color:#888">[%s]</span>',
            $r['language'],
            $r['label']
        );
    }
}
