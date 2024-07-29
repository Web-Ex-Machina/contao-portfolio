<?php

namespace WEM\PortfolioBundle\Controller;

use Contao\Model\Collection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Terminal42\ServiceAnnotationBundle\Annotation\ServiceTag;
use WEM\PortfolioBundle\Model\Category;
use WEM\PortfolioBundle\Model\Item;

/**
 * @Route('/api/portfolio',name="api_portfolio_")
 * @ServiceTag("controller.service_arguments")
 */
class ApiController
{

    public function __invoke(Request $request): Response
    {
        return new Response('Hello World!');
    }

    /**
     * @Route('/',name="doc", methods={"GET"})
     */
    public function doc(Request $request): JsonResponse
    {
        $infos1 = ["path" => "/items"];
        $infos2 = ["path" => "/item/{id}"];
        return new JsonResponse(['data' => [$infos1, $infos2]]);
    }

    /**
     * @Route('/items',name="items", methods={"POST"})
     */
    public function viewPortfolioList(Request $request, array $categories = [], int $page = 0, int $limit = 20): JsonResponse
    {
        $arrConfig['published'] = 1;
        $request->getPayload();
        $offset = $page * $limit;


        foreach ($categories as $category) {
            $objCategory = Category::findByIdOrAlias($category);
            if ($objCategory) {
                $arrConfig['categories'] = [$objCategory->id];
            } else {
                return new JsonResponse(['data' => "Error : categorie " . $category . " not found."], 418);
            }

        }

        $objItems = Item::findItems($arrConfig, ($limit ?: 30), $offset);
        if ($objItems instanceof Collection) {
            return new JsonResponse($objItems, 200);
        }
        return new JsonResponse(null, 404);
    }

    /**
     * @Route('/item/{id}',name="item", methods={"GET"})
     */
    public function viewPortfolioItem($id): JsonResponse
    {

        $objItem = Item::findByPk($id);

        if ($objItem instanceof Item) {
            return new JsonResponse($objItem, 200);
        }
        return new JsonResponse(null, 404);
    }

}