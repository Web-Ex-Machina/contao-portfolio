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

namespace WEM\PortfolioBundle\Controller;

use Contao\Config;
use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FilesModel;
use Contao\Model\Collection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;
use Terminal42\ServiceAnnotationBundle\Annotation\ServiceTag;
use WEM\PortfolioBundle\Model\Portfolio;
use WEM\PortfolioBundle\Model\PortfolioFeed;
use WEM\UtilsBundle\Classes\Encryption;
use WEM\UtilsBundle\Classes\StringUtil;

/**
 * @Route("/api/portfolio")
 *
 * @ServiceTag("controller.service_arguments")
 */
class ApiController
{
    private ContaoFramework $framework;

    private Encryption $encryption;

    private ?string $apiKey;

    public function __construct(ContaoFramework $framework, Encryption $encryption)
    {
        $this->encryption = $encryption;
        $this->framework = $framework;
        $this->framework->initialize();
        $this->apiKey = null;

        if (Config::get('portfolioApiKey')) {
            $this->apiKey = $this->encryption->decrypt_b64((string) Config::get('portfolioApiKey'));
        }
    }

    /**
     * @Route("/")
     */
    public function view(Request $request): Response
    {
        return new Response('Hello World!');
    }

    /**
     * @Route("/doc", methods={"GET"})
     */
    public function doc(Request $request): JsonResponse
    {
        $infos1 = [
            'usage' => 'To retrieve a list of article based on an categories array',
            'path' => '/items/{page}/{limit}?cats[]=1&cats[]=2&key=myKey',
        ];
        $infos2 = [
            'usage' => 'To count number of article based on an categories array',
            'path' => '/count?cats[]=1&cats[]=2&key=myKey',
        ];
        $infos3 = [
            'usage' => 'To retrieve an unique item based on the unique Id',
            'path' => '/item/{id}&key=myKey',
        ];

        return new JsonResponse(['data' => [$infos1, $infos2, $infos3]]);
    }

    /**
     * @Route("/items/{page}/{limit}", requirements={"page"="\d+","limit"="\d+"}), methods={"GET"})
     */
    public function viewPortfolioList(Request $request, int $page, int $limit, array $cats = []): JsonResponse
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

        $cats = $request->query->all('cats');

        $offset = ($page - 1) * $limit;
        if (!is_iterable($cats)) {
            return new JsonResponse('{"error":"Give at least on category : ?cats[]=1&cats[]=2"}', Response::HTTP_NOT_ACCEPTABLE, [], true);
        }

        $items = [];

        foreach ($cats as $category) {
            $objCategory = PortfolioFeed::findByIdOrAlias($category);
            if (!$objCategory) {
                return new JsonResponse('{"error":"Categorie '.$category.' not found"}', Response::HTTP_I_AM_A_TEAPOT, [], true);
            }

            $objItems = Portfolio::findItems(['pid' => $objCategory->id, 'published' => '1'], $limit, $offset);
            if ($objItems instanceof Collection) {
                /** @var Portfolio $item */
                foreach ($objItems as $item) {
                    $arrayItem = $item->row();
                    $id = $arrayItem['id'];
                    $return = [];
                    if ('1' === $arrayItem['published']) {
                        $return['mainPicture'] = [];

                        if ('1' === $arrayItem['addImage']) {
                            $imageP = FilesModel::findByUuid($arrayItem['singleSRC']);
                            $uuidP = Uuid::fromBinary($imageP->uuid);
                            $return['image_principal'][$uuidP->__toString()]['path'] = $imageP->path;
                            $return['image_principal'][$uuidP->__toString()]['tstamp'] = $imageP->tstamp;
                            $return['image_principal'][$uuidP->__toString()]['hash'] = $imageP->hash;
                            $return['image_principal'][$uuidP->__toString()]['lastModified'] = $imageP->lastModified;
                        }

                        $return['createdAt'] = $arrayItem['createdAt'];
                        $return['title'] = $arrayItem['title'];
                        $arrayCategory = $item->getRelated('pid')->row();
                        $return['categorie']['id'] = $arrayCategory['id'];
                        $return['categorie']['createdAt'] = $arrayCategory['createdAt'];
                        $return['categorie']['tstamp'] = $arrayCategory['tstamp'];
                        $return['categorie']['title'] = $arrayCategory['title'];
                        $return['categorie']['alias'] = $arrayCategory['alias'];

                        $return['teaser'] = $arrayItem['teaser'];
                        $return['link'] = $item->getUrl();
                    }

                    $items[$id] = $return;
                }

                return new JsonResponse($items, Response::HTTP_OK);
            }
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }

        /**
     * @Route("/count", methods={"GET"})
     */
    public function countPortfolioList(Request $request, array $cats = []): JsonResponse
    {
        $check = $this->accessCheck($request);
        if ($check instanceof JsonResponse) {
            return $check;
        }

        $cats = $request->query->all('cats');

        if (!is_iterable($cats)) {
            return new JsonResponse('{"error":"Give at least on category : ?cats[]=1&cats[]=2"}', Response::HTTP_NOT_ACCEPTABLE, [], true);
        }

        return new JsonResponse(['items' => Portfolio::countItems(['pid' => $cats])], Response::HTTP_OK);
    }

    /**
     * @Route("/item/{id}", requirements={"id"="\d+"}), methods={"GET"})
     */
    public function viewPortfolioItem(Request $request, $id): JsonResponse
    {
        $check = $this->accessCheck($request);
        if ($check instanceof JsonResponse) {
            return $check;
        }

        $objItem = Portfolio::findByPk($id, ['eager' => true]);

        if ($objItem instanceof Portfolio) {
            $arrayItem = $objItem->row();
            if ('1' === $arrayItem['published']) {
                $return = [];
                $strContent = '';
                $objElement = ContentModel::findPublishedByPidAndTable($id, 'tl_wem_portfolio') ?? [];

                foreach ($objElement as $element) {
                    $strContent .= Controller::getContentElement($element);
                }

                if ('1' === $arrayItem['addImage']) {
                    $imageP = FilesModel::findByUuid($arrayItem['singleSRC']);
                    $uuidP = Uuid::fromBinary($imageP->uuid);
                    $return['image_principal'][$uuidP->__toString()]['path'] = $imageP->path;
                    $return['image_principal'][$uuidP->__toString()]['tstamp'] = $imageP->tstamp;
                    $return['image_principal'][$uuidP->__toString()]['hash'] = $imageP->hash;
                    $return['image_principal'][$uuidP->__toString()]['lastModified'] = $imageP->lastModified;
                }

                $images = $arrayItem['orderPictures'];

                if (null !== $images) {
                    $images = StringUtil::deserialize($images);

                    foreach ($images as $image) {
                        $uuid = Uuid::fromBinary($image);
                        if ($image = FilesModel::findByUuid($image)) {
                            $return['orderPictures'][$uuid->__toString()]['path'] = $image->path;
                            $return['orderPictures'][$uuid->__toString()]['tstamp'] = $image->tstamp;
                            $return['orderPictures'][$uuid->__toString()]['hash'] = $image->hash;
                            $return['orderPictures'][$uuid->__toString()]['lastModified'] = $image->lastModified;
                        }
                    }

                // $return['orderPictures'] = $images;
                } else {
                    $return['orderPictures'] = [];
                }

                $return['createdAt'] = $arrayItem['createdAt'];
                $return['tstamp'] = $arrayItem['tstamp'];
                $return['title'] = $arrayItem['title'];

                $return['date'] = $arrayItem['date'];
                $return['teaser'] = $arrayItem['teaser'];
                $category = $objItem->getRelated('pid');
                $arrayCategory = $category->row();

                $return['categorie']['id'] = $arrayCategory['id'];
                $return['categorie']['createdAt'] = $arrayCategory['createdAt'];
                $return['categorie']['tstamp'] = $arrayCategory['tstamp'];
                $return['categorie']['title'] = $arrayCategory['title'];
                $return['categorie']['alias'] = $arrayCategory['alias'];

                $return['slug'] = $arrayItem['slug'];

                $return['url'] = $objItem->getUrl(true);

                $return['content_b64'] = base64_encode($strContent);

                return new JsonResponse($return, Response::HTTP_OK);
            }

            return new JsonResponse('{"error":"403 : Item not published"}', Response::HTTP_FORBIDDEN, [], true);
        }

        return new JsonResponse('{"error":"404 : Item not found"}', Response::HTTP_NOT_FOUND, [], true);
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
