<?php

declare(strict_types=1);

namespace WEM\PortfolioBundle\DataContainer;

use Contao\Backend;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\Input;
use WEM\PortfolioBundle\Model\PortfolioL10n;
use WEM\PortfolioBundle\Model\PortfolioFeedAttribute;

class PortfolioL10nContainer extends Backend
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
            $r['language'],
            $r['title']
        );
    }

    /**
     * Update DCA palettes and add custom attributes
     *
     * @throws \Exception
     */
    public function updatePalettes($dc): void
    {
        if ($dc->id && 'edit' == Input::get('act')) {
            $objItem = PortfolioL10n::findByPk($dc->id);
            $objAttributes = PortfolioFeedAttribute::findItems(['pid' => $objItem->getRelated('pid')->pid]);

            if (!$objAttributes || 0 == $objAttributes->count()) {
                return;
            }

            $objPalette = PaletteManipulator::create();
            while ($objAttributes->next()) {
                if ($objAttributes->translatable && false === strrpos($GLOBALS['TL_DCA']['tl_wem_portfolio_l10n']['palettes']['default'], (string) $objAttributes->name)) {
                    $objPalette->addField(
                        $objAttributes->name
                    );
                }
            }

            $objPalette->applyToPalette('default', 'tl_wem_portfolio_l10n');
        }
    }
}
