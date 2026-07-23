<?php

namespace WEM\PortfolioBundle\EventListener\DataContainer\Module;

use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\Database;
use Contao\DataContainer;
use Contao\Message;
use Contao\Model\Collection;
use WEM\PortfolioBundle\Model\PortfolioFeed;
use WEM\PortfolioBundle\Model\PortfolioFeedAttribute;
use WEM\PortfolioBundle\Wrapper\PortfolioApi;
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
            if (!empty($v['eval']) && array_key_exists('wemIsFilter', $v['eval']) && true === $v['eval']['wemIsFilter']) {
                $fields[$k] = \sprintf('%s (%s)', $v['label'][0] ?: $k, $k);
            }
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

    #[AsCallback(table: 'tl_module', target: 'fields.wem_portfolio_remote_feeds.options')]
    public function getRemoteFeeds(DataContainer|null $dc = null): array
    {
        $api = $this->getApi($dc);

        if (!$api) {
            return [];
        }

        $feeds = $api->getFeeds();

        if (empty($feeds)) {
            Message::addInfo('No remote feeds were found');

            return [];
        }

        foreach ($feeds as $f) {
            $options[$f['id']] = $f['title'];
        }

        return $options;
    }

    #[AsCallback(table: 'tl_module', target: 'fields.wem_portfolio_remote_filters.options')]
    public function getRemoteFiltersOptions(DataContainer|null $dc = null): array
    {
        $api = $this->getApi($dc);

        if (!$api) {
            return [];
        }

        if (!$dc->activeRecord->wem_portfolio_remote_feeds) {
            Message::addInfo('Configuration is missing: select feeds first');

            return [];
        }

        $options = [];
        $feeds = $dc->activeRecord->wem_portfolio_remote_feeds;

        if (!is_array($feeds)) {
            $feeds = StringUtil::deserialize($feeds);
        }

        foreach ($feeds as $f) {
            $attributes = $api->getAttributes($f);

            if (!$attributes || !is_array($attributes)) {
                continue;
            }

            foreach ($attributes as $a) {
                if (!$a['isFilter']) {
                    continue;
                }

                $options[$a['name']] = $a['label'];
            }
        }

        return $options;
    }

    #[AsCallback(table: 'tl_module', target: 'fields.wem_portfolio_remote_filters_module.options')]
    public function getRemoteFiltersModules(DataContainer|null $dc = null): array
    {
        $arrModules = [];
        $objModule = Database::getInstance()->execute('SELECT id, name FROM tl_module WHERE type = "wem_portfolio_remote_filters" ORDER BY name');

        if (!$objModule || 0 === $objModule->count()) {
            return $arrModules;
        }

        while ($objModule->next()) {
            $arrModules[$objModule->id] = $objModule->name;
        }

        return $arrModules;
    }

    private function getApi(DataContainer|null $dc = null): ?PortfolioApi
    {
        // Return empty if no url or no apikey are defined
        if (!$dc->activeRecord->wem_portfolio_remote_url || !$dc->activeRecord->wem_portfolio_remote_apikey) {
            Message::addInfo('Configuration is missing: remote url and/or api key');

            return null;
        }

        return new PortfolioApi(
            $dc->activeRecord->wem_portfolio_remote_url,
            $dc->activeRecord->wem_portfolio_remote_apikey,
        );
    }
}