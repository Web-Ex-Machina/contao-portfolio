<?php

declare(strict_types=1);

/**
 * Contao Portfolio for Contao Open Source CMS
 * Copyright (c) 2015-2025 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-portfolio
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-portfolio/
 */

namespace WEM\PortfolioBundle\Model;

use Contao\Model\Registry;
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
     * @param mixed  $varId      The ID or code
     * @param string $locale     Enter a specific locale
     * @param array  $arrOptions An optional options array
     *
     * @return \Contao\Model|static model or null if the result is empty
     */
    public static function findByIdOrSlug(int|string $varId, string $locale = '', array $arrOptions = [])
    {
        $isCode = !preg_match('/^[1-9]\d*$/', (string) $varId);

        // Try to load from the registry
        if (!$isCode && [] === $arrOptions) {
            $objModel = Registry::getInstance()->fetch(static::$strTable, $varId);

            if (null !== $objModel) {
                return $objModel;
            }
        }

        $t = static::$strTable;
        $columns = $isCode ? [$t.'.slug=?'] : [$t.'.id=?'];
        $vars = [$varId];

        if ('' !== $locale) {
            $columns[] = $t.'.language=?';
            $vars[] = $locale;
        }

        $arrOptions = array_merge([
            'limit' => 1, 
            'column' => $columns, 
            'value' => $vars, 
            'return' => 'Model',
        ], $arrOptions);

        return static::find($arrOptions);
    }

    public static function findTranslation(int $pid, string $locale): ?PortfolioL10n
    {
        $objL10n = PortfolioL10n::findItems(['language' => $locale, 'pid' => $pid], 1);
        
        if (!$objL10n) {
            return null;
        }

        return $objL10n->current();
    }
}
