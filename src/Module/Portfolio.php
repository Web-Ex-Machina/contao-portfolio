<?php

declare(strict_types=1);

/**
 * Contao Portfolio for Contao Open Source CMS
 * Copyright (c) 2015-2023 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-portfolio
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-portfolio/
 */

namespace WEM\PortfolioBundle\Module;

use Contao\Config;
use Contao\ContentModel;
use Contao\Date;
use Contao\FilesModel;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\Module;
use Contao\RequestToken;
use Contao\System;
use Exception;
use WEM\PortfolioBundle\Model\Attribute;
use WEM\PortfolioBundle\Model\Category;
use WEM\PortfolioBundle\Model\CategoryItem;
use WEM\PortfolioBundle\Model\Item;
use WEM\PortfolioBundle\Model\ItemAttribute;
use WEM\UtilsBundle\Classes\StringUtil;

/**
 * Handle generic Portfolio functions.
 *
 * @author Web ex Machina <http://www.webexmachina.fr>
 */
abstract class Portfolio extends Module
{
    /**
     * Load generic stuff.
     *
     * @return string
     * @throws Exception
     */
    public function generate(): string
    {
        if (Input::post('TL_AJAX') && $this->id === (int) Input::post('moduleId')) {
            $this->handleAjaxRequests();
        }

        return parent::generate();
    }

    /**
     * Parse an item.
     *
     * @param array $arrItem
     * @param string $strTemplate
     * @param string $strClass
     * @param int $intCount
     * @return string
     * @throws Exception
     */
    public function parseItem(array $arrItem, string $strTemplate = 'wem_portfolio_item_default', string $strClass = '', int $intCount = 0): string
    {
        $objItem = Item::findByPk($arrItem['id']);

        // Parse dates
        $arrDates = [
            'timestamp' => $arrItem['createdAt'],
            'date' => Date::parse(Config::get('dateFormat'), $arrItem['createdAt']),
            'time' => Date::parse(Config::get('timeFormat'), $arrItem['createdAt']),
            'datim' => Date::parse(Config::get('datimFormat'), $arrItem['createdAt']),
            'datetime' => Date::parse('Y-m-d\TH:i:sP', $arrItem['createdAt'])
        ];
        $arrItem['createdAt'] = $arrDates;
        $arrDates = [
            'timestamp' => $arrItem['date'],
            'date' => Date::parse(Config::get('dateFormat'), $arrItem['date']),
            'time' => Date::parse(Config::get('timeFormat'), $arrItem['date']),
            'datim' => Date::parse(Config::get('datimFormat'), $arrItem['date']),
            'datetime' => Date::parse('Y-m-d\TH:i:sP', $arrItem['date'])
        ];
        $arrItem['date'] = $arrDates;

        // Fetch item pictures
        $arrItem['pictures'] = $objItem->getPictures();

        // Load item categories
        $objCategories = $objItem->getRelated('categories');
        $arrItem['categories'] = $objCategories ? $objCategories->fetchAll() : null;

        // Generate item link
        $arrItem['link'] = $objItem->getUrl();

        // Get the item attributes
        $objAttributes = $objItem->getAttributes();
        if ($objAttributes) {
            $arrItem['attributes'] = [];
            while($objAttributes->next()) {
                $objAttribute = $objAttributes->getRelated('attribute');
                $arrItem['attributes'][$objAttribute->alias] = ['label' => $objAttribute->title, 'value' => $objAttributes->value];
            }
        }

        // Resize pictures
        if ($arrItem['pictures']) {
            $objImageLibrary = System::getContainer()->get('contao.image.image_factory');
            $imgSize = $arrItem['size'] ?: null;

            // Override the default image size
            if ($this->imgSize) {
                $size = StringUtil::deserialize($this->imgSize);

                if ($size[0] > 0 || $size[1] > 0 || is_numeric($size[2]) || ($size[2][0] ?? null) === '_')
                {
                    $imgSize = $this->imgSize;
                }
            }

            foreach ($arrItem['pictures'] as &$p) {
                try {
                    $rootDir = System::getContainer()->getParameter('kernel.project_dir');
                    $p['path'] = str_replace($rootDir . '/', '', $objImageLibrary->create(
                        $rootDir . '/' . $p['singleSRC'],
                        $imgSize
                    )->getPath());
                } catch(Exception $e) {

                }
            }
        }

        /** @var FrontendTemplate|object $objTemplate */
        $objTemplate = new FrontendTemplate($strTemplate);
        $objTemplate->setData($arrItem);
        $objTemplate->class = (('' !== $arrItem['cssClass']) ? ' '.$arrItem['cssClass'] : '').$strClass;
        $objTemplate->count = $intCount;

        $strContent = '';
        $objElement = ContentModel::findPublishedByPidAndTable($arrItem['id'], 'tl_wem_portfolio_item');
        if (null !== $objElement) {
            while ($objElement->next()) {
                $strContent .= $this->getContentElement($objElement->current());
            }
        }
        $objTemplate->text = $strContent;

        return $objTemplate->parse();
    }

    /**
     * Get a category with associated data.
     *
     * @param int $intId [Category ID]
     *
     * @return array [Category data, with picture and attributes]
     * @throws Exception
     */
    protected function getCategory(int $intId): array
    {
        $objCategory = Category::findByPk($intId);
        $r = $objCategory->row();

        // Load category picture
        if ($objFile = FilesModel::findByUuid($r['picture'])) {
            $r['picture'] = $objFile->row();
        } else {
            $r['picture'] = null;
        }

        // Load category attributes
        $objAttributes = $objCategory->getRelated('attributes');

        if (!$objAttributes || 0 === $objAttributes->count()) {
            $r['attributes'] = null;
        } else {
            $r['attributes'] = [];
            while ($objAttributes->next()) {
                $r['attributes'][] = $objAttributes->row();
            }
        }

        // Get the number of items
        $r['nbItems'] = CategoryItem::countItems(['pid' => $intId]);

        return $r;
    }

    /**
     * Retrieve module filters.
     *
     * @return array
     * @throws Exception
     */
    protected function getAvailableFilters(): array
    {
        $arrFilters = [];

        foreach (StringUtil::deserialize($this->wem_portfolio_filters) as $id) {
            switch ($id) {
                case 'category':
                    // Get the filter options & skip if there is no options available
                    $arrCategories = StringUtil::deserialize($this->wem_portfolio_categories);
                    if (!$arrCategories || 0 === count($arrCategories)) {
                        continue;
                    }

                    // Prepare the filter
                    $arrFilters[$id] = ['label' => 'Catégorie', 'options' => []];

                    // Get the options
                    foreach($arrCategories as $c) {
                        $objCategory = Category::findByPk($c);

                        // Format the option
                        $option = ['value' => $objCategory->alias, 'text' => $objCategory->title, 'selected' => 0];
                        if (Input::post($id) === $objCategory->alias || Input::get($id) === $objCategory->alias) {
                            $option['selected'] = 1;
                        }

                        $arrFilters[$id]['options'][] = $option;
                    }
                break;

                default:
                    $attribute = Attribute::findByIdOrAlias($id);

                    if (!$attribute) {
                        continue;
                    }

                    // Get the filter options & skip if there is no options available
                    $objItemAttributes = ItemAttribute::findItems(['attribute' => $id]);
                    if (!$objItemAttributes || 0 === $objItemAttributes->count()) {
                        continue;
                    }

                    // Prepare the filter
                    $arrFilters[$attribute->alias] = ['id' => $attribute->id, 'label' => $attribute->title, 'options' => []];

                    // Get the options
                    $arrValues = [];
                    while ($objItemAttributes->next()) {
                        // Skip if we already know this value
                        if (\in_array($objItemAttributes->value, $arrValues, true)) {
                            continue;
                        }

                        // Store the value
                        $arrValues[] = $objItemAttributes->value;

                        // Format the option
                        $option = ['value' => $objItemAttributes->value, 'text' => $objItemAttributes->value, 'selected' => 0];
                        if (Input::post($attribute->alias) === $objItemAttributes->value || Input::get($attribute->alias) === $objItemAttributes->value) {
                            $option['selected'] = 1;
                        }

                        $arrFilters[$attribute->alias]['options'][] = $option;
                    }
            }
        }

        return $arrFilters;
    }

    /**
     * Parse multiple items.
     *
     * @param array $arrItems
     * @param string $strTemplate
     * @return array
     * @throws Exception
     */
    protected function parseItems(array $arrItems, string $strTemplate = 'wem_portfolio_item_default'): array
    {
        $limit = \count($arrItems);
        if ($limit < 1) {
            return [];
        }

        $count = 0;
        $arrElements = [];
        foreach ($arrItems as $arrItem) {
            $arrElements[] = $this->parseItem($arrItem, $strTemplate, ((1 === ++$count) ? ' first' : '').(($count === $limit) ? ' last' : '').((0 === ($count % 2)) ? ' odd' : ' even'), $count);
        }

        return $arrElements;
    }

    /**
     * Apply logic to retrieve sorting rule.
     *
     * @param [String] Module sorting value
     *
     * @return array|string|string[] [String] [Sorting value wanted]
     */
    protected function getSortingValue($strField)
    {
        return str_replace('_', ' ', $strField);
    }

    /**
     * Handle AJAX requests.
     *
     * @return void
     * @throws Exception
     */
    protected function handleAjaxRequests(): void
    {
        try {
            switch (Input::post('action')) {
                case 'getItems':
                    $arrConfig = ['published' => 1];
                    $arrConfig['categories'] = StringUtil::deserialize($this->wem_portfolio_categories);

                    // Catch category post and transform it into array
                    if (Input::post('category')) {
                        $arrConfig['categories'] = [Input::post('category')];
                    }

                    // Check we have an array of IDs
                    if (Input::post('categories')) {
                        $arrConfig['categories'] = [];
                        foreach (Input::post('categories') as $k => $v) {
                            if (!is_int($v)) {
                                $objCategory = Category::findByIdOrAlias($v);
                                $arrConfig['categories'][$k] = $objCategory->id;
                            }
                        }
                    }

                    $objItems = Item::findItems($arrConfig, (Input::post('limit') ?: 0), Input::post('offset') ?: 0, Input::post('options') ?: []);
                    $strBuffer = '';
                    if (null !== $objItems) {
                        $strBuffer = $this->parseItems($objItems->fetchAll(), Input::post('template') ?: $this->wem_portfolio_item_template);
                    }

                    $arrResponse = ['status' => 'success', 'html' => $strBuffer];
                break;
                case 'getItem':
                    $objItem = Item::findByIdOrAlias(Input::post('item'));
                    $strBuffer = '';
                    if(null !== $objItem) {
                        $strBuffer = $this->parseItem($objItem->row(), Input::post('template') ?: $this->wem_portfolio_item_template);
                    }

                    $arrResponse = ['status' => 'success', 'html' => $strBuffer];
                break;
                
                default:
                    throw new Exception(sprintf($GLOBALS['TL_LANG']['WEMPORTFOLIO']['ERROR']['unknownAjaxRequest'], Input::post('action')));
            }
        } catch (Exception $e) {
            $arrResponse = ['status' => 'error', 'msg' => $e->getMessage(), 'trace' => $e->getTrace()];
        }

        // TODO : RequestToken deprecated
        $arrResponse['token'] = RequestToken::get();
        echo json_encode($arrResponse);
    }
}
