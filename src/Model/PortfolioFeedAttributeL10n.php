<?php

declare(strict_types=1);

namespace WEM\PortfolioBundle\Model;

use Contao\Model\Collection;
use WEM\UtilsBundle\Model\Model;

/**
 * Reads and writes items.
 */
class PortfolioFeedAttributeL10n extends Model
{
    /**
     * Table name.
     *
     * @var string
     */
    protected static $strTable = 'tl_wem_portfolio_feed_attribute_l10n';
}
