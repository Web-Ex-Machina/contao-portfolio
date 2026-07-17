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

namespace WEM\PortfolioBundle\Controller\Api;

use Contao\Config;
use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Environment;
use Contao\FilesModel;
use Contao\Model\Collection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Uid\Uuid;
use WEM\PortfolioBundle\Model\Portfolio;
use WEM\PortfolioBundle\Model\PortfolioL10n;
use WEM\PortfolioBundle\Model\PortfolioFeed;
use WEM\PortfolioBundle\Service\PortfolioService;
use WEM\UtilsBundle\Classes\Encryption;
use WEM\UtilsBundle\Classes\StringUtil;

#[Route(
    '/api/portfolio',
    name: 'wem_api_portfolio',
    defaults: ['_scope' => 'frontend', '_token_check' => false]
)]
#[AsController]
class ApiController
{
    private ?string $apiKey;

    public function __construct(
        private readonly ContaoFramework $framework, 
        private readonly Encryption $encryption,
        private readonly PortfolioService $service,
    ) {
        $this->framework->initialize();
        $this->apiKey = null;

        if (Config::get('portfolioApiKey')) {
            $this->apiKey = $this->encryption->decrypt_b64((string) Config::get('portfolioApiKey'));
        }
    }

    #[Route("/")]
    public function view(Request $request): Response
    {
        return new Response('Hello World!');
    }

    #[Route(
        '/doc',
        name: 'doc',
        methods: ['GET']
    )]
    public function doc(Request $request): JsonResponse
    {
        $infos1 = [
            'usage' => 'To retrieve a list of article based on an categories array',
            'path' => '/items/{page}/{limit}?pid[]=1&pid[]=2&key=myKey',
        ];
        $infos2 = [
            'usage' => 'To count number of article based on an categories array',
            'path' => '/count?pid[]=1&pid[]=2&key=myKey',
        ];
        $infos3 = [
            'usage' => 'To retrieve an unique item based on the unique Id',
            'path' => '/item/{id}&key=myKey',
        ];

        return new JsonResponse(['data' => [$infos1, $infos2, $infos3]]);
    }

    #[Route(
        '/items/{page}/{limit}',
        name: 'viewPortfolioList',
        requirements: ['page' => Requirement::DIGITS, 'limit' => Requirement::DIGITS],
        methods: ['GET']
    )]
    public function viewPortfolioList(Request $request, int $page, int $limit, array $pid = []): JsonResponse
    {
        return $this->getList($request, $page, $limit, 0, null, $pid);
    }

    #[Route(
        '/items/{page}/{limit}/{offset}',
        name: 'viewPortfolioListWithOffset',
        requirements: ['page' => Requirement::DIGITS, 'limit' => Requirement::DIGITS, 'offset' => Requirement::DIGITS],
        methods: ['GET']
    )]
    public function viewPortfolioListWithOffset(Request $request, int $page, int $limit, int $offset, array $pid = []): JsonResponse
    {
        return $this->getList($request, $page, $limit, $offset, null, $pid);
    }

    #[Route(
        '/items/{page}/{limit}/{offset}/{order}',
        name: 'viewPortfolioListWithOffsetAndOrder',
        requirements: ['page' => Requirement::DIGITS, 'limit' => Requirement::DIGITS, 'offset' => Requirement::DIGITS, 'order' => Requirement::ASCII_SLUG],
        methods: ['GET']
    )]
    public function viewPortfolioListWithOffsetAndOrder(Request $request, int $page, int $limit, int $offset, string $order, array $pid = []): JsonResponse
    {
        return $this->getList($request, $page, $limit, $offset, $order, $pid);
    }

    protected function getList(Request $request, int $page, int $limit, int $offset = 0, ?string $order = null, array $pid = []): JsonResponse
    {
        $check = $this->accessCheck($request);
        if ($check instanceof JsonResponse) {
            return $check;
        }

        if ($limit > 20) {
            $limit = 20;
        }

        if ($limit < 1) {
            $limit = 1;
        }

        if ($page < 1) {
            $page = 1;
        }

        $params = $request->query->all();
        $locale = $request->query->get('lang') ?: $GLOBALS['TL_LANGUAGE'];

        if (!is_iterable($params['pid'])) {
            return new JsonResponse('{"error":"Give at least one category : ?pid[]=1&pid[]=2"}', Response::HTTP_NOT_ACCEPTABLE, [], true);
        }

        unset($params['key']);
        unset($params['lang']);

        $items = [];
        $options = [];

        if ($order) {
            $options['order'] = urldecode($order);
        }

        $objItems = Portfolio::findItems($params, $limit, $offset, $options);

        if ($objItems instanceof Collection) {
            /** @var Portfolio $item */
            foreach ($objItems as $item) {
                if (!$item->published) {
                    continue;
                }

                $items[$item->id] = $this->prepareItem($item, $locale);
            }

            return new JsonResponse($items, Response::HTTP_OK);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }

    /**
     * @Route("/count", methods={"GET"})
     */
    public function countPortfolioList(Request $request, array $pid = []): JsonResponse
    {
        $check = $this->accessCheck($request);
        if ($check instanceof JsonResponse) {
            return $check;
        }

        $params = $request->query->all();
        $locale = $request->query->get('lang') ?: $GLOBALS['TL_LANGUAGE'];

        if (!is_iterable($params['pid'])) {
            return new JsonResponse('{"error":"Give at least one category : ?pid[]=1&pid[]=2"}', Response::HTTP_NOT_ACCEPTABLE, [], true);
        }

        unset($params['key']);
        unset($params['lang']);

        return new JsonResponse(['items' => Portfolio::countItems($params)], Response::HTTP_OK);
    }

    /**
     * @Route("/item/{id}", methods={"GET"})
     */
    public function viewPortfolioItem(Request $request, $id): JsonResponse
    {
        $check = $this->accessCheck($request);
        if ($check instanceof JsonResponse) {
            return $check;
        }

        $objItem = Portfolio::findByIdOrSlug($id, ['eager' => true]);

        if (null === $objItem) {
            $objL10nItem = PortfolioL10n::findByIdOrSlug($id);

            if ($objL10nItem) {
                $objItem = $objL10nItem->getRelated('pid');
            }
        }

        $locale = $request->query->get('lang') ?: $GLOBALS['TL_LANGUAGE'];

        if ($objItem instanceof Portfolio) {
            if ($objItem->published) {
                $return = $this->prepareItem($objItem, $locale);

                return new JsonResponse($return, Response::HTTP_OK);
            }

            return new JsonResponse('{"error":"403 : Item not published"}', Response::HTTP_FORBIDDEN, [], true);
        }

        return new JsonResponse('{"error":"404 : Item not found"}', Response::HTTP_NOT_FOUND, [], true);
    }

    protected function prepareItem(Portfolio $item, string $locale = null): ?array
    {
        // Return null if the item is not published
        if ('' === $item->published) {
            return null;
        }

        $this->service->load($item, $locale);
        $data = $this->service->getFields();

        // Adjust fields for API
        $base = Environment::get('base');

        foreach ($data as $col => &$value) {
            $config = $this->service->getDca($col);

            switch ($config['type']) {
                // For files & folders we need to transform relative paths
                // into absolutes
                case 'fileTree':
                    // Adjust if the field is single / multiple
                    if (
                        array_key_exists('eval', $config) && 
                        array_key_exists('multiple', $config['eval']) &&
                        true === $config['eval']['multiple']
                    ) {
                        foreach ($value as &$f) {
                            $f['path'] = Environment::get('base') . $f['path'];
                        }
                    } else {
                        $value['path'] = Environment::get('base') . $value['path'];
                    }

                    break;

                // For pid, we shoud retrieve all data from parent
                // and put it under a new key "category"
                case 'pid':
                    $data['category'] = $item->getRelated($col)->row();
                    break;
                
                default:
                    // Do nothing
                    break;
            }
        }

        // Retrieve item content
        $data['content_b64'] = base64_encode($this->service->getContent());

        return $data;
    }

    private function accessCheck(Request $request): ?JsonResponse
    {
        if (!$this->apiKey) {
            return new JsonResponse('{"error":"No API KEY Provided"}', Response::HTTP_SERVICE_UNAVAILABLE, [], true);
        }

        if ($request->headers->get('HTTP_PORTFOLIO_API_KEY')) {
            $token = $request->headers->get('HTTP_PORTFOLIO_API_KEY');
        } elseif ($request->query->get('key')) {
            $token = $request->query->get('key');
        } else {
            return new JsonResponse('{"error":"Forbidden Access no token : please provide &key=APIKEY in request OR HTTP_PORTFOLIO_API_KEY in headers"}', Response::HTTP_FORBIDDEN, [], true);
        }

        if ('' === $token) {
            return new JsonResponse('{"error":"Bad Request empty token"}', Response::HTTP_BAD_REQUEST, [], true);
        }

        if ($this->apiKey !== $token) {
            return new JsonResponse('{"error":"Forbidden Access bad token"}', Response::HTTP_FORBIDDEN, [], true);
        }

        return null;
    }
}
