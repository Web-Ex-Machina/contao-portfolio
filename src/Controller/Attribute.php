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

namespace WEM\PortfolioBundle\Controller;

use RuntimeException as Exception;
use WEM\PortfolioBundle\Model\Attribute as AttributeModel;

/**
 * Class Attribute - Handle Portfolio ItemAttributes functions.
 */
class Attribute extends \Controller
{
    /**
     * Get Attributes.
     *
     * @param [Array]   $arrConfig  [Configuration wanted for the list]
     * @param [Integer] $intLimit   [Query Limit]
     * @param [Integer] $intOffset  [Query Offset]
     * @param [Array]   $arrOptions [Query Options]
     *
     * @return [Array] [Items list as Array]
     */
    public static function getItems($arrConfig, $intLimit = 0, $intOffset = 0, $arrOptions = [])
    {
        try {
            $objItems = AttributeModel::findItems($arrConfig, $intLimit, $intOffset, $arrOptions);

            if (!$objItems) {
                return;
            }

            $arrItems = [];

            while ($objItems->next()) {
                $arrItems[] = static::getItem($objItems->row(), $arrConfig['getItem']);
            }

            return $arrItems;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Get Attribute.
     *
     * @param [Mixed] $varItem   [ItemAttribute ID, Alias, Array or Object]
     * @param [Array] $arrConfig [ItemAttribute configuration]
     *
     * @return [Array] [ItemAttribute data]
     */
    public static function getItem($varItem, $arrConfig = [])
    {
        try {
            if (\is_array($varItem)) {
                $arrItem = $varItem;
            } elseif ($varItem instanceof AttributeModel || $varItem = AttributeModel::findByPk($varItem)) {
                $arrItem = $varItem->row();
            } else {
                return;
            }

            return $arrItem;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Count Attributes.
     *
     * @param [Array] $arrConfig  [Configuration wanted for the count]
     * @param [Array] $arrOptions [Query Options]
     *
     * @return [Integer] [Number of items]
     */
    public static function countItems($arrConfig, $arrOptions = [])
    {
        try {
            return AttributeModel::countItems($arrConfig, $arrOptions);
        } catch (Exception $e) {
            throw $e;
        }
    }
}
