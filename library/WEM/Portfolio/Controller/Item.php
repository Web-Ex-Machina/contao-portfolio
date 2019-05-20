<?php

/**
 * Module Portfolio for Contao Open Source CMS
 *
 * Copyright (c) 2015-2018 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

namespace WEM\Portfolio\Controller;

use \RuntimeException as Exception;

use WEM\Portfolio\Model\Item as ItemModel;

/**
 * Class Item - Handle Portfolio Items functions
 */
class Item extends \Controller
{
    /**
     * Get Items
     * @param  [Array]   $arrConfig  [Configuration wanted for the list]
     * @param  [Integer] $intLimit   [Query Limit]
     * @param  [Integer] $intOffset  [Query Offset]
     * @param  [Array]   $arrOptions [Query Options]
     * @return [Array]               [Items list as Array]
     */
    public static function getItems($arrConfig, $intLimit = 0, $intOffset = 0, $arrOptions = array())
    {
        try {
            $objItems = ItemModel::findItems($arrConfig, $intLimit, $intOffset, $arrOptions);

            if (!$objItems) {
                return;
            }

            $arrItems = array();

            while ($objItems->next()) {
                $arrItems[] = static::getItem($objItems->row(), $arrConfig["getItem"]);
            }

            return $arrItems;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Get Item
     * @param  [Mixed] $varItem   [Item ID, Alias, Array or Object]
     * @param  [Array] $arrConfig [Item configuration]
     * @return [Array]            [Item data]
     */
    public static function getItem($varItem, $arrConfig = array())
    {
        try {
            if (is_array($varItem)) {
                $arrItem = $varItem;
            } elseif ($varItem instanceof ItemModel || $varItem = ItemModel::findByIdOrAlias($varItem)) {
                $arrItem = $varItem->row();
            } else {
                return;
            }

            // Parse item dates
            $arrDates = ["timestamp"=>$arrItem['created_on'], "date"=>\Date::parse(\Config::get('datimFormat'), $arrItem['created_on']), "datetime"=>\Date::parse('Y-m-d\TH:i:sP', $arrItem['created_on'])];
            $arrItem['created_on'] = $arrDates;
            $arrDates = ["timestamp"=>$arrItem['date'], "date"=>\Date::parse(\Config::get('datimFormat'), $arrItem['date']), "datetime"=>\Date::parse('Y-m-d\TH:i:sP', $arrItem['date'])];
            $arrItem['date'] = $arrDates;

            // Fetch item pictures
            if ($arrItem['pictures'] = \StringUtil::deserialize($arrItem['pictures'])) {
                $objFiles = \FilesModel::findMultipleByUuids($arrItem['pictures']);
                $images = [];
                while ($objFiles->next()) {
                    $images[$objFiles->path] = array(
                        'id'         => $objFiles->id,
                        'uuid'       => $objFiles->uuid,
                        'name'       => $objFile->basename,
                        'singleSRC'  => $objFiles->path,
                        'filesModel' => $objFiles->current()
                    );
                }

                if ($arrItem['orderPictures'] != '') {
                    $t = \StringUtil::deserialize($arrItem['orderPictures']);
                    if (!empty($t) && \is_array($t)) {
                        // Remove all values
                        $arrOrder = array_map(function () {
                        }, array_flip($t));

                        // Move the matching elements to their position in $arrOrder
                        foreach ($images as $k => $v) {
                            if (array_key_exists($v['uuid'], $arrOrder)) {
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

            // Get the item category
            if ($arrConfig["getCategory"]) {
                $arrItem['category'] = \PageModel::findByPk($arrItem['category']);
            }

            // Get the item attributes
            $arrConfig["itemAttributes"]["pid"] = $arrItem['id'];
            $arrConfig["itemAttributes"]["displayInFrontend"] = 1;
            if ($attributes = ItemAttribute::getItems($arrConfig["itemAttributes"])) {
                $arrItem["attributes"] = [];
                foreach ($attributes as $attribute) {
                    $arrItem["attributes"][$attribute['attribute']['alias']] = ['label'=>$attribute['attribute']['title'], 'value'=>$attribute['value']];
                }
            }

            return $arrItem;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Count Items
     * @param  [Array]   $arrConfig  [Configuration wanted for the count]
     * @param  [Array]   $arrOptions [Query Options]
     * @return [Integer]             [Number of items]
     */
    public static function countItems($arrConfig, $arrOptions = array())
    {
        try {
            return ItemModel::countItems($arrConfig, $arrOptions);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Adjusts the way Portfolio items URLs are generated
     * (useful for i18nl10n plugin)
     *
     * @param Array $item | Item sent by module
     *
     * @return Array
     */
    public function getFrontendUrl($item)
    {
        $objCurrentItem = ItemModel::findByIdOrAlias(\Input::get('auto_item'));

        if (!$objCurrentItem) {
            return $item;
        }

        $objItem = ItemModel::findItems(["i18nl10n_id"=>$objCurrentItem->i18nl10n_id, "lang"=>$item["language"]], 1);
        global $objPage;
        
        // If no equivalent item, return nothing to hide the change language module
        if (!$objItem) {
            return [];
        }

        return array(
            'id'               => empty($row['id']) ? $objPage->id : $row['id'],
            'alias'            => $item['alias']."/".$row['alias'],
            'title'            => empty($row['title']) ? $objPage->title : $row['title'],
            'pageTitle'        => empty($row['pageTitle'])
                ? $objPage->pageTitle
                : $row['pageTitle'],
            'language'         => $item["language"],
            'isActive'         => $item["language"] === $GLOBALS['TL_LANGUAGE'],
            'forceRowLanguage' => true
        );
    }
}
