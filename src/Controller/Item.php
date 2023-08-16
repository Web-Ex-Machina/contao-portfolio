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

namespace WEM\PortfolioBundle\Controller;

use RuntimeException as Exception;
use WEM\PortfolioBundle\Model\Item as ItemModel;

/**
 * Class Item - Handle Portfolio Items functions.
 */
class Item extends \Controller
{
    /**
     * Get Items.
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
            $objItems = ItemModel::findItems($arrConfig, $intLimit, $intOffset, $arrOptions);

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
     * Get Item.
     *
     * @param [Mixed] $varItem   [Item ID, Alias, Array or Object]
     * @param [Array] $arrConfig [Item configuration]
     *
     * @return [Array] [Item data]
     */
    public static function getItem($varItem, $arrConfig = [])
    {
        try {
            if (\is_array($varItem)) {
                $arrItem = $varItem;
            } elseif ($varItem instanceof ItemModel || $varItem = ItemModel::findByIdOrAlias($varItem)) {
                $arrItem = $varItem->row();
            } else {
                return;
            }

            // Parse item dates
            $arrDates = ['timestamp' => $arrItem['createdAt'], 'date' => \Date::parse(\Config::get('datimFormat'), $arrItem['createdAt']), 'datetime' => \Date::parse('Y-m-d\TH:i:sP', $arrItem['createdAt'])];
            $arrItem['createdAt'] = $arrDates;
            $arrDates = ['timestamp' => $arrItem['date'], 'date' => \Date::parse(\Config::get('datimFormat'), $arrItem['date']), 'datetime' => \Date::parse('Y-m-d\TH:i:sP', $arrItem['date'])];
            $arrItem['date'] = $arrDates;

            // Fetch item pictures
            if ($arrItem['pictures'] = \StringUtil::deserialize($arrItem['pictures'])) {
                $objFiles = \FilesModel::findMultipleByUuids($arrItem['pictures']);
                $images = [];
                while ($objFiles->next()) {
                    $images[$objFiles->path] = [
                        'id' => $objFiles->id,
                        'uuid' => $objFiles->uuid,
                        'name' => $objFile->basename,
                        'singleSRC' => $objFiles->path,
                        'filesModel' => $objFiles->current(),
                    ];
                }

                if ('' !== $arrItem['orderPictures']) {
                    $t = \StringUtil::deserialize($arrItem['orderPictures']);
                    if (!empty($t) && \is_array($t)) {
                        // Remove all values
                        $arrOrder = array_map(function (): void {
                        }, array_flip($t));

                        // Move the matching elements to their position in $arrOrder
                        foreach ($images as $k => $v) {
                            if (\array_key_exists($v['uuid'], $arrOrder)) {
                                $arrOrder[$v['uuid']] = $v;
                                unset($images[$k]);
                            }
                        }

                        // Append the left-over images at the end
                        if (!empty($images)) {
                            $arrOrder = array_merge($arrOrder, array_values($images));
                        }

                        // Remove empty (unreplaced) entries
                        $images = array_values(array_filter($arrOrder));
                        unset($arrOrder);
                    }
                }

                $arrItem['pictures'] = $images;
            }

            // Load item categories
            $objItem = ItemModel::findByPk($arrItem['id']);
            $objCategories = $objItem->getRelated('categories');

            if (0 === $objCategories->count()) {
                $arrItem['categories'] = null;
            } else {
                $arrItem['categories'] = [];
                while ($objCategories->next()) {
                    $arrItem['categories'][] = $objCategories->row();
                }
            }

            // Generate item link (category jumpTo page + item alias)*
            if (null !== $arrItem['categories'] && !empty($arrItem['categories']) && $objPage = \PageModel::findByPk($arrItem['categories'][0]['jumpTo'])) {
                $arrItem['link'] = $objPage->getFrontendUrl('/'.$arrItem['alias']);
            }

            // Get the item attributes
            $arrConfig['itemAttributes']['pid'] = $arrItem['id'];
            $arrConfig['itemAttributes']['displayInFrontend'] = 1;
            if ($attributes = ItemAttribute::getItems($arrConfig['itemAttributes'])) {
                $arrItem['attributes'] = [];
                foreach ($attributes as $attribute) {
                    $arrItem['attributes'][$attribute['attribute']['alias']] = ['label' => $attribute['attribute']['title'], 'value' => $attribute['value']];
                }
            }

            return $arrItem;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Count Items.
     *
     * @param [Array] $arrConfig  [Configuration wanted for the count]
     * @param [Array] $arrOptions [Query Options]
     *
     * @return [Integer] [Number of items]
     */
    public static function countItems($arrConfig, $arrOptions = [])
    {
        try {
            return ItemModel::countItems($arrConfig, $arrOptions);
        } catch (Exception $e) {
            throw $e;
        }
    }
}
