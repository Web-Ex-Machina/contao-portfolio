<?php

/**
 * Module Portfolio for Contao Open Source CMS
 *
 * Copyright (c) 2015-2019 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

namespace WEM\Portfolio\Controller;

use \RuntimeException as Exception;

use WEM\Portfolio\Model\ItemAttribute 	as ItemAttributeModel;

/**
 * Class ItemAttribute - Handle Portfolio ItemAttributes functions
 */
class ItemAttribute extends \Controller
{
	/**
	 * Get Item Attributes
	 * @param  [Array]   $arrConfig  [Configuration wanted for the list]
	 * @param  [Integer] $intLimit   [Query Limit]
	 * @param  [Integer] $intOffset  [Query Offset]
	 * @param  [Array]   $arrOptions [Query Options]
	 * @return [Array]               [Items list as Array]
	 */
	public static function getItems($arrConfig, $intLimit = 0, $intOffset = 0, $arrOptions = array()){
		try{
			$objItems = ItemAttributeModel::findItems($arrConfig, $intLimit, $intOffset, $arrOptions);

			if(!$objItems)
				return;

			$arrItems = array();

			while($objItems->next())
				$arrItems[] = static::getItem($objItems->row(), $arrConfig["getItem"]);

			return $arrItems;
		}
		catch(Exception $e){
			throw $e;
		}
	}

	/**
	 * Get Item Attribute
	 * @param  [Mixed] $varItem   [ItemAttribute ID, Alias, Array or Object]
	 * @param  [Array] $arrConfig [ItemAttribute configuration]
	 * @return [Array]            [ItemAttribute data]
	 */
	public static function getItem($varItem, $arrConfig = array()){
		try{
			if(is_array($varItem))
				$arrItem = $varItem;
			else if($varItem instanceof ItemAttributeModel || $varItem = ItemAttributeModel::findByPk($varItem))
				$arrItem = $varItem->row();
			else
				return;

			$arrItem["attribute"] = Attribute::getItem($arrItem["attribute"]);

			return $arrItem;
		}
		catch(Exception $e){
			throw $e;
		}
	}

	/**
	 * Count Item Attributes
	 * @param  [Array]   $arrConfig  [Configuration wanted for the count]
	 * @param  [Array]   $arrOptions [Query Options]
	 * @return [Integer]             [Number of items]
	 */
	public static function countItems($arrConfig, $arrOptions = array()){
		try{
			return ItemAttributeModel::countItems($arrConfig, $arrOptions);
		}
		catch(Exception $e){
			throw $e;
		}
	}
}