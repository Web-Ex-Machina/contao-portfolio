<?php

declare(strict_types=1);

namespace WEM\PortfolioBundle\Wrapper;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Content\ContentUrlResolverInterface;
use Contao\CoreBundle\Routing\Content\ContentUrlResult;
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

    public function getFeed(): PortfolioFeed
    {
        return [];
    }

    public function getAttributes(): array
    {
        return [];
    }

    public function getAttribute(): PortfolioFeedAttribute
    {
        return [];
    }

    public function countItems(): int
    {
        return 0;
    }

    public function getItems(): array
    {
        return [];
    }

    public function getItem(): Portfolio
    {
        return [];
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
