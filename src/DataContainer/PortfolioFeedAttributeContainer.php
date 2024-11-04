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

namespace WEM\PortfolioBundle\DataContainer;

use Contao\Backend;

class PortfolioFeedAttributeContainer extends Backend
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Format items list.
     */
    public function listItems(array $r): string
    {
        return \sprintf(
            '%s <span style="color:#888">[%s]</span>',
            $r['name'],
            $r['label']
        );
    }

    /**
     * Return a list of form fields.
     */
    public function getFieldOptions(): array
    {
        return ['text', 'textarea', 'select', 'picker', 'fileTree', 'listWizard'];
    }

    /**
     * Return a list of form fields.
     */
    public function getFieldsAndLegends(): array
    {
        $this->loadDataContainer('tl_wem_portfolio');
        $arrOptions = [];

        $strPalette = $GLOBALS['TL_DCA']['tl_wem_portfolio']['palettes']['default'];
        $arrChunks = explode(';', $strPalette);

        if ([] === $arrChunks) {
            return $arrOptions;
        }

        foreach ($arrChunks as $c) {
            $arrWidgets = explode(',', $c);

            if ([] === $arrWidgets) {
                continue;
            }

            foreach ($arrWidgets as $w) {
                if (str_contains($w, '_legend')) {
                    $arrOptions['legends'][] = trim(str_replace(['{', '}', '_hidden'], ['', '', ''], $w));
                    continue;
                }

                $arrOptions['fields'][] = trim($w);

                $arrSubfields = $this->getFieldsFromSubpalette($w);

                if ([] !== $arrSubfields) {
                    $arrOptions['fields'] = array_merge($arrOptions['fields'], $arrSubfields);
                }
            }
        }

        return $arrOptions;
    }

    /**
     * Retrieve fields from subpalette.
     */
    protected function getFieldsFromSubpalette(string $f): array
    {
        $arrFields = [];

        if (\array_key_exists('subpalettes', $GLOBALS['TL_DCA']['tl_wem_portfolio']) && \array_key_exists($f, $GLOBALS['TL_DCA']['tl_wem_portfolio']['subpalettes'])) {
            $arrSubfields = explode(',', $GLOBALS['TL_DCA']['tl_wem_portfolio']['subpalettes'][$f]);

            if ([] === $arrSubfields) {
                return $arrFields;
            }

            foreach ($arrSubfields as $s) {
                $arrFields[] = trim($s);

                $arrSubfields = $this->getFieldsFromSubpalette($s);

                if ([] !== $arrSubfields) {
                    $arrFields = array_merge($arrFields, $arrSubfields);
                }
            }
        }

        return $arrFields;
    }
}
