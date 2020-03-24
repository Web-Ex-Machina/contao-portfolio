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

class I18nl10nAssociatedLocationsWizard extends \Widget
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
    public function validate()
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
        $languages = \Verstaerker\I18nl10nBundle\Classes\I18nl10n::getInstance()->getAvailableLanguages(true, true);

        $this->import('Database');

        if (!$this->activeRecord->i18nl10n_lang) {
            return '<p class="tl_info">Veuillez sélectionner une langue pour cet item</p>';
        }

        // Make sure there is at least an empty array
        if (empty($this->varValue) || !\is_array($this->varValue)) {
            if (\count($languages) > 0) {
                $key = isset($languages[$GLOBALS['TL_LANGUAGE']]) ? $GLOBALS['TL_LANGUAGE'] : key($languages);
                $this->varValue = [$key => []];
            } else {
                return '<p class="tl_info">'.$GLOBALS['TL_LANG']['MSC']['metaNoLanguages'].'</p>';
            }
        }

        // Add the existing entries
        if (!empty($this->varValue)) {
            // Get all available items for the lang
            $stdModel = \Model::getClassFromTable($this->strTable);
            $objItems = $stdModel::findItems(['not_lang' => $this->activeRecord->i18nl10n_lang]);

            if (!$objItems || 0 === $objItems->count()) {
                return '<p class="tl_info">Aucune alternative existante trouvée</p>';
            }

            $itemsOptions = '';
            while ($objItems->next()) {
                $selected = ($objItems->i18nl10n_id === $this->activeRecord->id || $objItems->id === $this->activeRecord->i18nl10n_id) ? ' selected' : '';
                $itemsOptions .= '
                <option value="'.$objItems->id.'"'.$selected.'>'.$objItems->title.' ('.$objItems->i18nl10n_lang.')</option>
                ';
            }

            $return = '
            <div id="ctrl_'.$this->strId.'" class="tl_i18nl10nAssociatedLocationsWizard dcapicker">
                <select name="'.$this->strId.'[]" class="tl_select tl_chosen multiple" multiple>
                    <option value="">-</option>
                    '.$itemsOptions.'
                </select>
            </div>
            ';
        }

        return $return;
    }
}

class_alias(I18nl10nAssociatedLocationsWizard::class, 'I18nl10nAssociatedLocationsWizard');
