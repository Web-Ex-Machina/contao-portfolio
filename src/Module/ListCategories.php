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

namespace WEM\PortfolioBundle\Module;

use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\ModuleModel;
use Patchwork\Utf8;
use RuntimeException as Exception;
use WEM\PortfolioBundle\Model\Category;

/**
 * Front end module "wem_portfolio_list_categories".
 */
class ListCategories extends Portfolio
{
    /**
     * List of categories.
     *
     * @var array
     */
    protected $arrCategories = [];

    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'mod_wem_portfolio_list_categories';

    /**
     * Display a wildcard in the back end.
     *
     * @return string
     */
    public function generate()
    {
        if (TL_MODE === 'BE') {
            /** @var BackendTemplate|object $objTemplate */
            $objTemplate = new \BackendTemplate('be_wildcard');

            $objTemplate->wildcard = '### '.Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['wem_portfolio_list_categories'][0]).' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id='.$this->id;

            return $objTemplate->parse();
        }

        // Check if we have an existing category
        if (\Input::get('auto_item') && $objCategory = Category::findByIdOrAlias(\Input::get('auto_item'))) {
            $objModel = ModuleModel::findByPk($this->wem_portfolio_list_module);

            if (!$objModel) {
                throw new PageNotFoundException('Page not found: '.\Environment::get('uri'));
            }

            $objModel->wem_portfolio_categories = serialize([0 => $objCategory->id]);
            $objModule = new PortfolioList($objModel);

            return $objModule->generate();
        }

        if (\Input::get('auto_item')) {
            return '';
        }

        return parent::generate();
    }

    /**
     * Parse an item.
     *
     * @param array
     * @param string
     *
     * @return string
     */
    public function parseItem($arrItem, $strTemplate = 'wem_portfolio_category_default', $strClass = '', $intCount = 0)
    {
        try {
            /* @var \PageModel $objPage */
            global $objPage;

            /** @var \FrontendTemplate|object $objTemplate */
            $objTemplate = new \FrontendTemplate($strTemplate);
            $objTemplate->setData($arrItem);
            $objTemplate->class = (('' !== $arrItem['cssClass']) ? ' '.$arrItem['cssClass'] : '').$strClass;
            $objTemplate->count = $intCount;

            // Add an image
            if ($arrItem['picture']) {
                $arrArticle['singleSRC'] = $arrItem['picture']['path'];

                // Override the default image size
                if ('' !== $this->imgSize) {
                    $size = \StringUtil::deserialize($this->imgSize);

                    if ($size[0] > 0 || $size[1] > 0 || is_numeric($size[2]) || '_' === ($size[2][0] ?? null)) {
                        $arrArticle['size'] = $this->imgSize;
                    }
                }

                $this->addImageToTemplate($objTemplate, $arrArticle);
            }

            // Generate a link to the items list of this category
            $objTemplate->link = $objPage->getFrontendUrl('/'.$arrItem['alias']);

            return $objTemplate->parse();
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Generate the module.
     */
    protected function compile(): void
    {
        try {
            $limit = null;
            $offset = (int) ($this->skipFirst);
            $arrOptions = [];
            $arrOptions['order'] = $this->getSortingValue($this->wem_portfolio_category_sort);
            $bundles = \System::getContainer()->getParameter('kernel.bundles');

            // Maximum number of items
            if ($this->numberOfItems > 0) {
                $limit = $this->numberOfItems;
            }

            $this->Template->articles = [];
            $this->Template->rt = \RequestToken::get();
            $this->Template->request = \Environment::get('request');
            $this->Template->empty = $GLOBALS['TL_LANG']['WEM']['PORTFOLIO']['empty'];

            global $objPage;
            $arrConfig = [];

            // If i18nl10n bundle is active, add the current language as filter
            if (\array_key_exists('VerstaerkerI18nl10nBundle', $bundles)) {
                $arrConfig['lang'] = $GLOBALS['TL_LANGUAGE'];
            }

            // Get the total number of items
            $intTotal = Category::countItems($arrConfig, $arrOptions);

            if ($intTotal < 1) {
                return;
            }

            $total = $intTotal - $offset;

            // Split the results
            if ($this->perPage > 0 && (!isset($limit) || $this->numberOfItems > $this->perPage)) {
                // Adjust the overall limit
                if (isset($limit)) {
                    $total = min($limit, $total);
                }

                // Get the current page
                $id = 'page_n'.$this->id;
                $page = (null !== \Input::get($id)) ? \Input::get($id) : 1;

                // Do not index or cache the page if the page number is outside the range
                if ($page < 1 || $page > max(ceil($total / $this->perPage), 1)) {
                    throw new PageNotFoundException('Page not found: '.\Environment::get('uri'));
                }

                // Set limit and offset
                $limit = $this->perPage;
                $offset += (max($page, 1) - 1) * $this->perPage;
                $skip = (int) ($this->skipFirst);

                // Overall limit
                if ($offset + $limit > $total + $skip) {
                    $limit = $total + $skip - $offset;
                }

                // Add the pagination menu
                $objPagination = new \Pagination($total, $this->perPage, \Config::get('maxPaginationLinks'), $id);
                $this->Template->pagination = $objPagination->generate("\n  ");
            }

            $objItems = Category::findItems($arrConfig, ($limit ?: 0), $offset, $arrOptions);
            $arrItems = [];

            while ($objItems->next()) {
                $arrItems[] = $this->getCategory($objItems->id);
            }

            // Add the articles
            if (null !== $arrItems) {
                $this->Template->items = $this->parseItems($arrItems, $this->wem_portfolio_category_template);
            }

            $this->Template->raw_items = $arrItems;

            //dump($arrItems);
        } catch (Exception $e) {
            if (\Input::post('TL_AJAX')) {
                $arrResponse = ['status' => 'error', 'error' => $e->getMessage(), 'trace' => $e->getTrace()];
                echo json_encode($arrResponse);
                die;
            }
            $this->Template->error = true;
            $this->Template->message = $e->getMessage();
            $this->Template->trace = $e->getTrace();
        }
    }
}
