<?php

namespace WEM\PortfolioBundle\EventListener\DataContainer\Content;

use Contao\ContentModel;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Input;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsCallback(table: 'tl_content', target: 'config.onload')]
class UpdatePalettesCallback
{
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function __invoke(DataContainer|null $dc = null): void
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