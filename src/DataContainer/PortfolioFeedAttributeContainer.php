<?php

declare(strict_types=1);

namespace WEM\PortfolioBundle\DataContainer;

use Contao\Backend;

class PortfolioFeedAttributeContainer extends Backend
{
    public function __construct()
    {
        Parent::__construct();
    }

    /**
     * Format items list.
     */
    public function listItems(array $r): string
    {
        return sprintf(
            '%s <span style="color:#888">[%s]</span>',
            $r['name'],
            $r['label']
        );
    }

    /**
     * Return a list of form fields
     */
    public function getFieldOptions(): array
    {
        return ['text', 'select', 'picker', 'fileTree', 'listWizard'];
    }

    /**
     * Return a list of form fields
     */
    public function getFieldsAndLegends(): array
    {
        $this->loadDataContainer('tl_wem_portfolio');
        $arrOptions = [];

        $strPalette = $GLOBALS['TL_DCA']['tl_wem_portfolio']['palettes']['default'];
        $arrChunks = explode(';', $strPalette);

        if ($arrChunks === []) {
            return $arrOptions;
        }

        foreach ($arrChunks as $c) {
            $arrWidgets = explode(',', $c);

            if ($arrWidgets === []) {
                continue;
            }

            foreach ($arrWidgets as $w) {
                if (false !== strpos($w, "_legend")) {
                    $arrOptions['legends'][] = trim(str_replace(["{", "}", "_hidden"], ["", "", ""], $w));
                    continue;
                }

                $arrOptions['fields'][] = trim($w);

                $arrSubfields = $this->getFieldsFromSubpalette($w);

                if ($arrSubfields !== []) {
                    $arrOptions['fields'] = array_merge($arrOptions['fields'], $arrSubfields);
                }
            }
        }

        return $arrOptions;
    }

    /**
     * Retrieve fields from subpalette
     */
    protected function getFieldsFromSubpalette(string $f): array
    {
        $arrFields = [];

        if (array_key_exists('subpalettes', $GLOBALS['TL_DCA']['tl_wem_portfolio']) && array_key_exists($f, $GLOBALS['TL_DCA']['tl_wem_portfolio']['subpalettes'])) {
            $arrSubfields = explode(',', $GLOBALS['TL_DCA']['tl_wem_portfolio']['subpalettes'][$f]);

            if ($arrSubfields === []) {
                return $arrFields;
            }

            foreach ($arrSubfields as $s) {
                $arrFields[] = trim($s);

                $arrSubfields = $this->getFieldsFromSubpalette($s);

                if ($arrSubfields !== []) {
                    $arrFields = array_merge($arrFields, $arrSubfields);
                }
            }
        }

        return $arrFields;
    }
}