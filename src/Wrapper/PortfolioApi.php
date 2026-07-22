<?php

declare(strict_types=1);

namespace WEM\PortfolioBundle\Wrapper;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Content\ContentUrlResolverInterface;
use Contao\CoreBundle\Routing\Content\ContentUrlResult;
use Contao\Model\Collection;
use Contao\PageModel;
use Contao\System;
use Exception;
use Terminal42\ChangeLanguage\PageFinder;
use Symfony\Component\HttpFoundation\RequestStack;
use WEM\PortfolioBundle\Model\Portfolio;
use WEM\PortfolioBundle\Model\PortfolioFeed;
use WEM\PortfolioBundle\Model\PortfolioFeedAttribute;
use WEM\PortfolioBundle\Service\PortfolioService;

class PortfolioApi
{
    public function __construct(
        private string $remoteUrl,
        private string $apiKey,
    ) {
        $enc = System::getContainer()->get('wem.encryption_util');

        try {
            $this->remoteUrl = $enc->decrypt_b64($remoteUrl);
            $this->apiKey = $enc->decrypt_b64($apiKey);
        } catch (Exception $e) {
            $this->remoteUrl = $remoteUrl;
            $this->apiKey = $apiKey;
        }
    }

    public function getFeeds(): array
    {
        return $this->callApi('/get/feeds');
    }

    public function getFeed(int $feed): PortfolioFeed
    {
        $data = $this->callApi('/get/feed/' . $feed);

        $model = new PortfolioFeed();
        $model->setRow($data);

        return $model;
    }

    public function getAttributes(int $feed): Collection
    {
        $data = $this->callApi('/get/feed/' . $feed . '/attributes');
        $models = [];

        foreach ($data as $obj) {
            $model = new PortfolioFeedAttribute();
            $model->setRow($obj);

            $models[] = $model;
        }

        return new Collection($models, PortfolioFeedAttribute::getTable());
    }

    public function getAttribute(int $attribute): PortfolioFeedAttribute
    {
        $data = $this->callApi('/get/feed/attribute/' . $attribute);

        $model = new PortfolioFeedAttribute();
        $model->setRow($data);

        return $model;
    }

    public function searchAttributes(array $params = []): Collection
    {
        $data = $this->callApi('/search/feed/attributes', $params);
        $models = [];

        foreach ($data as $obj) {
            $model = new PortfolioFeedAttribute();
            $model->setRow($obj);

            $models[] = $model;
        }

        return new Collection($models, PortfolioFeedAttribute::getTable());
    }

    public function countItems(array $params = []): int
    {
        $res = $this->callApi('/count/items', $params);
        return $res['items'];
    }

    public function getItems(array $params = []): Collection
    {
        $data = $this->callApi('/get/items', $params);
        $models = [];

        foreach ($data as $obj) {
            $model = new Portfolio();
            $model->setRow($obj);

            $models[] = $model;
        }

        return new Collection($models, Portfolio::getTable());
    }

    public function getItem(int $item): Portfolio
    {
        $data = $this->callApi('/get/item/' . $item);

        $model = new Portfolio();
        $model->setRow($data);

        return $model;
    }

    protected function callApi(string $route, array $params = []): mixed
    {
        $ch = curl_init();

        $params['key'] = $this->apiKey;

        $url = $this->remoteUrl . '/api/portfolio' . $route . '?' . http_build_query($params);


        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $request = curl_exec($ch);
        curl_close($ch);

        return json_decode($request, true);
    }
}
