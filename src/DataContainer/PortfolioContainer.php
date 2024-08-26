<?php

declare(strict_types=1);

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
use WEM\UtilsBundle\Classes\StringUtil;

class PortfolioContainer extends Backend
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
            $r['title'],
            $r['slug']
        );
    }

    /**
     * Return the "toggle visibility" button.
     */
    public function toggleIcon(array $row, ?string $href, string $label, string $title, string $icon, string $attributes): string
    {
        if (!is_null(Input::get('tid')) && \strlen(Input::get('tid'))) {
            $this->toggleVisibility((int)Input::get('tid'), ('1' === Input::get('state')), (@func_get_arg(12) ?: null));
            $this->redirect($this->getReferer());
        }

        $href .= '&amp;tid=' . $row['id'] . '&amp;state=' . ($row['published'] ? '' : 1);

        if (!$row['published']) {
            $icon = 'invisible.svg';
        }

        return '<a href="' . $this->addToUrl($href) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label, 'data-state="' . ($row['published'] ? 1 : 0) . '"') . '</a> ';
    }

    /**
     * Disable/enable a job.
     *
     * @param DataContainer|null $dc
     */
    public function toggleVisibility(int $intId, bool $blnVisible, DataContainer $dc = null): void
    {
        // Set the ID and action
        Input::setGet('id', $intId);
        Input::setGet('act', 'toggle');

        if ($dc instanceof DataContainer) {
            $dc->id = $intId; // see #8043
        }

        // Trigger the onload_callback
        if (\is_array($GLOBALS['TL_DCA']['tl_wem_portfolio']['config']['onload_callback'])) {
            foreach ($GLOBALS['TL_DCA']['tl_wem_portfolio']['config']['onload_callback'] as $callback) {
                if (\is_array($callback)) {
                    $this->import($callback[0]);
                    $this->{$callback[0]}->{$callback[1]}($dc);
                } elseif (\is_callable($callback)) {
                    $callback($dc);
                }
            }
        }

        // Set the current record
        if ($dc instanceof DataContainer) {
            $objRow = $this->Database->prepare('SELECT * FROM tl_wem_portfolio WHERE id=?')
                ->limit(1)
                ->execute($intId);

            if ($objRow->numRows) {
                $dc->activeRecord = $objRow;
            }
        }

        $objVersions = new Versions('tl_wem_portfolio', $intId);
        $objVersions->initialize();

        // Trigger the save_callback
        if (\is_array($GLOBALS['TL_DCA']['tl_wem_portfolio']['fields']['published']['save_callback'])) {
            foreach ($GLOBALS['TL_DCA']['tl_wem_portfolio']['fields']['published']['save_callback'] as $callback) {
                if (\is_array($callback)) {
                    $this->import($callback[0]);
                    $blnVisible = $this->{$callback[0]}->{$callback[1]}($blnVisible, $dc);
                } elseif (\is_callable($callback)) {
                    $blnVisible = $callback($blnVisible, $dc);
                }
            }
        }

        $time = time();

        // Update the database
        $this->Database->prepare(sprintf("UPDATE tl_wem_portfolio SET tstamp=%d, published='", $time) . ($blnVisible ? '1' : '') . "' WHERE id=?")
            ->execute($intId);

        if ($dc instanceof DataContainer) {
            $dc->activeRecord->tstamp = $time;
            $dc->activeRecord->published = ($blnVisible ? '1' : '');
        }

        // Trigger the onsubmit_callback
        if (\is_array($GLOBALS['TL_DCA']['tl_wem_portfolio']['config']['onsubmit_callback'])) {
            foreach ($GLOBALS['TL_DCA']['tl_wem_portfolio']['config']['onsubmit_callback'] as $callback) {
                if (\is_array($callback)) {
                    $this->import($callback[0]);
                    $this->{$callback[0]}->{$callback[1]}($dc);
                } elseif (\is_callable($callback)) {
                    $callback($dc);
                }
            }
        }

        $objVersions->create();
    }

    /**
     * Update DCA palettes and add custom attributes
     *
     * @throws \Exception
     */
    public function updatePalettes(DataContainer $dc): void
    {
        if ($dc->id && 'edit' == Input::get('act')) {
            $objJob = Portfolio::findByPk($dc->id);
            $objAttributes = PortfolioFeedAttribute::findItems(['pid' => $objJob->pid]);

            if (!$objAttributes || 0 == $objAttributes->count()) {
                exit();
            }

            $objPalette = PaletteManipulator::create();
            while ($objAttributes->next()) {
                if (false === strrpos($GLOBALS['TL_DCA']['tl_wem_portfolio']['palettes']['default'], (string)$objAttributes->name)) {
                    $objPalette->addField(
                        $objAttributes->name,
                        $objAttributes->insertInDca,
                        constant(PaletteManipulator::class . '::' . $objAttributes->insertType)
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
        $aliasExists = fn(string $slug): bool => $this->Database->prepare('SELECT id FROM tl_wem_portfolio WHERE slug=? AND id!=?')->execute($slug, $dc->id)->numRows > 0;

        // Generate an alias if there is none
        if (!$varValue) {
            $varValue = System::getContainer()->get('contao.slug')->generate($dc->activeRecord->title, $dc->activeRecord->id, $aliasExists);
        } elseif ($aliasExists($varValue)) {
            throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
        }

        return $varValue;
    }
}
