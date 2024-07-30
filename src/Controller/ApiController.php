<?php

namespace WEM\PortfolioBundle\Controller;

use Contao\ContentModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Model\Collection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Terminal42\ServiceAnnotationBundle\Annotation\ServiceTag;
use WEM\PortfolioBundle\Model\Category;
use WEM\PortfolioBundle\Model\Item;

/**
 * @Route("/api/portfolio")
 *
 * @ServiceTag("controller.service_arguments")
 */
class ApiController
{

    private ContaoFramework $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
        $this->framework->initialize();
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
     * @Route("/items/{page}/{limit}", methods={"GET"})
     */
    public function viewPortfolioList(Request $request, int $page, int $limit, array $cats = []): JsonResponse
    {
        $cats = $request->query->get("cats"); //?ids[]=1&ids[]=2
        $token = $request->query->get("token"); //?token=lol
        if ($token != "blurp") {
            return new JsonResponse('{"error":"Forbidden Access"}', Response::HTTP_FORBIDDEN, [], true);
        }
        $arrOption['eager'] = true;
        $arrConfig['published'] = "1";


        $offset = ($page - 1) * $limit;
        if (!is_iterable($cats)) {
            return new JsonResponse('{"error":"Give at least on category : ?cats[]=1&cats[]=2 "}', Response::HTTP_NOT_ACCEPTABLE, [], true);
        }

        foreach ($cats as $category) {
            $objCategory = Category::findByIdOrAlias($category);
            if ($objCategory) {
                $arrConfig['categories'] = [$objCategory->id];
            } else {
                return new JsonResponse(['data' => "Error : categorie " . $category . " not found."], Response::HTTP_I_AM_A_TEAPOT);
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
     * @Route("/item/{id}", methods={"GET"})
     */
    public function viewPortfolioItem($id): JsonResponse
    {

        $objItem = Item::findByPk($id, ["eager" => true]);

        if ($objItem instanceof Item) {
            $row = $objItem->row();
            if ($row["published"] === '1') {
                $item = $objItem->row();
                $strContent = '';
                $objElement = ContentModel::findPublishedByPidAndTable($id, 'tl_wem_portfolio_item');
                foreach ($objElement as $element) {
                    //$strContent .=  $this->controller->getContentElement($objElement->current());
                    $strContent .= "lol";
                }

                $item['content'] = $strContent;

                return new JsonResponse($item, Response::HTTP_OK);
            }
            return new JsonResponse('{"error":"403"}', Response::HTTP_FORBIDDEN, [], true);
        }
        return new JsonResponse('{"error":"404"}', Response::HTTP_NOT_FOUND, [], true);
    }

}
