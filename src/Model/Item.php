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

use Contao\PageModel;
use Exception;
use WEM\UtilsBundle\Model\Model;

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
     * Default order column
     *
     * @var string
     */
    protected static $strOrderColumn = "date DESC";

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
                case 'category':
                    $arrColumns[] = "$t.id IN (SELECT t2.item FROM tl_wem_portfolio_category_item AS t2 WHERE t2.pid = ".$varValue.')';
                break;

                case 'categories':
                    $arrColumns[] = "$t.id IN (SELECT t2.item FROM tl_wem_portfolio_category_item AS t2 WHERE t2.pid IN (".implode(',', $varValue).'))';
                break;

                case 'attributes':
                    $i = 1;
                    foreach ($varValue as $a) {
                        ++$i;
                        $arrColumns[] = "$t.id IN(SELECT t".$i.'.pid FROM tl_wem_portfolio_item_attribute AS t'.$i.' WHERE t'.$i.'.attribute = '.$a['attribute'].' AND t'.$i.".value = '".$a['value']."')";
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

    /**
     * Generate item url
     * @param  boolean $blnAbsolute
     * @return string
     */
    public function getUrl($blnAbsolute = false)
    {
        $objCategories = $this->getRelated('categories');
        $objFirstCategory = $objCategories->first();
        $objPage = PageModel::findByPk($objFirstCategory->jumpTo);
        return $blnAbsolute ? $objPage->getAbsoluteUrl('/'.$this->alias) : $objPage->getFrontendUrl('/'.$this->alias);
    }
}