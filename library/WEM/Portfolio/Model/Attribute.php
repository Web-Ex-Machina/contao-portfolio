<?php

/**
 * Module Portfolio for Contao Open Source CMS.
 *
 * Copyright (c) 2015-2018 Web ex Machina
 *
 * @author Web ex Machina <http://www.webexmachina.fr>
 */

namespace WEM\Portfolio\Model;

use RuntimeException as Exception;
use Contao\Model;

/**
 * Reads and writes attributes.
 */
class Attribute extends Model
{
    /**
     * Table name.
     *
     * @var string
     */
    protected static $strTable = 'tl_wem_portfolio_attribute';

    /**
     * Find attributes, depends on the arguments.
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
                $arrOptions['order'] = "$t.title ASC";
            }

            if (empty($arrColumns)) {
                return static::findAll($arrOptions);
            } else {
                return static::findBy($arrColumns, null, $arrOptions);
            }
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
            } else {
                return static::countBy($arrColumns, null, $arrOptions);
            }
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Format AttributeModel columns.
     *
     * @param [Array] $arrConfig [Configuration to format]
     *
     * @return [Array] [The Model columns]
     */
    public static function formatColumns($arrConfig)
    {
        try {
            $t = static::$strTable;
            $arrColumns = [];

            if ($arrConfig['title']) {
                $arrColumns[] = "$t.title = '".$arrConfig['title']."'";
            }

            if (1 === $arrConfig['useAsFilter']) {
                $arrColumns[] = "$t.useAsFilter = '1'";
            } elseif (0 === $arrConfig['useAsFilter']) {
                $arrColumns[] = "$t.useAsFilter = ''";
            }

            if (1 === $arrConfig['displayInFrontend']) {
                $arrColumns[] = "$t.displayInFrontend = '1'";
            } elseif (0 === $arrConfig['displayInFrontend']) {
                $arrColumns[] = "$t.displayInFrontend = ''";
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
