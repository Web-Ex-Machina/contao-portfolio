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

namespace WEM\PortfolioBundle\Model;

use Contao\Model;
use RuntimeException as Exception;

/**
 * Reads and writes items.
 */
class ItemCategory extends Model
{
    /**
     * Table name.
     *
     * @var string
     */
    protected static $strTable = 'tl_wem_portfolio_item_category';

    /**
     * Find items, depends on the arguments.
     *
     * @param array
     * @param int
     * @param int
     * @param array
     *
     * @return Collection
     */
    public static function findItems($arrConfig = [], $intLimit = 0, $intOffset = 0, array $arrOptions = [])
    {
        try {
            $t = static::$strTable;
            $arrColumns = static::formatColumns($arrConfig);

            if ($intLimit > 0) {
                $arrOptions['limit'] = $intLimit;
            }

            if ($intOffset > 0) {
                $arrOptions['offset'] = $intOffset;
            }

            if (!isset($arrOptions['order'])) {
                $arrOptions['order'] = "$t.sorting ASC";
            }

            if (empty($arrColumns)) {
                return static::findAll($arrOptions);
            }

            return static::findBy($arrColumns, null, $arrOptions);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Count item attributes, depends on the arguments.
     *
     * @param array
     * @param array
     *
     * @return int
     */
    public static function countItems($arrConfig = [], array $arrOptions = [])
    {
        try {
            $t = static::$strTable;
            $arrColumns = static::formatColumns($arrConfig);
            if (empty($arrColumns)) {
                return static::countAll($arrOptions);
            }

            return static::countBy($arrColumns, null, $arrOptions);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Format ItemModel columns.
     *
     * @param [Array] $arrConfig [Configuration to format]
     *
     * @return [Array] [The Model columns]
     */
    public static function formatColumns($arrConfig)
    {
        try {
            $t = static::$strTable;

            if ($arrConfig['category']) {
                $arrColumns[] = "$t.category = ".$arrConfig['category'];
            }

            if ($arrConfig['item']) {
                $arrColumns[] = "$t.item = ".$arrConfig['item'];
            }

            if ($arrConfig['not']) {
                $arrColumns[] = $arrConfig['not'];
            }

            return $arrColumns;
        } catch (Exception $e) {
            throw $e;
        }
    }
}
