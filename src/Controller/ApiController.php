<?php

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

        $this->apiKey = ($this->encryption->decrypt_b64(Config::get('portfolioApiKey'))) ?? null;

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
            "usage" => "For retrieve a list of article based on an categories array",
            "path" => "/items/{page}/{limit}?cats[]=1&cats[]=2&key=myKey"
        ];
        $infos2 = [
            "usage" => "For retrieve an unique item based on the unique Id",
            "path" => "/item/{id}&key=myKey"
        ];
        return new JsonResponse(['data' => [$infos1, $infos2]]);
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

        $limit = ($limit > 20) ? 20 : $limit;
        $limit = ($limit < 1) ? 1 : $limit;
        $page = ($page = 0) ? 1 : $page;

        $cats = $request->query->get("cats");
        $arrOption['eager'] = true;
        $arrConfig['published'] = "1";

        $offset = ($page - 1) * $limit;
        if (!is_iterable($cats)) {
            return new JsonResponse('{"error":"Give at least on category : ?cats[]=1&cats[]=2"}', Response::HTTP_NOT_ACCEPTABLE, [], true);
        }

        foreach ($cats as $category) {
            $objCategory = PortfolioFeed::findByIdOrAlias($category);
            if ($objCategory) {
                $arrConfig['categories'][] = $objCategory->id;
            } else {
                return new JsonResponse('{"error":"Categorie ' . $category . ' not found"}', Response::HTTP_I_AM_A_TEAPOT, [], true);
            }

        }

        $items = [];

        $objItems = Portfolio::findItems($arrConfig, $limit, $offset, $arrOption);

        if ($objItems instanceof Collection) {
            foreach ($objItems as $item) {
                $arrayItem = $item->row();
                $id = $arrayItem["id"];
                $return = [];
                if ($arrayItem["published"] === '1') {

                    $return['mainPicture'] = [];

                    $images = $item->getPictures();

                    if (count($images) > 0) {
                        $uuid = Uuid::fromBinary($images[0]['uuid']);
                        $images[0]['uuid'] = $uuid->__toString();
                        $return['mainPicture'] = $images[0];
                    }

                    $return["createdAt"] = $arrayItem["createdAt"];
                    $return["title"] = $arrayItem["title"];
                    $objCategories = $item->getRelated('categories');
                    $return['categories'] = null;
                    foreach ($objCategories->fetchAll() as $category) {
                        $return['categories']['id'] = $category['id'];
                        $return['categories']['title'] = $category['title'];
                        $return['categories']['alias'] = $category['alias'];
                    }

                    $return['teaser'] = $arrayItem["teaser"];
                    $return["link"] = $item->getUrl();
                }

                $items[$id] = $return;
            }
            return new JsonResponse($items, Response::HTTP_OK);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
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

        $objItem = Portfolio::findByPk($id, ["eager" => true]);

        if ($objItem instanceof Portfolio) {
            $arrayItem = $objItem->row();
            if ($arrayItem["published"] === '1') {
                $return = [];
                $strContent = '';
                $objElement = ContentModel::findPublishedByPidAndTable($id, 'tl_wem_portfolio');
                foreach ($objElement as $element) {
                    $strContent .= Controller::getContentElement($element);
                }

                $return['pictures'] = [];

                $images = $objItem->getPictures();

                if ($images !== []) {
                    foreach ($images as $key => $image) {
                        $uuid = Uuid::fromBinary($image['uuid']);
                        $images[$key]['uuid'] = $uuid->__toString();
                    }
                    $return['pictures'] = $images;
                }

                $return["createdAt"] = $arrayItem["createdAt"];
                $return["tstamp"] = $arrayItem["tstamp"];
                $return["title"] = $arrayItem["title"];
                $return["alias"] = $arrayItem["alias"];
                $return["date"] = $arrayItem["date"];
                $objCategories = $objItem->getRelated('categories');
                $return['categories'] = null;
                foreach ($objCategories->fetchAll() as $category) {
                    $return['categories']['id'] = $category['id'];
                    $return['categories']['createdAt'] = $category['createdAt'];
                    $return['categories']['tstamp'] = $category['tstamp'];
                    $return['categories']['title'] = $category['title'];
                    $return['categories']['alias'] = $category['alias'];
                    $return['categories']['jumpTo'] = $category['jumpTo'];
                    $return['categories']['picture'] = null;
                    if ($image = FilesModel::findByUuid($category['picture'])) {
                        $return['categories']['picture']['path'] = $image->path;
                        $return['categories']['picture']['tstamp'] = $image->tstamp;
                        $return['categories']['picture']['hash'] = $image->hash;
                        $return['categories']['picture']['lastModified'] = $image->lastModified;
                    }

                    $return['categories']['teaser'] = $category['teaser'];
                    $return['categories']['attributes'] = $category['attributes'];

                }

                $return['teaser'] = $arrayItem["teaser"];
                $return["link"] = $objItem->getUrl();

                $return["attributes"] = $objItem->getAttributes();

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

        if ($request->headers->get("HTTP_PORTFOLIO_API_KEY")) {
            $token = $request->headers->get("HTTP_PORTFOLIO_API_KEY");
        } elseif ($request->query->get("key")) {
            $token = $request->query->get("key");
        } else {
            return new JsonResponse('{"error":"Forbidden Access no token : please provide &key=APIKEY in request OR HTTP_PORTFOLIO_API_KEY in headers"}', Response::HTTP_FORBIDDEN, [], true);
        }

        if ($token == "") {
            return new JsonResponse('{"error":"Bad Request empty token"}', Response::HTTP_BAD_REQUEST, [], true);
        }

        if ($this->apiKey !== $token) {
            return new JsonResponse('{"error":"Forbidden Access bad token"}', Response::HTTP_FORBIDDEN, [], true);
        }

        return null;
    }

}
