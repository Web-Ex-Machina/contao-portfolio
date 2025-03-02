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
use Contao\Model\Collection;
use WEM\PortfolioBundle\Model\PortfolioFeedAttribute;
use WEM\UtilsBundle\Classes\StringUtil;

class ModuleContainer extends Backend
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Return all templates as array.
     */
    public function getTemplates(): array
    {
        return $this->getTemplateGroup('wem_portfolio_item_');
    }

    /**
     * Return all feeds as array.
     */
    public function getFeeds(): array
    {
        $arrFeeds = [];
        $objFeeds = $this->Database->execute('SELECT id, title FROM tl_wem_portfolio_feed ORDER BY title');

        if (!$objFeeds || 0 === $objFeeds->count()) {
            return $arrFeeds;
        }

        while ($objFeeds->next()) {
            $arrFeeds[$objFeeds->id] = $objFeeds->title;
        }

        return $arrFeeds;
    }

    /**
     * Return all job alerts available gateways.
     */
    public function getFiltersOptions(): array
    {
        $this->loadDataContainer('tl_wem_portfolio');
        $fields = [];

        foreach ($GLOBALS['TL_DCA']['tl_wem_portfolio']['fields'] as $k => $v) {
            if (!empty($v['eval']) && true === $v['eval']['isFilter']) {
                $fields[$k] = \sprintf('%s (%s)', $v['label'][0] ?: $k, $k);
            }
        }

        return $fields;
    }

    /**
     * Return all portfolio attributes available.
     *
     * @throws \Exception
     */
    public function getAttributesOptions(): array
    {
        $arrPids = StringUtil::deserialize($this->activeRecord->wem_portfolio_feeds);
        $c = [];

        if (null !== $arrPids && !empty($arrPids)) {
            $c = ['pid' => $arrPids];
        }

        $objAttributes = PortfolioFeedAttribute::findItems($c);

        if (!$objAttributes instanceof Collection) {
            return [];
        }

        $fields = [];
        while ($objAttributes->next()) {
            $fields[$objAttributes->name] = \sprintf('%s (ID:%s)', $objAttributes->label ?: $objAttributes->name, $objAttributes->id);
        }

        return $fields;
    }

    /**
     * Return all feeds as array.
     */
    public function getFiltersModules(): array
    {
        $arrModules = [];
        $objModule = $this->Database->execute('SELECT id, name FROM tl_module WHERE type = "wem_portfolio_filters" ORDER BY name');

        if (!$objModule || 0 === $objModule->count()) {
            return $arrModules;
        }

        while ($objModule->next()) {
            $arrModules[$objModule->id] = $objModule->name;
        }

        return $arrModules;
    }
}
