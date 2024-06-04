<?php

declare(strict_types=1);

namespace WEM\PortfolioBundle\DataContainer;

use Contao\Backend;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\Database;
use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\System;
use Contao\Versions;
use WEM\PortfolioBundle\Model\Category;
use WEM\PortfolioBundle\Model\CategoryItem;
use WEM\UtilsBundle\Classes\StringUtil;


class PortfolioItem extends Backend
{
    /**
     * Add an image to each item in the tree.
     *
     * @param array $row
     * @param string $label
     * @param DataContainer|null $dc
     * @param string $imageAttribute
     * @param bool $blnReturnImage
     * @param bool $blnProtected
     *
     * @return string
     */
    public function addIcon(
        array $row, string $label, DataContainer $dc = null, string $imageAttribute = '',
        bool  $blnReturnImage = false, bool $blnProtected = false): string //TODO : many useless var no ?
    {
        return '<img src="assets/contao/images/iconJPG.svg" width="18" height="18" alt="image/jpeg" style="margin-right:3px"><span style="vertical-align:-1px">'.$label.'</span>';
    }

    /**
     * Add an icon to access categories sorting.
     *
     * @param DataContainer $dc
     *
     * @return array  Categories DCA
     * @throws \Exception
     */
    public function getCategories(DataContainer $dc): array
    {
        $objCategories = Category::findItems();

        if (!$objCategories || 0 === $objCategories->count()) {
            return [];
        }

        System::loadLanguageFile('tl_wem_portfolio_category');

        $arrData = [];
        while ($objCategories->next()) {
            $strTitle = sprintf($GLOBALS['TL_LANG']['tl_wem_portfolio_category']['items'][1], $objCategories->id);
            $strHref = sprintf(
                'contao?do=wem_portfolio_category&table=tl_wem_portfolio_category_item&id=%s&popup=1&rt=%s&ref=%s',
                $objCategories->id,
                REQUEST_TOKEN,
                Input::get('ref')
            );

            $href = sprintf(
                ' <a href="%s" title="%s" onclick="%s"><img src="%s" alt="%s" /></a>',
                $strHref,
                $strTitle,
                "Backend.openModalIframe({'title':'".$strTitle."','url':this.href});return false",
                'bundles/wemportfolio/portfolio_16.png',
                $strTitle
            );

            $arrData[$objCategories->id] = $objCategories->title.$href;
        }

        return $arrData;
    }

    /**
     * Save item categories in the child table
     * ci stands for CategoryItem.
     *
     * @param $varValue
     * @param $dc
     * @return mixed [Array] [Understandable values]
     * @throws \Exception
     */
    public function saveCategories($varValue, $dc)
    {
        if ($varValue) {
            $arrCategories = StringUtil::deserialize($varValue);
            $objCategoryItems = CategoryItem::findItems(['item' => $dc->id]);

            if ($objCategoryItems && 0 < $objCategoryItems->count()) {
                while ($objCategoryItems->next()) {
                    if (!\in_array($objCategoryItems->pid, $arrCategories, true)) {
                        $objCategoryItems->delete();
                    }
                }
            }


            foreach ($arrCategories as $c) {
                $lastSorting = CategoryItem::findItems(['pid' => $c], 1);
                $intSorting = $lastSorting->sorting ?: 0;
                
                $ci = CategoryItem::findItems(['item' => $dc->activeRecord->id, 'pid' => $c], 1);

                // If we did not found an CategoryItem, create it
                if (!$ci) {
                    $intSorting += 256;

                    $ci = new CategoryItem();
                    $ci->createdAt = time();
                    $ci->sorting = $intSorting;
                    $ci->pid = $c;
                    $ci->item = $dc->activeRecord->id;
                }

                $ci->tstamp = time();
                $ci->save();
            }
        } else {
            Database::getInstance()->prepare('DELETE FROM tl_wem_portfolio_category_item WHERE item = ?')->execute($dc->activeRecord->id);
        }

        return $varValue;
    }

    /**
     * Auto-generate an article alias if it has not been set yet.
     *
     * @param $varValue
     * @param DataContainer $dc
     * @return string
     * @throws \Exception
     */
    public function generateAlias($varValue, DataContainer $dc): string
    {
        $autoAlias = false;

        // Generate an alias if there is none
        if ('' === $varValue) {
            $autoAlias = true;
            $slugOptions = [];

            $varValue = System::getContainer()->get('contao.slug.generator')->generate(StringUtil::prepareSlug($dc->activeRecord->title), $slugOptions);

            // Prefix numeric aliases (see #1598)
            if (is_numeric($varValue)) {
                $varValue = 'id-'.$varValue;
            }
        }

        $objAlias = $this->Database->prepare('SELECT id FROM tl_wem_portfolio_item WHERE id=? OR alias=?')
                                   ->execute($dc->id, $varValue)
        ;

        // Check whether the page alias exists
        if ($objAlias->numRows > 1) {
            if (!$autoAlias) {
                throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
            }

            $varValue .= '-'.$dc->id;
        }

        return $varValue;
    }

    /**
     * Return the "toggle visibility" button.
     *
     * @param array $row
     * @param string $href
     * @param string $label
     * @param string $title
     * @param string $icon
     * @param string $attributes
     *
     * @return string
     */
    public function toggleIcon(array  $row, string $href,
                               string $label, string $title, string $icon, string $attributes): string
    {
        if (Input::get('tid') && \strlen(Input::get('tid'))) {
            // TODO : check if is ok, Input::get return a string toggleVisibility want int, i added cast to int.
            $this->toggleVisibility((int)Input::get('tid'), (1 === Input::get('state')), (@func_get_arg(12) ?: null));
            $this->redirect($this->getReferer());
        }

        $href .= '&amp;tid='.$row['id'].'&amp;state='.($row['published'] ? '' : 1);

        if (!$row['published']) {
            $icon = 'invisible.svg';
        }

        return '<a href="'.$this->addToUrl($href).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label, 'data-state="'.($row['published'] ? 1 : 0).'"').'</a> ';
    }

    /**
     * Disable/enable a user group.
     *
     * @param int $intId
     * @param bool $blnVisible
     * @param DataContainer|null $dc
     *
     * @throw AccessDeniedException
     */
    public function toggleVisibility(int $intId, bool $blnVisible, DataContainer $dc = null): void
    {
        // Set the ID and action
        Input::setGet('id', $intId);
        Input::setGet('act', 'toggle');

        if ($dc) {
            $dc->id = $intId;
        } // see #8043

        // Trigger the onload_callback
        if (\is_array($GLOBALS['TL_DCA']['tl_wem_portfolio_item']['config']['onload_callback'])) {
            foreach ($GLOBALS['TL_DCA']['tl_wem_portfolio_item']['config']['onload_callback'] as $callback) {
                if (\is_array($callback)) {
                    $this->import($callback[0]);
                    $this->{$callback[0]}->{$callback[1]}($dc);
                } elseif (\is_callable($callback)) {
                    $callback($dc);
                }
            }
        }

        // Check the field access
        if (!$this->User->hasAccess('tl_wem_portfolio_item::published', 'alexf')) {
            throw new AccessDeniedException('Not enough permissions to publish/unpublish article ID "' . $intId . '".');
        }
        // TODO : deprecated hasAccess

        // Set the current record
        if ($dc) {
            $objRow = $this->Database->prepare('SELECT * FROM tl_wem_portfolio_item WHERE id=?')->limit(1)->execute($intId);

            if ($objRow->numRows) {
                $dc->activeRecord = $objRow;
            }
        }

        $objVersions = new Versions('tl_wem_portfolio_item', $intId);
        $objVersions->initialize();

        // Trigger the save_callback
        if (\is_array($GLOBALS['TL_DCA']['tl_wem_portfolio_item']['fields']['published']['save_callback'])) {
            foreach ($GLOBALS['TL_DCA']['tl_wem_portfolio_item']['fields']['published']['save_callback'] as $callback) {
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
        $this->Database->prepare("UPDATE tl_wem_portfolio_item SET tstamp=$time, published='".($blnVisible ? '1' : '')."' WHERE id=?")->execute($intId);

        if ($dc) {
            $dc->activeRecord->tstamp = $time;
            $dc->activeRecord->published = ($blnVisible ? '1' : '');
        }

        // Trigger the onsubmit_callback
        if (\is_array($GLOBALS['TL_DCA']['tl_wem_portfolio_item']['config']['onsubmit_callback'])) {
            foreach ($GLOBALS['TL_DCA']['tl_wem_portfolio_item']['config']['onsubmit_callback'] as $callback) {
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
}
