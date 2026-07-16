<?php

declare(strict_types=1);

namespace WEM\PortfolioBundle\Routing;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Content\ContentUrlResolverInterface;
use Contao\CoreBundle\Routing\Content\ContentUrlResult;
use Contao\PageModel;
use Contao\System;
use Terminal42\ChangeLanguage\PageFinder;
use Symfony\Component\HttpFoundation\RequestStack;
use WEM\PortfolioBundle\Model\Portfolio;
use WEM\PortfolioBundle\Model\PortfolioFeed;
use WEM\PortfolioBundle\Service\PortfolioService;

class PortfolioResolver implements ContentUrlResolverInterface
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly RequestStack $requestStack,
        private readonly PortfolioService $service
    ) {
    }

    public function resolve(object $content): ContentUrlResult|null
    {
        if (!$content instanceof Portfolio) {
            return null;
        }

        $pageAdapter = $this->framework->getAdapter(PageModel::class);
        $archiveAdapter = $this->framework->getAdapter(PortfolioFeed::class);
        $locale = $this->requestStack->getCurrentRequest()->getLocale();

        $objMaster = $pageAdapter->findById((int) $archiveAdapter->findById($content->pid)?->jumpTo);
        
        if ($objMaster) {
            $objTarget = (new PageFinder())->findAssociatedForLanguage($objMaster, $locale);
        } else {
            $objTarget = $objMaster;
        }

        // Link to the default page
        return ContentUrlResult::resolve($objTarget);
    }

    public function getParametersForContent(object $content, PageModel $pageModel): array
    {
        if (!$content instanceof Portfolio) {
            return [];
        }

        $this->service->load($content);

        $params = \sprintf(
            '/category/%s/item/%s',
            $content->getRelated('pid')->alias,
            $this->service->getField('slug') ?: $content->id,
        );

        return ['parameters' => $params];
    }
}
