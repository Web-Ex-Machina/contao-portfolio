<?php

declare(strict_types=1);

/**
 * Contao Portfolio for Contao Open Source CMS
 * Copyright (c) 2015-2023 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-portfolio
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-portfolio/
 */

namespace WEM\PortfolioBundle\Module;

use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\System;
use WEM\PortfolioBundle\Model\Item;

/**
 * Front end module "portfolio reader".
 *
 * @property array $portfolio_categories
 *
 * @author Web ex Machina <http://www.webexmachina.fr>
 */
class PortfolioReader extends Portfolio
{
    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'mod_wem_portfolio_reader';

    /**
     * Display a wildcard in the back end.
     *
     * @return string
     */
    public function generate()
    {
        $scopeMatcher = System::getContainer()->get('wem.scope_matcher');

        if ($scopeMatcher->isBackend()) {
            /** @var BackendTemplate|object $objTemplate */
            $objTemplate = new \BackendTemplate('be_wildcard');

            $objTemplate->wildcard = '### '.utf8_strtoupper($GLOBALS['TL_LANG']['FMD']['wem_portfolio_reader'][0]).' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id='.$this->id;

            return $objTemplate->parse();
        }

        // Set the item from the auto_item parameter
        if (!isset($_GET['items']) && \Config::get('useAutoItem') && isset($_GET['auto_item'])) {
            \Input::setGet('items', \Input::get('auto_item'));
        }

        // Do not index or cache the page if no news item has been specified
        if (!\Input::get('items')) {
            /* @var PageModel $objPage */
            global $objPage;

            $objPage->noSearch = 1;
            $objPage->cache = 0;

            return '';
        }

        return parent::generate();
    }

    /**
     * Generate the module.
     */
    protected function compile(): void
    {
        /* @var PageModel $objPage */
        global $objPage;

        $this->Template->articles = '';
        $this->Template->referer = 'javascript:history.go(-1)';
        $this->Template->back = $GLOBALS['TL_LANG']['MSC']['goBack'];

        // Get the portfolio item
        $arrConfig = [];
        $arrConfig['getCategory'] = true;
        $objItem = Item::findByIdOrAlias(\Input::get('items'));

        if (null === $objItem) {
            throw new PageNotFoundException('Page not found: '.\Environment::get('uri'));
        }

        $this->Template->articles = $this->parseItem($objItem->row(), $this->wem_portfolio_item_template);

        // Overwrite the page title (see #2853 and #4955)
        if ('' !== $objItem->title) {
            $objPage->pageTitle = strip_tags(\StringUtil::stripInsertTags($objItem->title));
        }

        // Overwrite the page description
        if ('' !== $objItem->teaser) {
            $objPage->description = $this->prepareMetaDescription($objItem->teaser);
        }
    }
}
