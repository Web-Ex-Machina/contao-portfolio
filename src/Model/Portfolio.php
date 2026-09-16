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

use Contao\Config;
use Contao\Controller;
use Contao\Date;
use Contao\Environment;
use Contao\FilesModel;
use Contao\Model\Collection;
use Contao\Model\Registry;
use Contao\System;
use Terminal42\ChangeLanguage\PageFinder;
use WEM\UtilsBundle\Classes\StringUtil;
use WEM\UtilsBundle\Model\Model;

/**
 * Reads and writes items.
 */
class Portfolio extends Model
{
    /**
     * Search fields.
     *
     * @var array<string>
     */
    public static $arrSearchFields = ['slug', 'title', 'teaser'];

    /**
     * Table name.
     *
     * @var string
     */
    protected static $strTable = 'tl_wem_portfolio';

    /**
     * Count items, depends on the arguments.
     */
    public static function countItems(array $arrConfig = [], array $arrOptions = []): int
    {
        $arrColumns = static::formatColumns($arrConfig);

        if ([] === $arrColumns) {
            return static::countAll();
        }

        return static::countBy($arrColumns, null, $arrOptions);
    }

    /**
     * Format ItemModel columns.
     *
     * @return array [The Model columns]
     */
    public static function formatColumns(array $arrConfig): array
    {
        $arrColumns = [];

        foreach ($arrConfig as $c => $v) {
            $arrColumns = array_merge($arrColumns, static::formatStatement($c, $v));
        }

        return $arrColumns;
    }

    /**
     * Generic statements format.
     *
     * @param string $strField    [Column to format]
     * @param mixed  $varValue    [Value to use]
     * @param string $strOperator [Operator to use, default "="]
     */
    public static function formatStatement(string $strField, $varValue, string $strOperator = '='): array
    {
        $arrColumns = [];
        $t = static::$strTable;
        Controller::loadDatacontainer($t);
        switch ($strField) {
            // Search by pid
            case 'pid':
                if (\is_array($varValue)) {
                    $arrColumns[] = $t.'.pid IN('.implode(',', array_map('\intval', $varValue)).')';
                } else {
                    $arrColumns[] = $t.'.pid = '.$varValue;
                }

                break;

                // Search for recipient not present in the subtable lead
            case 'published':
                if (1 === $varValue) {
                    $time = Date::floorToMinute();
                    $arrColumns[] = \sprintf("(%s.start='' OR %s.start<='%s') AND (%s.stop='' OR %s.stop>'", $t, $t, $time, $t, $t).($time + 60).\sprintf("') AND %s.published='1'", $t);
                }

                break;

                // Wizard for active items
            case 'active':
                if (1 === $varValue) {
                    $arrColumns[] = \sprintf('%s.published = 1 AND (%s.start = 0 OR %s.start <= ', $t, $t, $t).time().\sprintf(') AND (%s.stop = 0 OR %s.stop >= ', $t, $t).time().')';
                } elseif (-1 === $varValue) {
                    $arrColumns[] = \sprintf("%s.published = '' AND (%s.start = 0 OR %s.start >= ", $t, $t, $t).time().\sprintf(') AND (%s.stop = 0 OR %s.stop <= ', $t, $t).time().')';
                }

                break;

            case 'language':
                $arrColumns[] = '('.$t.'.language = "'.$varValue.'" OR '.$t.'.id IN (SELECT pid FROM tl_wem_portfolio_l10n AS twpl WHERE twpl.language = "'.$varValue.'"))';
                break;

                // Load parent
            default:
                if (\array_key_exists($strField, $GLOBALS['TL_DCA'][$t]['fields'])) {
                    switch ($GLOBALS['TL_DCA'][$t]['fields'][$strField]['inputType']) {
                        case 'select':
                            if (isset($GLOBALS['TL_DCA'][$t]['fields'][$strField]['eval']['multiple'])) {
                                $varValue = \is_array($varValue) ? $varValue : [$varValue];
                                $arrSubColumns = [];

                                foreach ($varValue as $subValue) {
                                    $arrSubColumns[] = \sprintf(\sprintf('%s.%s LIKE \'%%%%;s:%%s:"%%s";%%%%\'', $t, $strField), \strlen($subValue), $subValue);
                                }

                                $arrColumns[] = '('.implode(' OR ', $arrSubColumns).')';
                            } else {
                                $arrColumns[] = \sprintf("%s.%s = '%s'", $t, $strField, $varValue);
                            }

                            break;

                        case 'listWizard':
                            $varValue = \is_array($varValue) ? $varValue : [$varValue];
                            $arrSubColumns = [];
                            foreach ($varValue as $subValue) {
                                $arrSubColumns[] = \sprintf(\sprintf('%s.%s LIKE \'%%%%;s:%%s:"%%s";%%%%\'', $t, $strField), \strlen($subValue), $subValue);
                            }

                            $arrColumns[] = '('.implode(' AND ', $arrSubColumns).')';
                            break;

                        default:
                            $arrColumns = array_merge($arrColumns, parent::formatStatement($strField, $varValue, $strOperator));
                    }
                } else {
                    $varValue = \is_array($varValue) ? $varValue : [$varValue];

                    $arrColumns = array_merge($arrColumns, parent::formatStatement($strField, $varValue, $strOperator));
                }
        }

        return $arrColumns;
    }

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

    /**
     * Find items, depends on the arguments.
     *
     * @return Model|Collection|null
     */
    public static function findItems(
        array $arrConfig = [], int $intLimit = 0,
        int $intOffset = 0, array $arrOptions = []
    ): ?Collection {
        $t = static::$strTable;
        $arrColumns = static::formatColumns($arrConfig);

        if ($intLimit > 0) {
            $arrOptions['limit'] = $intLimit;
        }

        if ($intOffset > 0) {
            $arrOptions['offset'] = $intOffset;
        }

        if (!isset($arrOptions['order'])) {
            $arrOptions['order'] = $t.'.title ASC';
        }

        if ([] === $arrColumns) {
            return static::findAll($arrOptions);
        }

        return static::findBy($arrColumns, null, $arrOptions);
    }

    public function getFeedAttribute(string $field): PortfolioFeedAttribute
    {
        $objFeedAttribute = PortfolioFeedAttribute::findItems(['pid' => $this->pid, 'name' => $field], 1);

        return $objFeedAttribute->current();
    }

    public function getAttributeValues(string $field): array
    {
        $objFeedAttribute = $this->getFeedAttribute($field);
        $strFTable = array_first(explode(".", $objFeedAttribute->fkey));

        $objValues = PortfolioAttribute::findItems([
            'pid' => $this->id,
            'ptable' => Portfolio::getTable(),
            'ftable' => $strFTable, 
            'fcolumn' => $objFeedAttribute->syncFcolumn
        ]);

        $arrValues = [];
        if ($objValues && 0 < $objValues->count()) {
            while ($objValues->next()) {
                $arrValues[] = $objValues->{$objFeedAttribute->getSyncColumn()};
            }
        }

        return $arrValues;
    }

    public function saveAttributeValues(string $field, mixed $values): void
    {
        $objFeedAttribute = $this->getFeedAttribute($field);
        $strFTable = array_first(explode(".", $objFeedAttribute->fkey));

        // If the attribute is multiple, we want to save values in different db rows
        // Else, build a fake array to standardize the following process
        if ($objFeedAttribute->multiple) {
            $values = StringUtil::deserialize($values);
        }

        if (null === $values) {
            $arrValues = [];
        } else if (!is_array($values)) {
            $arrValues = [$values];
        } else {
            $arrValues = $values;
        }

        if (!is_array($arrValues)) {
            throw new Exception("Values to sync are not an array");
        }

        $arrFields = [
            'createdAt' => time(),
            'ptable' => Portfolio::getTable(),
            'ftable' => $strFTable,
            'fcolumn' => $objFeedAttribute->syncFcolumn,
        ];

        $arrWheres = [
            'ptable' => Portfolio::getTable(),
            'ftable' => $strFTable,
            'fcolumn' => $objFeedAttribute->syncFcolumn,
        ];

        $ds = System::getContainer()->get('wem.utils.service.data_synchronizer');
        $ds->syncData(
            $arrValues, 
            PortfolioAttribute::getTable(),
            $this->id,
            'pid',
            $objFeedAttribute->getSyncColumn(),
            [],
            $arrFields,
            $arrWheres,
        );

        if ($objFeedAttribute->syncWithSubtable) {
            foreach ($arrValues as $val) {
                $arrValues2 = [];
                $objValues = PortfolioAttribute::findItems([
                    'pid' => $val,
                    'ptable' => Portfolio::getTable(),
                    'ftable' => $strFTable,
                    'fcolumn' => $objFeedAttribute->syncFcolumn
                ]);

                if ($objValues && 0 < $objValues->count()) {
                    while ($objValues->next()) {
                        $arrValues2[] = $objValues->{$objFeedAttribute->getSyncColumn()};
                    }
                }

                if (!in_array($this->{$objFeedAttribute->syncFcolumn}, $arrValues2)) {
                    $arrValues2[] = $this->{$objFeedAttribute->syncFcolumn};
                }

                $ds->syncData(
                    $arrValues2,
                    PortfolioAttribute::getTable(),
                    $val,
                    'pid',
                    $objFeedAttribute->getSyncColumn(),
                    [],
                    $arrFields,
                    $arrWheres,
                );
            }
        }
    }
}

