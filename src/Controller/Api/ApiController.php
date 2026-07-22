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
use Contao\System;
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
use WEM\PortfolioBundle\Model\PortfolioFeedAttribute;
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
    private readonly PortfolioService $service;

    public function __construct(
        private readonly ContaoFramework $framework, 
        private readonly Encryption $encryption,
    ) {
        $this->framework->initialize();
        $this->apiKey = null;

        $this->service = System::getContainer()->get('wem.portfolio.service.portfolio');

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
        $routes = [];

        $routes[] = [
            'usage' => 'To retrieve a list of article based on an categories array',
            'path' => '/api/portfolio/get/items/{page}/{limit}?pid[]=1&pid[]=2&key={key}',
            'arguments' => [
                'page' => [
                    'type' => 'int',
                    'mandatory' => true,
                ],
                'limit' => [
                    'type' => 'int',
                    'mandatory' => true,
                ],
                'pid' => [
                    'type' => 'array',
                    'mandatory' => false,
                ],
                'key' => [
                    'type' => 'string',
                    'mandatory' => true,
                ],
            ],
        ];
        $routes[] = [
            'usage' => 'To retrieve a list of article based on an categories array with an offset',
            'path' => '/api/portfolio/get/items/{page}/{limit}/{offset}/?pid[]=1&pid[]=2&key={key}',
            'arguments' => [
                'page' => [
                    'type' => 'int',
                    'mandatory' => true,
                ],
                'limit' => [
                    'type' => 'int',
                    'mandatory' => true,
                ],
                'offset' => [
                    'type' => 'int',
                    'mandatory' => true,
                ],
                'pid' => [
                    'type' => 'array',
                    'mandatory' => false,
                ],
                'key' => [
                    'type' => 'string',
                    'mandatory' => true,
                ],
            ],
        ];
        $routes[] = [
            'usage' => 'To retrieve a list of article based on an categories array with an offset and an order',
            'path' => '/api/portfolio/get/items/{page}/{limit}/{offset}/{order}/?pid[]=1&pid[]=2&key={key}',
            'arguments' => [
                'page' => [
                    'type' => 'int',
                    'mandatory' => true,
                ],
                'limit' => [
                    'type' => 'int',
                    'mandatory' => true,
                ],
                'offset' => [
                    'type' => 'int',
                    'mandatory' => true,
                ],
                'order' => [
                    'type' => 'string',
                    'mandatory' => true,
                ],
                'pid' => [
                    'type' => 'array',
                    'mandatory' => false,
                ],
                'key' => [
                    'type' => 'string',
                    'mandatory' => true,
                ],
            ],
        ];
        $routes[] = [
            'usage' => 'To count number of article based on an categories array',
            'path' => '/api/portfolio/count/items?pid[]=1&pid[]=2&key={key}',
            'arguments' => [
                'pid' => [
                    'type' => 'array',
                    'mandatory' => false,
                ],
                'key' => [
                    'type' => 'string',
                    'mandatory' => true,
                ],
            ],
        ];
        $routes[] = [
            'usage' => 'To retrieve an unique item based on the unique Id',
            'path' => '/api/portfolio/get/item/{id}&key={key}',
            'arguments' => [
                'pid' => [
                    'type' => 'array',
                    'mandatory' => false,
                ],
                'key' => [
                    'type' => 'string',
                    'mandatory' => true,
                ],
            ],
        ];
        $routes[] = [
            'usage' => 'To retrieve portfolio feeds',
            'path' => '/api/portfolio/get/feeds&key={key}',
            'arguments' => [
                'key' => [
                    'type' => 'string',
                    'mandatory' => true,
                ],
            ],
        ];
        $routes[] = [
            'usage' => 'To retrieve portfolio feed',
            'path' => '/api/portfolio/get/feed/{id}&key={key}',
            'arguments' => [
                'id' => [
                    'type' => 'int',
                    'mandatory' => true,
                ],
                'key' => [
                    'type' => 'string',
                    'mandatory' => true,
                ],
            ],
        ];
        $routes[] = [
            'usage' => 'To retrieve portfolio feed attributes',
            'path' => '/api/portfolio/get/feed/{id}/attributes&key={key}',
            'arguments' => [
                'id' => [
                    'type' => 'int',
                    'mandatory' => true,
                ],
                'key' => [
                    'type' => 'string',
                    'mandatory' => true,
                ],
            ],
        ];
        $routes[] = [
            'usage' => 'To retrieve portfolio feed attribute',
            'path' => '/api/portfolio/get/feed/attribute/{id}&key={key}',
            'arguments' => [
                'id' => [
                    'type' => 'int',
                    'mandatory' => true,
                ],
                'key' => [
                    'type' => 'string',
                    'mandatory' => true,
                ],
            ],
        ];

        return new JsonResponse(['routes' => $routes]);
    }

    #[Route(
        '/get/feeds',
        name: 'getfeeds',
        methods: ['GET']
    )]
    public function getFeeds(Request $request): JsonResponse
    {
        $check = $this->accessCheck($request);

        if ($check instanceof JsonResponse) {
            return $check;
        }

        $objItems = PortfolioFeed::findAll();
        $arrFeeds = [];

        if (!$objItems || 0 === $objItems->count()) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        while ($objItems->next()) {
            $arrFeeds[$objItems->id] = $objItems->row();
        }

        return new JsonResponse($arrFeeds, Response::HTTP_OK);
    }

    #[Route(
        '/get/feed/{id}',
        name: 'getFeed',
        requirements: ['id' => Requirement::DIGITS],
        methods: ['GET']
    )]
    public function getFeed(Request $request, $id): JsonResponse
    {
        $check = $this->accessCheck($request);

        if ($check instanceof JsonResponse) {
            return $check;
        }

        $objItem = PortfolioFeed::findById($id);

        if (!$objItem) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($objItem->row(), Response::HTTP_OK);
    }

    #[Route(
        '/get/feed/{id}/attributes',
        name: 'getfeedsattributes',
        requirements: ['id' => Requirement::DIGITS],
        methods: ['GET']
    )]
    public function getFeedAttributes(Request $request, $id): JsonResponse
    {
        $check = $this->accessCheck($request);

        if ($check instanceof JsonResponse) {
            return $check;
        }

        $objItems = PortfolioFeedAttribute::findItems(['pid' => $id]);
        $arrItems = [];

        if (!$objItems || 0 === $objItems->count()) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        while ($objItems->next()) {
            $arrItems[$objItems->id] = $this->prepareAttribute($objItems->current());
        }

        return new JsonResponse($arrItems, Response::HTTP_OK);
    }

    #[Route(
        '/get/feed/attribute/{id}',
        name: 'getFeedAttribute',
        requirements: ['id' => Requirement::DIGITS],
        methods: ['GET']
    )]
    public function getFeedAttribute(Request $request, $id): JsonResponse
    {
        $check = $this->accessCheck($request);

        if ($check instanceof JsonResponse) {
            return $check;
        }

        $objItem = PortfolioFeedAttribute::findById($id);

        if (!$objItem) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($this->prepareAttribute($objItem), Response::HTTP_OK);
    }

    #[Route(
        '/search/feed/attribute',
        name: 'searchFeedAttribute',
        methods: ['GET']
    )]
    public function searchFeedAttribute(Request $request): JsonResponse
    {
        $check = $this->accessCheck($request);

        if ($check instanceof JsonResponse) {
            return $check;
        }

        $params = $request->query->all();
        $objItems = PortfolioFeedAttribute::findItems($params);

        if (!$objItems) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $arrItems = [];
        while ($objItems->next()) {
            $arrItems[$objItems->id] = $this->prepareAttribute($objItems->current());
        }

        return new JsonResponse($arrItems, Response::HTTP_OK);
    }

    private function prepareAttribute(PortfolioFeedAttribute $attr): array 
    {
        $item = $attr->row();

        Controller::loadDataContainer('tl_wem_portfolio');
        $item['dcaConfig'] = $GLOBALS['TL_DCA']['tl_wem_portfolio']['fields'][$attr->name];

        return $item;
    }

    #[Route(
        '/get/items/{page}/{limit}',
        name: 'getItems',
        requirements: ['page' => Requirement::DIGITS, 'limit' => Requirement::DIGITS],
        methods: ['GET']
    )]
    public function getItems(Request $request, int $page, int $limit, array $pid = []): JsonResponse
    {
        return $this->getList($request, $page, $limit, 0, null, $pid);
    }

    #[Route(
        '/get/items/{page}/{limit}/{offset}',
        name: 'getItemsWithOffset',
        requirements: ['page' => Requirement::DIGITS, 'limit' => Requirement::DIGITS, 'offset' => Requirement::DIGITS],
        methods: ['GET']
    )]
    public function getItemsWithOffset(Request $request, int $page, int $limit, int $offset, array $pid = []): JsonResponse
    {
        return $this->getList($request, $page, $limit, $offset, null, $pid);
    }

    #[Route(
        '/get/items/{page}/{limit}/{offset}/{order}',
        name: 'getItemsWithOffsetAndOrder',
        requirements: ['page' => Requirement::DIGITS, 'limit' => Requirement::DIGITS, 'offset' => Requirement::DIGITS, 'order' => Requirement::ASCII_SLUG],
        methods: ['GET']
    )]
    public function getItemsWithOffsetAndOrder(Request $request, int $page, int $limit, int $offset, string $order, array $pid = []): JsonResponse
    {
        return $this->getList($request, $page, $limit, $offset, $order, $pid);
    }

    #[Route(
        '/count/items',
        name: 'countItems',
        methods: ['GET']
    )]
    public function countItems(Request $request, array $pid = []): JsonResponse
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

    #[Route(
        '/get/item/{id}',
        name: 'getItem',
        requirements: ['id' => Requirement::DIGITS],
        methods: ['GET']
    )]
    public function getItem(Request $request, $id): JsonResponse
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

        // Allow people to choose order direction
        if(null !== $order && false !== strpos($order, "-")) {
            $chunks = explode("-", $order);
            $options['order'] = urldecode($chunks[0]) . ' ' . strtoupper($chunks[1]);
        } else if (null !== $order) {
            $options['order'] = urldecode($order) . ' DESC';
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

    protected function prepareItem(Portfolio $item, string $locale = null): ?array
    {
        // Return null if the item is not published
        if ('' === $item->published) {
            return null;
        }

        $this->service->load($item, $locale);
        $data = $this->service->getFields();
        $base = Environment::get('base');

        // Adjustement based on type
        foreach ($data as $col => &$value) {
            $config = $this->service->getDca($col);

            if (!array_key_exists('inputType', $config)) {
                continue;
            }

            switch ($config['inputType']) {
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
                            if (!array_key_exists('path', $f)) {
                                continue;
                            }

                            $f['path'] = Environment::get('base') . $f['path'];
                            $f['fromApi'] = true;
                        }
                    } else {
                        if (!array_key_exists('path', $value)) {
                            continue 2;
                        }

                        $value['path'] = Environment::get('base') . $value['path'];
                        $value['fromApi'] = true;
                    }

                    break;
                
                default:
                    // Do nothing
                    break;
            }
        }

        // Adjustement based on field
        foreach ($data as $col => &$value) {
            switch ($col) {
                // Serialized values
                case 'size':
                case 'imagemargin':
                    if ($value) {
                        $value = StringUtil::deserialize($value);
                    }

                    break;

                // For pid, we shoud retrieve all data from parent
                case 'pid':
                    $data['pid_data'] = $item->getRelated($col)->row();
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
