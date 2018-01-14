<?php

/**
 * Module Portfolio for Contao Open Source CMS
 *
 * Copyright (c) 2015-2018 Web ex Machina
 *
 * @author Web ex Machina <http://www.webexmachina.fr>
 */

namespace WEM\Portfolio\Model;

use \RuntimeException as Exception;
use Contao\Model;

/**
 * Reads and writes items
 */
class Item extends Model
{
	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_wem_portfolio_item';

	/**
	 * Find items, depends on the arguments
	 * @param Array
	 * @param Int
	 * @param Int
	 * @param Array
	 * @return Collection
	 */
	public static function findItems($arrConfig = array(), $intLimit = 0, $intOffset = 0, array $arrOptions = array())
	{
		try
		{
			$t = static::$strTable;
			$arrColumns = static::formatColumns($arrConfig);
				
			if($intLimit > 0)
				$arrOptions['limit'] = $intLimit;

			if($intOffset > 0)
				$arrOptions['offset'] = $intOffset;

			if(!isset($arrOptions['order']))
				$arrOptions['order'] = "$t.sorting ASC";

			return static::findBy($arrColumns, null, $arrOptions);
		}
		catch(Exception $e)
		{
			throw $e;
		}
	}

	/**
	 * Count items, depends on the arguments
	 * @param Array
	 * @param Array
	 * @return Integer
	 */
	public static function countItems($arrConfig = array(), array $arrOptions = array())
	{
		try
		{
			$t = static::$strTable;
			$arrColumns = static::formatColumns($arrConfig);

			return static::countBy($arrColumns, null, $arrOptions);
		}
		catch(Exception $e)
		{
			throw $e;
		}
	}

	/**
	 * Format ItemModel columns
	 * @param  [Array] $arrConfig [Configuration to format]
	 * @return [Array]            [The Model columns]
	 */
	public static function formatColumns($arrConfig)
	{
		try
		{
			$t = static::$strTable;
			$arrColumns = array("published=1");

			if($arrConfig["page"])
				$arrColumns[] = "$t.pages = ". $arrConfig["page"];

			if($arrConfig["alias"])
				$arrColumns[] = "$t.alias = '". $arrConfig["alias"] ."'";

			if($arrConfig["not"])
				$arrColumns[] = $arrConfig["not"];

			return $arrColumns;
		}
		catch(Exception $e)
		{
			throw $e;
		}
	}
}