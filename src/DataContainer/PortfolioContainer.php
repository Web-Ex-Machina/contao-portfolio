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

namespace WEM\PortfolioBundle\DataContainer;

use Contao\Backend;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\System;
use Contao\Versions;
use WEM\PortfolioBundle\Model\Portfolio;
use WEM\PortfolioBundle\Model\PortfolioFeedAttribute;
use WEM\PortfolioBundle\Model\PortfolioL10n;
use WEM\UtilsBundle\Classes\StringUtil;

class PortfolioContainer extends Backend
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
        $arrLanguages = System::getContainer()->get('contao.intl.locales')->getLocales(null, false);
        $arrTranslations = [$arrLanguages[$r['language']]];
        $objTranslations = PortfolioL10n::findItems(['pid' => $r['id']]);
        if ($objTranslations) {
            while ($objTranslations->next()) {
                $arrTranslations[] = $arrLanguages[$objTranslations->language];
            }
        }

        return \sprintf(
            '%s <span style="color:#888">[%s]</span>',
            $r['title'],
            implode(', ', $arrTranslations)
        );
    }

    /**
     * Update DCA palettes and add custom attributes.
     *
     * @throws \Exception
     */
    public function updatePalettes(DataContainer $dc): void
    {
        if ($dc->id && 'edit' === Input::get('act')) {
            $objItem = Portfolio::findByPk($dc->id);
            $objAttributes = PortfolioFeedAttribute::findItems(['pid' => $objItem->pid]);

            if (!$objAttributes || 0 === $objAttributes->count()) {
                return;
            }

            $objPalette = PaletteManipulator::create();
            while ($objAttributes->next()) {
                if (false === strrpos($GLOBALS['TL_DCA']['tl_wem_portfolio']['palettes']['default'], (string) $objAttributes->name)) {
                    $objPalette->addField(
                        $objAttributes->name,
                        $objAttributes->insertInDca,
                        \constant(PaletteManipulator::class.'::'.$objAttributes->insertType)
                    );
                }
            }

            $objPalette->applyToPalette('default', 'tl_wem_portfolio');
        }
    }

    /**
     * @throws \Exception
     */
    public function generateSlug($varValue, DataContainer $dc): string
    {
        $aliasExists = fn (string $slug): bool => $this->Database->prepare('SELECT id FROM tl_wem_portfolio WHERE slug=? AND id!=?')->execute($slug, $dc->id)->numRows > 0;

        // Generate an alias if there is none
        if (!$varValue) {
            $varValue = System::getContainer()->get('contao.slug')->generate($dc->activeRecord->title, $dc->activeRecord->id, $aliasExists);
        } elseif ($aliasExists($varValue)) {
            throw new \Exception(\sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
        }

        return $varValue;
    }
}
