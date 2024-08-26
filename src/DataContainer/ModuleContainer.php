<?php

declare(strict_types=1);

/**
 * Contao Job Portfolios for Contao Open Source CMS
 * Copyright (c) 2018-2020 Web ex Machina.
 *
 * @category ContaoBundle
 *
 * @author   Web ex Machina <contact@webexmachina.fr>
 *
 * @see     https://github.com/Web-Ex-Machina/contao-job-portfolios/
 */

namespace WEM\PortfolioBundle\DataContainer;

use Contao\Backend;
use WEM\PortfolioBundle\Model\PortfolioFeedAttribute;
use WEM\UtilsBundle\Classes\StringUtil;

class ModuleContainer extends Backend
{
    public function __construct()
    {
        Parent::__construct();
    }

    /**
     * Return all templates as array.
     */
    public function getTemplates(): array
    {
        return $this->getTemplateGroup('portfolio_');
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
            // if (!empty($v['eval']) && true === $v['eval']['wemportfolios_isAvailableForFilters']) {
            if (!empty($v['eval']) && true === $v['eval']['isFilter']) {
                $fields[$k] = $v['label'][0] ?: $k;
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
        $arrPids = StringUtil::deserialize($this->activeRecord->portfolio_feeds);
        $c = [];

        if (null !== $arrPids && !empty($arrPids)) {
            $c = ['pid' => $arrPids];
        }

        $objAttributes = PortfolioFeedAttribute::findItems($c);

        if (!$objAttributes) {
            return [];
        }

        $fields = [];
        while ($objAttributes->next()) {
            $fields[$objAttributes->name] = $objAttributes->label ?: $objAttributes->name;
        }

        return $fields;
    }

    /**
     * Return all feeds as array.
     */
    public function getFiltersModules(): array
    {
        $arrModules = [];
        $objModule = $this->Database->execute('SELECT id, name FROM tl_module WHERE type = "offersfilters" ORDER BY name');

        if (!$objModule || 0 === $objModule->count()) {
            return $arrModules;
        }

        while ($objModule->next()) {
            $arrModules[$objModule->id] = $objModule->name;
        }

        return $arrModules;
    }
}
