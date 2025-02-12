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

    /**
     * Get offer attributes as array.
     *
     * @param mixed|null $lang
     *
     * @throws \Exception
     *
     * @return array ['attribute_name'=>['label'=>$label, 'raw_value'=>$value,'human_readable_value'=>$human_readable_value]]
     */
    public function getAttributesFull($varAttributes = [], $lang = null, $forApi = false): array
    {
        $attributes = [];

        if (empty($varAttributes)) {
            $objAttributes = PortfolioFeedAttribute::findItems(['pid' => $this->pid]);
        } else {
            $objAttributes = PortfolioFeedAttribute::findItems(['pid' => $this->pid, 'name' => $varAttributes]);
        }

        if ($objAttributes && 0 < $objAttributes->count()) {
            $arrArticleData = $this->row();
            while ($objAttributes->next()) {
                if (\array_key_exists($objAttributes->name, $arrArticleData)) {
                    $varValue = $this->getAttributeValue($objAttributes->current(), $lang, $forApi);

                    $attributes[$objAttributes->name] = [
                        'label' => $objAttributes->current()->getL10nLabel('label'),
                        'raw_value' => $varValue,
                        'human_readable_value' => $varValue,
                    ];
                }
            }
        }

        return $attributes;
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

    /**
     * TODO : this fonction return too many different value type.
     *
     * @param mixed|null $lang
     *
     * @throws \Exception
     *
     * @return array|Collection|mixed|string|Portfolio|null
     */
    public function getAttributeValue($varAttribute, $lang = null, $forApi = false)
    {
        if ('string' === \gettype($varAttribute)) {
            $varAttribute = PortfolioFeedAttribute::findItems(['pid' => $this->pid, 'name' => $varAttribute], 1);
        }

        if (null === $varAttribute) {
            return null;
        }

        // If $l is null, retrieve current language
        if (null === $lang) {
            $r = System::getContainer()->get('request_stack')->getCurrentRequest();
            if (null !== $r) {
                $lang = $r->getLocale();
            }
        }

        switch ($varAttribute->type) {
            case 'select':
                $return = null;
                $arrArticleData = $this->row();
                $options = StringUtil::deserialize($varAttribute->options ?? []);

                if ($varAttribute->translatable) {
                    $objL10n = PortfolioFeedAttributeL10n::findItems(['language' => $lang, 'pid' => $varAttribute->id], 1);
                    $options = StringUtil::deserialize($objL10n->options ?? []);
                }

                if ($varAttribute->multiple) {
                    $arrArticleData[$varAttribute->name] = StringUtil::deserialize($arrArticleData[$varAttribute->name]);
                    $return = [];
                }

                foreach ($options as $option) {
                    if ($varAttribute->multiple && \is_array($arrArticleData[$varAttribute->name]) && \in_array($option['value'], $arrArticleData[$varAttribute->name], true)) {
                        $return[] = $option['label'];
                    } elseif (!$varAttribute->multiple && $option['value'] === $arrArticleData[$varAttribute->name]) {
                        $return = $option['label'];
                    }
                }

                if ($varAttribute->multiple) {
                    $return = implode(', ', $return);
                }

                return $return;

            case 'picker':
                return $this->getRelated($varAttribute->name);

            case 'fileTree':
                $figureBuilder = System::getContainer()
                    ->get('contao.image.studio')
                    ->createFigureBuilder()
                    ->setSize($this->size)
                    ->setLightboxGroupIdentifier('lb'.$this->id)
                    ->enableLightbox((bool) $this->fullsize)
                ;

                if ($varAttribute->multiple) {
                    $objFiles = FilesModel::findMultipleByUuids(StringUtil::deserialize($this->{$varAttribute->name}));

                    if (!$objFiles) {
                        return null;
                    }

                    $arrFiles = [];
                    while ($objFiles->next()) {
                        $figure = $figureBuilder
                            ->fromPath($objFiles->path)
                            ->build()
                        ;

                        $data = $figure->getLegacyTemplateData() ?: null;

                        if (null === $data) {
                            continue;
                        }

                        if ($forApi && is_array($data)) {
                            $data['picture']['img']['srcset'] = Environment::get('base') . $data['picture']['img']['srcset'];
                            $data['picture']['img']['src'] = Environment::get('base') . $data['picture']['img']['src'];
                            $data['singleSRC'] = Environment::get('base') . $data['singleSRC'];
                            $data['src'] = Environment::get('base') . $data['src'];
                        }

                        $arrFiles[] = $data;
                    }

                    return $arrFiles ?: null;
                }

                $data = $this->{$varAttribute->name};

                if (!is_array($data)) {
                    $objFile = FilesModel::findByUuid($data);

                    $figure = $figureBuilder
                        ->fromPath($objFile->path)
                        ->build()
                    ;

                    $data = $figure->getLegacyTemplateData() ?: null;
                }

                if ($forApi && is_array($data)) {
                    $data['picture']['img']['srcset'] = Environment::get('base') . $data['picture']['img']['srcset'];
                    $data['picture']['img']['src'] = Environment::get('base') . $data['picture']['img']['src'];
                    $data['singleSRC'] = Environment::get('base') . $data['singleSRC'];
                    $data['src'] = Environment::get('base') . $data['src'];
                }
                
                return $data;

            case 'listWizard':
                $varValue = StringUtil::deserialize($this->getL10nLabel($varAttribute->name, $lang));

                if (!$varValue) {
                    return '';
                }

                if (is_array($varValue)) {
                    return implode(', ', $varValue);
                }

                return $varValue;

            default:
                return $this->getL10nLabel($varAttribute->name, $lang);
        }
    }

    /**
     * Get offer attributes as array.
     *
     * @param mixed|null $lang
     *
     * @throws \Exception
     *
     * @return array ['attribute_label'=>$human_readable_value,...]
     */
    public function getAttributesSimple($varAttributes = [], $lang = null): array
    {
        $attributes = [];

        $objAttributes = PortfolioFeedAttribute::findItems(['pid' => $this->pid, 'name' => $varAttributes]);

        if ($objAttributes && 0 < $objAttributes->count()) {
            $arrArticleData = $this->row();
            while ($objAttributes->next()) {
                if (\array_key_exists($objAttributes->name, $arrArticleData)) {
                    $attributes[$objAttributes->name] = $this->getAttributeValue($objAttributes->current(), $lang);
                }
            }
        }

        return $attributes;
    }

    /**
     * Generate item url.
     *
     * @throws \Exception
     */
    public function getUrl(bool $blnAbsolute = false, string $lang = ''): ?string
    {
        $objFeed = $this->getRelated('pid');

        if (!$objFeed) {
            throw new \Exception(\sprintf('Cannot retrieve pid from item id %s', $this->id));
        }

        $objTarget = $objFeed->getRelated('jumpTo');

        if (!$objTarget) {
            return null;
        }

        // If $l is null, retrieve current language
        if ('' === $lang) {
            $r = System::getContainer()->get('request_stack')->getCurrentRequest();
            if (null !== $r) {
                $lang = $r->getLocale();
            }
        }

        $objPageData = (new PageFinder())->findAssociatedForLanguage($objTarget, $lang);
        $params = (Config::get('useAutoItem') ? '/' : '/items/').'category/'.$objFeed->alias.'/item/'.($this->getL10nLabel('slug', $lang) ?: $this->id);

        return $blnAbsolute ? $objPageData->getAbsoluteUrl($params) : $objPageData->getFrontendUrl($params);
    }

    public function getL10nLabel($f, $l = null)
    {
        // Set default value
        $label = $this->{$f};

        // If $l is null, retrieve current language
        if (null === $l) {
            $r = System::getContainer()->get('request_stack')->getCurrentRequest();
            if (null !== $r) {
                $l = $r->getLocale();
            }
        }

        // Try to retrieve a l10n entry for this pid and language
        $objL10n = PortfolioL10n::findItems(['language' => $l, 'pid' => $this->id], 1);

        // If there is no translation available, retrieve the current field
        if (null === $objL10n || null === $objL10n->{$f}) {
            return $label;
        }

        return $objL10n->{$f};
    }
}
