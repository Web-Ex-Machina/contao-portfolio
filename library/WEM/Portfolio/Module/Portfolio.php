<?php

/**
 * Module Portfolio for Contao Open Source CMS
 *
 * Copyright (c) 2015-2019 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

namespace WEM\Portfolio\Module;

use \RuntimeException as Exception;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Patchwork\Utf8;

use WEM\Portfolio\Controller\Item;
use WEM\Portfolio\Model\ItemAttribute;
use WEM\Portfolio\Model\Attribute;

/**
 * Handle generic Portfolio functions
 *
 * @author Web ex Machina <http://www.webexmachina.fr>
 */
abstract class Portfolio extends \Module
{
    /**
     * Retrieve module filters
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
                $objItemAttributes = ItemAttribute::findItems(['attribute'=>$id]);
                if (!$objItemAttributes || 0 == $objItemAttributes->count()) {
                    continue;
                }

                // Prepare the filter
                $arrFilters[$attribute->alias] = ['label'=>$attribute->title, 'options'=>[]];

                // Get the options
                $arrValues = array();
                while ($objItemAttributes->next()) {
                    // Skip if we already know this value
                    if (in_array($objItemAttributes->value, $arrValues)) {
                        continue;
                    }

                    // Store the value
                    $arrValues[] = $objItemAttributes->value;
                    
                    // Format the option
                    $option = ['value' => $objItemAttributes->value, 'text' => $objItemAttributes->value, 'selected'=>0];
                    if (\Input::post($attribute->alias) == $objItemAttributes->value || \Input::get($attribute->alias) == $objItemAttributes->value) {
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
     * Parse multiple items
     * @param Array
     * @return String
     */
    protected function parseItems($arrItems, $strTemplate = "wem_portfolio_item")
    {
        try {
            $limit = count($arrItems);
            if ($limit < 1) {
                return array();
            }

            $count = 0;
            $arrElements = array();
            foreach ($arrItems as $arrItem) {
                $arrElements[] = $this->parseItem($arrItem, $strTemplate, ((++$count == 1) ? ' first' : '') . (($count == $limit) ? ' last' : '') . ((($count % 2) == 0) ? ' odd' : ' even'), $count);
            }
            
            return $arrElements;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Parse an item
     * @param Array
     * @param String
     * @return String
     */
    public function parseItem($arrItem, $strTemplate = "wem_portfolio_item", $strClass = '', $intCount = 0)
    {
        try {
            /** @var \PageModel $objPage */
            global $objPage;
            
            /** @var \FrontendTemplate|object $objTemplate */
            $objTemplate = new \FrontendTemplate($strTemplate);
            $objTemplate->setData($arrItem);
            $objTemplate->class = (($arrItem['cssClass'] != '') ? ' ' . $arrItem['cssClass'] : '') . $strClass;
            $objTemplate->count = $intCount;

            // Build the item's link
            if ($this->jumpTo instanceof \PageModel) {
                $objTemplate->link = $this->jumpTo->getFrontendUrl("/".$arrItem['alias']);
            }

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
            for ($i=1; $i<count($arrItem['pictures']); $i++) {
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
            if ($objElement !== null) {
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
}
