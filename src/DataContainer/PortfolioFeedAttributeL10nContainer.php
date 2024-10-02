<?php

declare(strict_types=1);

namespace WEM\PortfolioBundle\DataContainer;

use Contao\Backend;

class PortfolioFeedAttributeL10nContainer extends Backend
{
    public function __construct()
    {
        Parent::__construct();
    }

    /**
     * Format items list.
     */
    public function listItems(array $r): string
    {
        return sprintf(
            '%s <span style="color:#888">[%s]</span>',
            $r['language'],
            $r['label']
        );
    }
}
