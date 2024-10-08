<?php

declare(strict_types=1);

namespace WEM\PortfolioBundle\Model;

use Contao\Controller;
use Contao\Date;
use Contao\FilesModel;
use Contao\Model\Collection;
use Contao\Model\Registry;
use Contao\PageModel;
use Contao\System;
use Exception;
use WEM\UtilsBundle\Classes\StringUtil;
use WEM\UtilsBundle\Model\Model;

/**
 * Reads and writes items.
 */
class Portfolio extends Model
{
    /**
     * Search fields.
     * @var array<string>
     */
    public static array $arrSearchFields = ['slug', 'title', 'teaser'];

    /**
     * Table name.
     *
     * @var string
     */
    protected static $strTable = 'tl_wem_portfolio';

    /**
     * @var mixed|null
     */
    private $title;

    /**
     * Count items, depends on the arguments.
     */
    public static function countItems(array $arrConfig = [], array $arrOptions = []): int
    {
        $arrColumns = static::formatColumns($arrConfig);

        if ($arrColumns === []) {
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

        $arrConfig['lang'] = System::getContainer()->get('request_stack')->getCurrentRequest()->getLocale();

        foreach ($arrConfig as $c => $v) {
            $arrColumns = array_merge($arrColumns, static::formatStatement($c, $v));
        }

        return $arrColumns;
    }

    /**
     * Generic statements format.
     *
     * @param string $strField [Column to format]
     * @param mixed $varValue [Value to use]
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
                    $arrColumns[] = $t . '.pid IN(' . implode(',', array_map('\intval', $varValue)) . ')';
                } else {
                    $arrColumns[] = $t . '.pid = ' . $varValue;
                }

                break;

            // Search by country
            case 'country':
                $arrColumns[] = $t . ".countries LIKE '%%" . $varValue . "%'";
                break;

            // Search for recipient not present in the subtable lead
            case 'published':
                if (1 === $varValue) {
                    $time = Date::floorToMinute();
                    $arrColumns[] = sprintf("(%s.start='' OR %s.start<='%s') AND (%s.stop='' OR %s.stop>'", $t, $t, $time, $t, $t) . ($time + 60) . sprintf("') AND %s.published='1'", $t);
                }

                break;

            // Wizard for active items
            case 'active':
                if (1 === $varValue) {
                    $arrColumns[] = sprintf('%s.published = 1 AND (%s.start = 0 OR %s.start <= ', $t, $t, $t) . time() . sprintf(') AND (%s.stop = 0 OR %s.stop >= ', $t, $t) . time() . ')';
                } elseif (-1 === $varValue) {
                    $arrColumns[] = sprintf("%s.published = '' AND (%s.start = 0 OR %s.start >= ", $t, $t, $t) . time() . sprintf(') AND (%s.stop = 0 OR %s.stop <= ', $t, $t) . time() . ')';
                }

                break;

            // Load parent
            default:
                if (array_key_exists($strField, $GLOBALS['TL_DCA'][$t]['fields'])) {
                    switch ($GLOBALS['TL_DCA'][$t]['fields'][$strField]['inputType']) {
                        case 'select':
                            if (isset($GLOBALS['TL_DCA'][$t]['fields'][$strField]['eval']['multiple']))  {
                                $varValue = is_array($varValue) ? $varValue : [$varValue];
                                $arrSubColumns = [];

                                foreach ($varValue as $subValue) {
                                    $arrSubColumns[] = sprintf(sprintf('%s.%s LIKE \'%%%%;s:%%s:"%%s";%%%%\'', $t, $strField), strlen($subValue), $subValue);
                                }

                                $arrColumns[] = '(' . implode(' OR ', $arrSubColumns) . ')';
                            } else {
                                $arrColumns[] = sprintf("%s.%s = '%s'", $t, $strField, $varValue);
                            }

                            break;

                        case 'listWizard':
                            $varValue = is_array($varValue) ? $varValue : [$varValue];
                            $arrSubColumns = [];
                            foreach ($varValue as $subValue) {
                                $arrSubColumns[] = sprintf(sprintf('%s.%s LIKE \'%%%%;s:%%s:"%%s";%%%%\'', $t, $strField), strlen($subValue), $subValue);
                            }

                            $arrColumns[] = '(' . implode(' AND ', $arrSubColumns) . ')';
                            break;

                        default:
                            $arrColumns = array_merge($arrColumns, parent::formatStatement($strField, $varValue, $strOperator));
                    }
                } else {
                    $varValue = is_array($varValue) ? $varValue : [$varValue];

                    $arrColumns = array_merge($arrColumns, parent::formatStatement($strField, $varValue, $strOperator));
                }
        }

        return $arrColumns;
    }

    /**
     * Find a single record by its ID or code
     *
     * @param mixed $varId The ID or code
     * @param array $arrOptions An optional options array
     *
     * @return \Contao\Model|static model or null if the result is empty
     */
    public static function findByIdOrSlug($varId, array $arrOptions = [])
    {
        $isCode = !preg_match('/^[1-9]\d*$/', $varId);

        // Try to load from the registry
        if (!$isCode && $arrOptions === []) {
            $objModel = Registry::getInstance()->fetch(static::$strTable, $varId);

            if ($objModel !== null) {
                return $objModel;
            }
        }

        $t = static::$strTable;

        $arrOptions = array_merge
        (
            ['limit' => 1, 'column' => $isCode ? [$t . '.slug=?'] : [$t . '.id=?'], 'value' => $varId, 'return' => 'Model'],
            $arrOptions
        );

        return static::find($arrOptions);
    }


    /**
     * Get offer attributes as array
     * @return array ['attribute_name'=>['label'=>$label, 'raw_value'=>$value,'human_readable_value'=>$human_readable_value]]
     * @throws \Exception
     */
    public function getAttributesFull($varAttributes = []): array
    {
        $attributes = [];

        $objAttributes = PortfolioFeedAttribute::findItems(['pid' => $this->pid, 'name' => $varAttributes]);

        if ($objAttributes && 0 < $objAttributes->count()) {
            $arrArticleData = $this->row();
            while ($objAttributes->next()) {
                if (array_key_exists($objAttributes->name, $arrArticleData)) {
                    $varValue = $this->getAttributeValue($objAttributes->current());

                    $attributes[$objAttributes->name] = [
                        'label' => $objAttributes->label,
                        'raw_value' => $varValue,
                        'human_readable_value' => $varValue
                    ];
                }
            }
        }

        return $attributes;
    }

    /**
     * Find items, depends on the arguments.
     *
     *
     * @return Model|Collection|null
     */
    public static function findItems(
        array $arrConfig = [], int $intLimit = 0,
        int   $intOffset = 0, array $arrOptions = []
    ): ?Collection
    {
        $t = static::$strTable;
        $arrColumns = static::formatColumns($arrConfig);

        if ($intLimit > 0) {
            $arrOptions['limit'] = $intLimit;
        }

        if ($intOffset > 0) {
            $arrOptions['offset'] = $intOffset;
        }

        if (!isset($arrOptions['order'])) {
            $arrOptions['order'] = $t . '.createdAt DESC';
        }

        if ($arrColumns === []) {
            return static::findAll($arrOptions);
        }

        return static::findBy($arrColumns, null, $arrOptions);
    }

    /**
     * TODO : this fonction return too many different value type
     * @param mixed $varAttribute
     * @return array|Collection|mixed|string|Portfolio|null
     * @throws \Exception
     */
    public function getAttributeValue($varAttribute)
    {
        if ("string" === gettype($varAttribute)) {
            $varAttribute = PortfolioFeedAttribute::findItems(['pid' => $this->pid, 'name' => $varAttribute], 1);
        }

        if (null === $varAttribute) {
            return null;
        }

        switch ($varAttribute->type) {
            case "select":
                $return = null;
                $arrArticleData = $this->row();
                $options = StringUtil::deserialize($varAttribute->options ?? []);

                if ($varAttribute->multiple) {
                    $arrArticleData[$varAttribute->name] = StringUtil::deserialize($arrArticleData[$varAttribute->name]);
                    $return = [];
                }

                $arrArticleData = $this->row();
                foreach ($options as $option) {
                    if ($varAttribute->multiple && is_array($arrArticleData[$varAttribute->name]) && in_array($option['value'], $arrArticleData[$varAttribute->name])) {
                        $return[] = $option['label'];
                    } elseif (!$varAttribute->multiple && $option['value'] === $arrArticleData[$varAttribute->name]) {
                        $return = $option['label'];
                    }
                }

                if ($varAttribute->multiple) {
                    $return = implode(", ", $return);
                }

                return $return;

            case "picker":
                return $this->getRelated($varAttribute->name);

            case "fileTree":
                $figureBuilder = System::getContainer()
                    ->get('contao.image.studio')
                    ->createFigureBuilder()
                    ->setSize($this->size)
                    ->setLightboxGroupIdentifier('lb' . $this->id)
                    ->enableLightbox((bool)$this->fullsize);

                if ($varAttribute->multiple) {
                    $objFiles = FilesModel::findMultipleByUuids(StringUtil::deserialize($this->{$varAttribute->name}));

                    if (!$objFiles) {
                        return null;
                    }

                    $arrFiles = [];
                    while ($objFiles->next()) {
                        $figure = $figureBuilder
                            ->fromPath($objFiles->path)
                            ->build();

                        $arrFiles[] = $figure->getLegacyTemplateData();
                    }

                    return $arrFiles ?: null;
                }

                $objFile = FilesModel::findByUuid($this->{$varAttribute->name});

                $figure = $figureBuilder
                    ->fromPath($objFile->path)
                    ->build();

                return $figure->getLegacyTemplateData() ?: null;

            case "listWizard":
                return $this->{$varAttribute->name} ? implode(',', StringUtil::deserialize($this->{$varAttribute->name})) : '';

            default:
                return $this->{$varAttribute->name};
        }
    }

    /**
     * Get offer attributes as array
     * @return array ['attribute_label'=>$human_readable_value,...]
     * @throws \Exception
     */
    public function getAttributesSimple($varAttributes = []): array
    {
        $attributes = [];

        $objAttributes = PortfolioFeedAttribute::findItems(['pid' => $this->pid, 'name' => $varAttributes]);

        if ($objAttributes && 0 < $objAttributes->count()) {
            $arrArticleData = $this->row();
            while ($objAttributes->next()) {
                if (array_key_exists($objAttributes->name, $arrArticleData)) {
                    $attributes[$objAttributes->name] = $this->getAttributeValue($objAttributes->current());
                }
            }
        }

        return $attributes;
    }


    /**
     * Generate item url
     * @throws Exception
     */
    public function getUrl(bool $blnAbsolute = false): string
    {
        $objCategory = $this->getRelated('pid');

        $objPage = PageModel::findByPk($objCategory->jumpTo);
        // TODO : deprecated getAbsoluteUrl getFrontendUrl in 5.3 removed in 6
        return $blnAbsolute ? $objPage->getAbsoluteUrl('/' . $this->slug) : $objPage->getFrontendUrl('/' . $this->slug);
    }
}
