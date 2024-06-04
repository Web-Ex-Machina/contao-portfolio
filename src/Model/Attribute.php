<?php

declare(strict_types=1);

/**
 * Contao Portfolio for Contao Open Source CMS
 * Copyright (c) 2015-2023 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-portfolio
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-portfolio/
 */

namespace WEM\PortfolioBundle\Model;

use Exception;
use WEM\UtilsBundle\Model\Model;

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
     * Default order column
     *
     * @var string
     */
    protected static $strOrderColumn = "title ASC";

    /**
     * Formats the statement for querying the database table.
     *
     * @param string $strField The field to use in the statement.
     * @param mixed $varValue The value to compare against.
     * @param string $strOperator The comparison operator to use.
     * @return array The formatted statement.
     * @throws Exception If an error occurs during the formatting process.
     */
    public static function formatStatement(string $strField, $varValue, string $strOperator = '='): array
    {
        $arrColumns = [];
        $t = static::$strTable;

        switch ($strField) {
            case 'useAsFilter':
                if (1 === $varValue) {
                    $arrColumns[] = "$t.useAsFilter = '1'";
                } elseif (0 === $varValue) {
                    $arrColumns[] = "$t.useAsFilter = ''";
                }
            break;

            case 'displayInFrontend':
                if (1 === $varValue) {
                    $arrColumns[] = "$t.displayInFrontend = '1'";
                } elseif (0 === $varValue) {
                    $arrColumns[] = "$t.displayInFrontend = ''";
                }
            break;

            // Load parent
            default:
                $arrColumns = array_merge($arrColumns, parent::formatStatement($strField, $varValue, $strOperator));
        }

        return $arrColumns;
    }
}
