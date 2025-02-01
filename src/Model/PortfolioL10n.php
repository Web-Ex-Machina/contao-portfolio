<?php

declare(strict_types=1);

/**
 * Contao Portfolio for Contao Open Source CMS
 * Copyright (c) 2015-2024 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-portfolio
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-portfolio/
 */

namespace WEM\PortfolioBundle\Model;

use WEM\UtilsBundle\Model\Model;

/**
 * Reads and writes items.
 */
class PortfolioL10n extends Model
{
    /**
     * Table name.
     *
     * @var string
     */
    protected static $strTable = 'tl_wem_portfolio_l10n';

    /**
     * Find a single record by its ID or code.
     *
     * @param mixed $varId      The ID or code
     * @param array $arrOptions An optional options array
     *
     * @return \Contao\Model|static model or null if the result is empty
     */
    public static function findByIdOrSlug(string $varId, array $arrOptions = [])
    {
        $isCode = !preg_match('/^[1-9]\d*$/', $varId);

        // Try to load from the registry
        if (!$isCode && [] === $arrOptions) {
            $objModel = Registry::getInstance()->fetch(static::$strTable, $varId);

            if (null !== $objModel) {
                return $objModel;
            }
        }

        $t = static::$strTable;

        $arrOptions = array_merge(
            ['limit' => 1, 'column' => $isCode ? [$t.'.slug=?'] : [$t.'.id=?'], 'value' => $varId, 'return' => 'Model'],
            $arrOptions
        );

        return static::find($arrOptions);
    }
}
