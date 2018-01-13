<?php

/**
 * Module Portfolio for Contao Open Source CMS
 *
 * Copyright (c) 2015-2018 Web ex Machina
 *
 * @author Web ex Machina <http://www.webexmachina.fr>
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
		try
		{
			$objItems = ItemModel::findItems($arrConfig, $intLimit, $intOffset, $arrOptions);

			if(!$objItems)
			{
				return;
			}

			$arrItems = array();

			while($objItems->next())
			{
				$arrItems[] = static::getItem($objItems->row(), $arrConfig["getItem"]);
			}

			return $arrItems;
		}
		catch(Exception $e)
		{
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
		try
		{
			if(is_object($varItem))
			{
				$arrItem = $varItem->row();
			}
			else if(is_array($varItem))
			{
				$arrItem = $varItem;
			}
			else
			{
				$sql = ItemModel::findByIdOrAlias($varItem);
				
				if(!$sql)
				{
					return;
				}
				else
				{
					$arrItem = $sql->row();
				}
			}

			// Parse item dates
			$arrDates = ["timestamp"=>$arrItem['created_on'], "date"=>\Date::parse(\Config::get('datimFormat'), $arrItem['created_on']), "datetime"=>\Date::parse('Y-m-d\TH:i:sP', $arrItem['created_on'])];
			$arrItem['created_on'] = $arrDates;
			$arrDates = ["timestamp"=>$arrItem['date'], "date"=>\Date::parse(\Config::get('datimFormat'), $arrItem['date']), "datetime"=>\Date::parse('Y-m-d\TH:i:sP', $arrItem['date'])];
			$arrItem['date'] = $arrDates;

			// Fetch item pictures
			if($arrItem['pictures'])
			{
				$arrPictures = deserialize($arrItem['pictures']);
				if(is_array($arrPictures) && count($arrPictures) > 0)
				{
					unset($arrItem['pictures']);
					foreach($arrPictures as $strUuid)
					{
						if($objFile = \FilesModel::findByUuid($strUuid))
						{
							$arrItem['pictures'][] = $objFile;
						}
					}
				}
			}

			// Get the item category
			if($arrConfig["getCategory"])
			{
				$arrItem['pid'] = Category::getItem($arrItem['pid'], $arrConfig['getCategoryConfig']);
			}

			// Get the item tags
			if($arrConfig["getTags"])
			{
				$arrTags = deserialize($arrItem['tags']);

				if(is_array($arrTags) && !empty($arrTags))
				{
					$arrItem['tags'] = [];

					foreach($arrTags as $intTag)
					{
						$arrItem['tags'][] = Tag::getItem($intTag, $arrConfig['getTagsConfig']);
					}
				}
			}

			// Get the item attributes
			if($arrConfig["getAttributes"])
			{
				$arrConfig["itemAttributes"]["pid"] = $arrItem['id'];
				$arrItem['attributes'] = ItemAttribute::getItems($arrConfig["itemAttributes"]);
			}

			return $arrItem;
		}
		catch(Exception $e)
		{
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
		try
		{
			return ItemModel::countItems($arrConfig, $arrOptions);
		}
		catch(Exception $e)
		{
			throw $e;
		}
	}
}