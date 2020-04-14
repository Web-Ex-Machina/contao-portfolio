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

namespace WEM\PortfolioBundle\Widget;

use WEM\PortfolioBundle\Model\Attribute;
use WEM\PortfolioBundle\Model\Category;
use WEM\PortfolioBundle\Model\CategoryItem;
use WEM\PortfolioBundle\Model\Item;
use WEM\PortfolioBundle\Model\ItemAttribute;

class AttributeWizard extends \Widget
{
    /**
     * Submit user input.
     *
     * @var bool
     */
    protected $blnSubmitInput = true;

    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'be_widget';

    /**
     * Trim the values and add new languages if necessary.
     */
    public function validate(): void
    {
        // Get the items IDs sent and apply the current ID as their i18nl10n_id value
        $ids = $this->getPost($this->strName);
        $this->import('Database');
        $stdModel = \Model::getClassFromTable($this->strTable);

        if ($ids) {
            foreach ($ids as $id) {
                $objModel = $stdModel::findByPk($id);
                $objModel->tstamp = time();
                $objModel->i18nl10n_id = $this->activeRecord->id;
                $objModel->save();
            }
        }

        // Always save the current ID
        $this->varValue = $this->activeRecord->id;
    }

    /**
     * Generate the widget and return it as string.
     *
     * @return string
     */
    public function generate()
    {
        // First retrieve the categories of the current item
        $objItemCategories = CategoryItem::findItems(['item' => $this->activeRecord->id]);

        // Then retrieve all the attributes
        $objAttributes = Attribute::findItems();

        if (!$objAttributes || 0 === $objAttributes->count()) {
            return '<p class="tl_info">'.$GLOBALS['TL_LANG']['WEM']['PORTFOLIO']['noAttributes'].'</p>';
        }

        // List all the available attributes
        $arrAuthorizedAttributes = [];
        if ($objAttributes && 0 < $objAttributes->count()) {
            while ($objItemCategories->next()) {
                $attributes = $objItemCategories->getRelated('pid')->attributes;
                if (null === $attributes) {
                    continue;
                }

                $arrAuthorizedAttributes = array_merge($arrAuthorizedAttributes, deserialize($attributes));
            }
        }

        $arrAuthorizedAttributes = array_unique($arrAuthorizedAttributes);

        // Filter if we have restrictions per category
        $arrAttributes = [];
        while ($objAttributes->next()) {
            if (\in_array($objAttributes->id, $arrAuthorizedAttributes, true)) {
                $arrAttributes[] = $objAttributes->row();
            }
        }

        // Make sure there is at least an empty array
        if (empty($this->varValue) || !\is_array($this->varValue)) {
            $this->varValue = [];
        }

        // Get all available items for the lang
        $itemsOptions = '';
        $arrFields = [];
        foreach ($arrAttributes as $a) {
            // Try to find an existing value for this attribute/item
            $objItemAttribute = ItemAttribute::findItems(['pid' => $dc->activeRecord->id, 'attribute' => $a['id']], 1);

            $strField = sprintf(
                '<label for="%s">%s</label>',
                'ctrl_'.$this->strName.'_attribute_'.$a['id'],
                $a['title']
            );
            switch ($a['type']) {
                case 'select':
                    $options = '';
                    foreach (deserialize($a['options']) as $o) {
                        $options .= sprintf(
                            '<option value="%s"%s>%s</option>',
                            $o,
                            ($o === $objItemAttribute->value) ? ' selected' : '',
                            $o
                        );
                    }

                    $strField .= sprintf(
                        '<select name="%s" id="%s" class="tl_select tl_chosen">%s</select>',
                        $this->strName.'['.$a['id'].']',
                        'ctrl_'.$this->strName.'_attribute_'.$a['id'],
                        $options
                    );
                    break;

                default:
                    $strField .= sprintf(
                        '<input type="text" name="%s" id="%s" class="tl_text" value="%s" />',
                        $this->strName.'['.$a['id'].']',
                        'ctrl_'.$this->strName.'_attribute_'.$a['id'],
                        $objItemAttribute->value ?: ''
                    );
                    break;
            }

            $arrFields[] = '<div class="field row">'.$strField.'</div>';
        }

        $return = '
        <div id="ctrl_'.$this->strId.'" class="tl_attributeWizard">
            '.implode('', $arrFields).'
        </div>
        ';

        return $return;
    }
}

class_alias(AttributeWizard::class, 'AttributeWizard');
