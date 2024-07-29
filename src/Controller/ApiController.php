<?php

namespace WEM\PortfolioBundle\Controller;

use Contao\Model\Collection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use WEM\PortfolioBundle\Model\Category;
use WEM\PortfolioBundle\Model\Item;
use WEM\PortfolioBundle\Module\PortfolioList;

/**
 * @Route('/api/portfolio')
 */
class ApiController
{

    private PortfolioList $portfolioList;

    public function __construct(PortfolioList $portfolioList)
    {
        $this->portfolioList = $portfolioList;
    }

    /**
     * @Route('/', methods={"GET"})
     */
    public function doc(Request $request): JsonResponse
    {
        $infos1 = ["path" => "/items"];
        $infos2 = ["path" => "/item/{id}"];
        return new JsonResponse(['data' => [$infos1, $infos2]]);
    }

    /**
     * @Route('/items', methods={"POST"})
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
     * @Route('/item/{id}', methods={"GET"})
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