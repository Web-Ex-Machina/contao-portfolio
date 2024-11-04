<?php

declare(strict_types=1);

namespace WEM\PortfolioBundle\DataContainer;

use Contao\Backend;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\Input;

class ContentContainer extends Backend
{
    public function updatePalettes()
    {
        if ('wem_portfolio_feed' === Input::get('do')) {
            foreach ($GLOBALS['TL_DCA']['tl_content']['palettes'] as $key => $value) {
                if ($key == "__selector__" || $key == "default" ) {continue;}

                PaletteManipulator::create()
                    // apply the field "custom_field" after the field "username"
                    ->addLegend("language")
                    ->addField('wem_language', 'language')

                    // now the field is registered in the PaletteManipulator
                    // but it still has to be registered in the globals array:
                    ->applyToPalette($key, 'tl_content')
                ;
            }
        }
    }
}
