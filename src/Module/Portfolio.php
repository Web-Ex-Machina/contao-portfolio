<?php

declare(strict_types=1);

/**
 * Contao Portfolio for Contao Open Source CMS
 * Copyright (c) 2015-2020 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-portfolio
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-portfolio/
 */

namespace WEM\PortfolioBundle\Module;

use RuntimeException as Exception;
use WEM\PortfolioBundle\Controller\Item;
use WEM\PortfolioBundle\Model\Attribute;
use WEM\PortfolioBundle\Model\Category;
use WEM\PortfolioBundle\Model\CategoryItem;
use WEM\PortfolioBundle\Model\ItemAttribute;

/**
 * Handle generic Portfolio functions.
 *
 * @author Web ex Machina <http://www.webexmachina.fr>
 */
abstract class Portfolio extends \Module
{
    /**
     * Load generic stuff.
     *
     * @return string
     */
    public function generate()
    {
        return parent::generate();
    }

    /**
     * Parse an item.
     *
     * @param array
     * @param string
     *
     * @return string
     */
    public function parseItem($arrItem, $strTemplate = 'wem_portfolio_item_default', $strClass = '', $intCount = 0)
    {
        try {
            /* @var \PageModel $objPage */
            global $objPage;

            /** @var \FrontendTemplate|object $objTemplate */
            $objTemplate = new \FrontendTemplate($strTemplate);
            $objTemplate->setData($arrItem);
            $objTemplate->class = (('' !== $arrItem['cssClass']) ? ' '.$arrItem['cssClass'] : '').$strClass;
            $objTemplate->count = $intCount;

            // Add an image
            if ($arrItem['pictures'][0]) {
                $size = \StringUtil::deserialize($this->imgSize);

                if ($arrItem['pictures'][0]->imgSize || $size) {
                    if ($size[0] > 0 || $size[1] > 0 || is_numeric($size[2])) {
                        $arrArticle['size'] = $this->imgSize;
                    } elseif ($arrItem['pictures'][0]->imgSize) {
                        $arrArticle['size'] = $arrItem['pictures'][0]->imgSize;
                    }
                }

                $arrArticle['singleSRC'] = $arrItem['pictures'][0]['singleSRC'];
                $this->addImageToTemplate($objTemplate, $arrArticle, null, null, null);
            }

            // Parse the others images, in a easier way
            for ($i = 1; $i < \count($arrItem['pictures']); ++$i) {
                $strPath = $arrItem['pictures'][$i]['singleSRC'];
                if ($size || $arrItem['pictures'][$i]->imgSize) {
                    if ($size[0] > 0 || $size[1] > 0 || is_numeric($size[2])) {
                        $arrImages[$i] = \Image::get($strPath, $size[0], $size[1], $size[2]);
                    } elseif ($arrItem['pictures'][0]->imgSize) {
                        $imgSize = deserialize($arrItem['pictures'][0]->imgSize);
                        $arrImages[$i] = \Image::get($strPath, $imgSize[0], $imgSize[1], $imgSize[2]);
                    }
                } else {
                    $arrImages[$i] = $strPath;
                }
            }

            if ($arrImages) {
                $objTemplate->images = $arrImages;
            }

            $strContent = '';
            $objElement = \ContentModel::findPublishedByPidAndTable($arrItem['id'], 'tl_wem_portfolio_item');
            if (null !== $objElement) {
                while ($objElement->next()) {
                    $strContent .= $this->getContentElement($objElement->current());
                }
            }
            $objTemplate->text = $strContent;

            return $objTemplate->parse();
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Get a category with associated data.
     *
     * @param int $intId [Category ID]
     *
     * @return array [Category data, with picture and attributes]
     */
    protected function getCategory($intId)
    {
        try {
            $objCategory = Category::findByPk($intId);
            $r = $objCategory->row();

            // Load category picture
            if ($objFile = \FilesModel::findByUuid($r['picture'])) {
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
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Retrieve module filters.
     *
     * @return [Array] [Attributes available]
     */
    protected function getAvailableFilters()
    {
        try {
            $arrFilters = [];

            foreach (unserialize($this->wem_portfolio_filters) as $id) {
                $attribute = Attribute::findByPk($id);

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
                    if (\Input::post($attribute->alias) === $objItemAttributes->value || \Input::get($attribute->alias) === $objItemAttributes->value) {
                        $option['selected'] = 1;
                    }

                    $arrFilters[$attribute->alias]['options'][] = $option;
                }
            }

            return $arrFilters;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Parse multiple items.
     *
     * @param array
     *
     * @return string
     */
    protected function parseItems($arrItems, $strTemplate = 'wem_portfolio_item_default')
    {
        try {
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
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Apply logic to retrieve sorting rule.
     *
     * @param [String] Module sorting value
     *
     * @return [String] [Sorting value wanted]
     */
    protected function getSortingValue($strField)
    {
        switch ($strField) {
            default:
                return str_replace('_', ' ', $strField);
                break;
        }
    }
}
