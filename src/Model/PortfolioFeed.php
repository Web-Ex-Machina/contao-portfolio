<?php

declare(strict_types=1);


namespace WEM\PortfolioBundle\Model;

use WEM\UtilsBundle\Model\Model;

/**
 * Reads and writes items.
 */
class PortfolioFeed extends Model
{
    /**
     * Table name.
     *
     * @var string
     */
    protected static $strTable = 'tl_wem_portfolio_feed';
}
