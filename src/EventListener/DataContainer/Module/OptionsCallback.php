<?php

namespace WEM\PortfolioBundle\EventListener\DataContainer\Module;

use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\Database;
use Contao\DataContainer;
use WEM\PortfolioBundle\Model\PortfolioFeed;
use WEM\PortfolioBundle\Model\PortfolioFeedAttribute;
use WEM\UtilsBundle\Classes\StringUtil;

class OptionsCallback
{

    public function __construct(
    ) { 
    }

    #[AsCallback(table: 'tl_module', target: 'fields.wem_portfolio_template.options')]
    public function getTemplates(DataContainer|null $dc = null): array
    {
        return Controller::getTemplateGroup('wem_portfolio_item_');
    }

    #[AsCallback(table: 'tl_module', target: 'fields.wem_portfolio_feeds.options')]
    public function getFeeds(DataContainer|null $dc = null): array
    {
        $arrFeeds = [];
        $objFeeds = PortfolioFeed::findAll();

        if (!$objFeeds || 0 === $objFeeds->count()) {
            return $arrFeeds;
        }

        while ($objFeeds->next()) {
            $arrFeeds[$objFeeds->id] = $objFeeds->title;
        }

        return $arrFeeds;
    }

    #[AsCallback(table: 'tl_module', target: 'fields.wem_portfolio_filters.options')]
    public function getFiltersOptions(DataContainer|null $dc = null): array
    {
        Controller::loadDataContainer('tl_wem_portfolio');
        $fields = [];

        foreach ($GLOBALS['TL_DCA']['tl_wem_portfolio']['fields'] as $k => $v) {
            if (!empty($v['eval']) && array_key_exists('isFilter', $v) && true === $v['eval']['isFilter']) {
                $fields[$k] = \sprintf('%s (%s)', $v['label'][0] ?: $k, $k);
            }
        }

        return $fields;
    }

    #[AsCallback(table: 'tl_module', target: 'fields.wem_portfolio_attributes.options')]
    public function getAttributesOptions(DataContainer|null $dc = null): array
    {
        $arrPids = StringUtil::deserialize($dc->activeRecord->wem_portfolio_feeds);
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

    #[AsCallback(table: 'tl_module', target: 'fields.wem_portfolio_filters_module.options')]
    public function getFiltersModules(DataContainer|null $dc = null): array
    {
        $arrModules = [];
        $objModule = Database::getInstance()->execute('SELECT id, name FROM tl_module WHERE type = "wem_portfolio_filters" ORDER BY name');

        if (!$objModule || 0 === $objModule->count()) {
            return $arrModules;
        }

        while ($objModule->next()) {
            $arrModules[$objModule->id] = $objModule->name;
        }

        return $arrModules;
    }
}