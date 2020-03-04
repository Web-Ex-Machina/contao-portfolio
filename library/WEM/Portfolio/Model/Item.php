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

namespace WEM\Portfolio\Model;

use Contao\Model;
use RuntimeException as Exception;

/**
 * Reads and writes items.
 */
class Item extends Model
{
    /**
     * Table name.
     *
     * @var string
     */
    protected static $strTable = 'tl_wem_portfolio_item';

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
                $arrOptions['order'] = "$t.date DESC";
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
            $arrColumns = ["$t.published=1"];

            if ($arrConfig['category']) {
                $arrColumns[] = "$t.id IN (SELECT t2.pid FROM tl_wem_portfolio_item_page AS t2 WHERE t2.page = ".$arrConfig['category'].')';
            }

            if ($arrConfig['categories']) {
                $arrColumns[] = "$t.id IN (SELECT t2.pid FROM tl_wem_portfolio_item_page AS t2 WHERE t2.page IN (".implode(',', $arrConfig['categories']).'))';
            }

            if ($arrConfig['alias']) {
                $arrColumns[] = "$t.alias = '".$arrConfig['alias']."'";
            }

            if ($arrConfig['lang']) {
                $arrColumns[] = "$t.i18nl10n_lang = '".$arrConfig['lang']."'";
            }

            if ($arrConfig['not_lang']) {
                $arrColumns[] = "$t.i18nl10n_lang != '".$arrConfig['not_lang']."'";
            }

            if ($arrConfig['i18nl10n_id']) {
                $arrColumns[] = "$t.i18nl10n_id = ".$arrConfig['i18nl10n_id'];
            }

            if ($arrConfig['attributes']) {
                $i = 1;
                foreach ($arrConfig['attributes'] as $attribute) {
                    ++$i;
                    $arrColumns[] = "$t.id IN(SELECT t".$i.'.pid FROM tl_wem_portfolio_item_attribute AS t'.$i.' WHERE t'.$i.'.attribute = '.$attribute['attribute'].' AND t'.$i.".value = '".$attribute['value']."')";
                }
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
