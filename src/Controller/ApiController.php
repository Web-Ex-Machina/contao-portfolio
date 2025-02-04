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
use Contao\Environment;
use Contao\FilesModel;
use Contao\Model\Collection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;
use Terminal42\ServiceAnnotationBundle\Annotation\ServiceTag;
use WEM\PortfolioBundle\Model\Portfolio;
use WEM\PortfolioBundle\Model\PortfolioL10n;
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
     * @Route("/migrate2")
     */
    public function migrate2(Request $request): Response
    {
        return new Response('Migrate 2');
        $db = \Database::getInstance();
        $objItems = $db->prepare('SELECT * FROM tl_iso_product')->execute();
        while ($objItems->next()) {
            if (null === $objItems->related_projects) {
                continue;
            }

            dump($objItems->related_projects);
            $arrRelatedProjects = explode(',', $objItems->related_projects);
            $arrNewProjects = [];
            dump($arrRelatedProjects);

            foreach ($arrRelatedProjects as $p) {
                $objItem = $db->prepare('SELECT * FROM tl_wem_portfolio_item WHERE id = ?')->limit(1)->execute($p);
                $r = $objItem->row();

                dump($r);

                if ('en' === $r['i18nl10n_lang']) {
                    $objItemL10n = PortfolioL10n::findOneByTitle($r['title']);
                    $arrNewProjects[] = $objItemL10n->pid;
                } else {
                    $objItem = Portfolio::findOneByTitle($r['title']);
                    $arrNewProjects[] = $objItem->id;
                }
            }

            $arrNewProjects = array_values(array_unique($arrNewProjects));
            dump($arrNewProjects);

            $db->prepare('UPDATE tl_iso_product SET related_projects = "'.implode(',', $arrNewProjects).'" WHERE id = ?')->execute($objItems->id);

        }

        return new Response('Migrate 2');
    }

    /**
     * @Route("/migrate")
     */
    public function migrate(Request $request): Response
    {
        return new Response('Migrate 1');
        
        $db = \Database::getInstance();
        $objItems = $db->prepare('SELECT * FROM tl_wem_portfolio_item WHERE i18nl10n_lang = "en" AND i18nl10n_id = 0')->execute();
        $arrRequests = [];

        while ($objItems->next()) {
            dump($objItems->row());

            $objItemAttributesL10n = $db->prepare('SELECT * FROM tl_wem_portfolio_item_attribute WHERE pid = ?')->execute($objItems->id);
            while ($objItemAttributesL10n->next()) {
                $r = $objItemAttributesL10n->row();

                $objAttribute = $db->prepare('SELECT * FROM tl_wem_portfolio_attribute WHERE id = ?')->execute($objItemAttributesL10n->attribute);
                $r['attribute'] = $objAttribute->title;

                dump($r);
            }

            continue;

            $arrPictures = unserialize($objItems->orderPictures);
            $mainPicture = $arrPictures[0];

            $objNewItem = new Portfolio();
            $objNewItem->pid = 1;
            $objNewItem->tstamp = $objItems->tstamp;
            $objNewItem->createdAt = $objItems->createdAt;
            $objNewItem->title = $objItems->title;
            $objNewItem->slug = $objItems->alias;
            $objNewItem->date = $objItems->date;
            $objNewItem->pictures = $objItems->pictures;
            $objNewItem->orderPictures = $objItems->orderPictures;
            $objNewItem->teaser = $objItems->teaser;
            $objNewItem->published = $objItems->published;
            $objNewItem->published = $objItems->published;
            $objNewItem->start = $objItems->start;
            $objNewItem->stop = $objItems->stop;
            $objNewItem->singleSRC = $mainPicture;
            $objNewItem->alt = $objItems->title;
            $objNewItem->imageTitle = $objItems->title;

            $objItemAttributes = $db->prepare('SELECT * FROM tl_wem_portfolio_item_attribute WHERE pid = ?')->execute($objItems->id);

            while ($objItemAttributes->next()) {                
                switch($objItemAttributes->attribute) {
                    case 1: $objNewItem->client = $objItemAttributes->value; break;
                    case 2: $objNewItem->maitreouvrage = $objItemAttributes->value; break;
                    case 3: $objNewItem->solutions = $objItemAttributes->value; break;
                    case 5: $objNewItem->dureetravaux = $objItemAttributes->value; break;
                    case 6: $objNewItem->localisation = $objItemAttributes->value; break;
                    case 7: $objNewItem->emplacement = $objItemAttributes->value; break;
                }
            }

            //$objNewItem->save();
            $sql = 'UPDATE tl_content SET pid = ' . $objNewItem->id . ', ptable = "tl_wem_portfolio", wem_language = "fr" WHERE ptable = "tl_wem_portfolio_item" AND pid = ' . $objItems->id;
            $arrRequests[] = $sql;
            //$db->query($sql);

            // dump($objNewItem);

            if (0 !== $objItems->i18nl10n_id) {
                $objItemEn = $db->prepare('SELECT * FROM tl_wem_portfolio_item WHERE id = ? AND i18nl10n_lang = "en"')->execute($objItems->i18nl10n_id);

                // dump($objItemEn->row());

                $objNewItemL10n = new PortfolioL10n();
                $objNewItemL10n->tstamp = $objItemEn->tstamp;
                $objNewItemL10n->pid = $objNewItem->id;
                $objNewItemL10n->createdAt = $objItemEn->createdAt;
                $objNewItemL10n->language = 'en';
                $objNewItemL10n->title = $objItemEn->title;
                $objNewItemL10n->teaser = $objItemEn->teaser;

                $objItemAttributesL10n = $db->prepare('SELECT * FROM tl_wem_portfolio_item_attribute WHERE pid = ?')->execute($objItemEn->id);

                while ($objItemAttributesL10n->next()) {                
                    switch($objItemAttributesL10n->attribute) {
                        case 4: $objNewItemL10n->solutions = $objItemAttributesL10n->value; break;
                        case 14: $objNewItemL10n->localisation = $objItemAttributesL10n->value; break;
                        case 9: $objNewItemL10n->emplacement = $objItemAttributesL10n->value; break;
                        case 10: $objNewItemL10n->dureetravaux = $objItemAttributesL10n->value; break;
                        case 8: $objNewItemL10n->client = $objItemAttributesL10n->value; break;
                        case 12: $objNewItemL10n->maitreouvrage = $objItemAttributesL10n->value; break;
                    }
                }

                //$objNewItemL10n->save();

                $sql = 'UPDATE tl_content SET pid = ' . $objNewItemL10n->id. ', ptable = "tl_wem_portfolio", wem_language = "en" WHERE ptable = "tl_wem_portfolio_item" AND pid = ' . $objItemEn->id;
                $arrRequests[] = $sql;
                //$db->query($sql);

                // dump($objNewItemL10n);
            }
        }

        echo implode('/n', $arrRequests);

        return new Response('Migrate 1');
    }

    /**
     * @Route("/doc", methods={"GET"})
     */
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

    /**
     * @Route("/items/{page}/{limit}", requirements={"page"="\d+","limit"="\d+"}), methods={"GET"})
     */
    public function viewPortfolioList(Request $request, int $page, int $limit, array $pid = []): JsonResponse
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

        $pid = $request->query->all('pid');
        $lang = $request->query->get('lang') ?: $GLOBALS['TL_LANGUAGE'];

        $offset = ($page - 1) * $limit;
        if (!is_iterable($pid)) {
            return new JsonResponse('{"error":"Give at least one category : ?pid[]=1&pid[]=2"}', Response::HTTP_NOT_ACCEPTABLE, [], true);
        }

        $items = [];

        foreach ($pid as $category) {
            $objCategory = PortfolioFeed::findByIdOrAlias($category);
            if (!$objCategory) {
                return new JsonResponse('{"error":"Category '.$category.' not found"}', Response::HTTP_I_AM_A_TEAPOT, [], true);
            }

            $base = Environment::get('base');

            $objItems = Portfolio::findItems(['pid' => $objCategory->id, 'published' => '1'], $limit, $offset);
            if ($objItems instanceof Collection) {
                /** @var Portfolio $item */
                foreach ($objItems as $item) {
                    if (!$item->published) {
                        continue;
                    }

                    $items[$item->id] = $this->prepareItem($item, $lang);
                }

                return new JsonResponse($items, Response::HTTP_OK);
            }
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

        $pid = $request->query->all('pid');

        if (!is_iterable($pid)) {
            return new JsonResponse('{"error":"Give at least on category : ?pid[]=1&pid[]=2"}', Response::HTTP_NOT_ACCEPTABLE, [], true);
        }

        return new JsonResponse(['items' => Portfolio::countItems(['pid' => $pid])], Response::HTTP_OK);
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
        $lang = $request->query->get('lang') ?: $GLOBALS['TL_LANGUAGE'];

        if ($objItem instanceof Portfolio) {
            if ($objItem->published) {
                $return = $this->prepareItem($objItem, $lang, true);

                return new JsonResponse($return, Response::HTTP_OK);
            }

            return new JsonResponse('{"error":"403 : Item not published"}', Response::HTTP_FORBIDDEN, [], true);
        }

        return new JsonResponse('{"error":"404 : Item not found"}', Response::HTTP_NOT_FOUND, [], true);
    }

    protected function prepareItem(Portfolio $item, string $lang = null, bool $getContent = false): array
    {
        $arrayItem = $item->row();
        $id = $arrayItem['id'];
        $return = [];
        $return['singleSRC'] = [];
        $return['pictures'] = [];
        if ('1' === $arrayItem['published']) {
            foreach($arrayItem as $c => $v) {
                switch ($c) {
                    case 'singleSRC':
                        $imageP = FilesModel::findByUuid($arrayItem['singleSRC']);
                        $uuidP = Uuid::fromBinary($imageP->uuid);
                        $return['singleSRC']['uuid'] = $base . $imageP->path;
                        $return['singleSRC']['path'] = $base . $imageP->path;
                        $return['singleSRC']['extension'] = $imageP->extension;
                        $return['singleSRC']['tstamp'] = $imageP->tstamp;
                        $return['singleSRC']['hash'] = $imageP->hash;
                        $return['singleSRC']['lastModified'] = $imageP->lastModified;
                        $return['singleSRC']['basename'] = $imageP->basename;
                        $return['singleSRC']['main'] = true;
                        break;

                    case 'pictures':
                        $arrPictures = deserialize($v);
                        foreach ($v as $uuid) {
                            $imageP = FilesModel::findByUuid($uuid);
                            $uuidP = Uuid::fromBinary($imageP->uuid);
                            $return['pictures'][$uuidP->__toString()]['uuid'] = $uuidP->__toString();
                            $return['pictures'][$uuidP->__toString()]['path'] = $base . $imageP->path;
                            $return['pictures'][$uuidP->__toString()]['extension'] = $imageP->extension;
                            $return['pictures'][$uuidP->__toString()]['tstamp'] = $imageP->tstamp;
                            $return['pictures'][$uuidP->__toString()]['hash'] = $imageP->hash;
                            $return['pictures'][$uuidP->__toString()]['lastModified'] = $imageP->lastModified;
                            $return['pictures'][$uuidP->__toString()]['basename'] = $imageP->basename;
                            $return['pictures'][$uuidP->__toString()]['main'] = false;
                        }
                    break;

                    case 'pid':
                        $arrayCategory = $item->getRelated('pid')->row();
                        $return['category']['id'] = $arrayCategory['id'];
                        $return['category']['createdAt'] = $arrayCategory['createdAt'];
                        $return['category']['tstamp'] = $arrayCategory['tstamp'];
                        $return['category']['title'] = $arrayCategory['title'];
                        $return['category']['alias'] = $arrayCategory['alias'];
                    break;

                    case 'size':
                    case 'imagemargin':
                    case 'orderPictures':
                        // skip fields
                    break;
                    
                    default:
                        // Try to find a matching attribute
                        $varValue = $item->getAttributeValue($c);

                        $return[$c] = $varValue ?: $v;
                        break;
                }
            }
        }

        $return['attributes'] = $item->getAttributesFull([], $lang);

        if ($getContent) {
            $strContent = '';
            $objElement = ContentModel::findPublishedByPidAndTable($id, 'tl_wem_portfolio') ?? [];

            foreach ($objElement as $element) {
                $strContent .= Controller::getContentElement($element);
            }

            $return['content_b64'] = base64_encode($strContent);
        }

        return $return;
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
