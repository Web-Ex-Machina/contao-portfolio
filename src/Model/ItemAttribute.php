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
 * Reads and writes item attributes.
 */
class ItemAttribute extends Model
{
    /**
     * Table name.
     *
     * @var string
     */
    protected static $strTable = 'tl_wem_portfolio_item_attribute';

    /**
     * Default order column
     *
     * @var string
     */
    protected static $strOrderColumn = "attribute ASC";

    /**
     * [formatStatement description]
     * @param  [type] $strField    [description]
     * @param  [type] $varValue    [description]
     * @param  string $strOperator [description]
     * @return [type]              [description]
     */
    public static function formatStatement($strField, $varValue, $strOperator = '='): array
    {
        try {
            $arrColumns = [];
            $t = static::$strTable;

            switch ($strField) {
                case 'name':
                    $arrColumns[] = "$t.attribute IN(SELECT t3.id FROM tl_wem_portfolio_attribute AS t3 WHERE t3.name = '".$varValue."')";
                break;

                case 'displayInFrontend':
                    if (1 === $varValue) {
                        $arrColumns[] = "$t.attribute IN(SELECT t1.id FROM tl_wem_portfolio_attribute AS t1 WHERE t1.displayInFrontend = '1')";
                    } elseif (0 === $varValue) {
                        $arrColumns[] = "$t.attribute IN(SELECT t2.id FROM tl_wem_portfolio_attribute AS t2 WHERE t2.displayInFrontend = '')";
                    }
                break;

                // Load parent
                default:
                    $arrColumns = array_merge($arrColumns, parent::formatStatement($strField, $varValue, $strOperator));
            }

            return $arrColumns;
        } catch (Exception $e) {
            throw $e;
        }
    }
}
