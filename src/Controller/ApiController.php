<?php

namespace WEM\PortfolioBundle\Controller;

use Contao\Config;
use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Model\Collection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Terminal42\ServiceAnnotationBundle\Annotation\ServiceTag;
use WEM\PortfolioBundle\Model\Category;
use WEM\PortfolioBundle\Model\Item;
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
        $infos1 = ["path" => "/items"];
        $infos2 = ["path" => "/item/{id}"];
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
            $objCategory = Category::findByIdOrAlias($category);
            if ($objCategory) {
                $arrConfig['categories'] = [$objCategory->id];
            } else {
                return new JsonResponse('{"error":"Categorie ' . $category . ' not found"}', Response::HTTP_I_AM_A_TEAPOT, [], true);
            }

        }

        $items = [];

        $objItems = Item::findItems($arrConfig, $limit, $offset, $arrOption);

        if ($objItems instanceof Collection) {
            foreach ($objItems as $item) {
                $items[$item->row()["id"]] = $item->row();
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

        $objItem = Item::findByPk($id, ["eager" => true]);

        if ($objItem instanceof Item) {
            $row = $objItem->row();
            if ($row["published"] === '1') {

                $strContent = '';
                $objElement = ContentModel::findPublishedByPidAndTable($id, 'tl_wem_portfolio_item');
                foreach ($objElement as $element) {
                    $strContent .= Controller::getContentElement($element);
                }

                $row['content_b64'] = base64_encode($strContent);

                return new JsonResponse($row, Response::HTTP_OK);
            }
            return new JsonResponse('{"error":"403 : Item not published"}', Response::HTTP_FORBIDDEN, [], true);
        }
        return new JsonResponse('{"error":"404 : Item not found"}', Response::HTTP_NOT_FOUND, [], true);
    }

    private function accessCheck($request): ?JsonResponse
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
