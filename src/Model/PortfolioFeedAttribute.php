<?php

declare(strict_types=1);

namespace WEM\PortfolioBundle\Model;

use Contao\Model\Collection;
use WEM\UtilsBundle\Model\Model;

/**
 * Reads and writes items.
 */
class PortfolioFeedAttribute extends Model
{
    /**
     * Table name.
     *
     * @var string
     */
    protected static $strTable = 'tl_wem_portfolio_feed_attribute';

    /**
     * Find items, depends on the arguments.
     *
     *
     * @return Model|Collection|null
     * @throws \Exception
     */
    public static function findItems(
        array $arrConfig = [], int $intLimit = 0,
        int   $intOffset = 0, array $arrOptions = []
    ): ?Collection
    {
        $t = static::$strTable;
        $arrColumns = static::formatColumns($arrConfig);

        if ($intLimit > 0) {
            $arrOptions['limit'] = $intLimit;
        }

        if ($intOffset > 0) {
            $arrOptions['offset'] = $intOffset;
        }

        if (!isset($arrOptions['order'])) {
            $arrOptions['order'] = $t . '.createdAt DESC';
        }

        if ($arrColumns === []) {
            return static::findAll($arrOptions);
        }

        return static::findBy($arrColumns, null, $arrOptions);
    }

    /**
     * Generic statements format.
     *
     * @param string $strField [Column to format]
     * @param mixed $varValue [Value to use]
     * @param string $strOperator [Operator to use, default "="]
     */
    public static function formatStatement(string $strField, $varValue, string $strOperator = '='): array
    {
        $arrColumns = [];
        $t = static::$strTable;

        switch ($strField) {
            // Search by pid
            case 'pid':
                if (\is_array($varValue)) {
                    $arrColumns[] = $t . '.pid IN(' . implode(',', array_map('\intval', $varValue)) . ')';
                } else {
                    $arrColumns[] = $t . '.pid = ' . $varValue;
                }

                break;

            // Search by name
            case 'name':
                if (\is_array($varValue)) {
                    $arrColumns[] = $t . ".name IN('" . implode("','", $varValue) . "')";
                } else {
                    $arrColumns[] = $t . '.name = "' . $varValue . '"';
                }

                break;
        }

        return $arrColumns;
    }
}