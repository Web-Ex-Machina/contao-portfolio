<?php

declare(strict_types=1);

namespace WEM\PortfolioBundle\Routing;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Content\ContentUrlResolverInterface;
use Contao\CoreBundle\Routing\Content\ContentUrlResult;
use Contao\PageModel;
use WEM\PortfolioBundle\Model\Portfolio;
use WEM\PortfolioBundle\Model\PortfolioFeed;

class PortfolioResolver implements ContentUrlResolverInterface
{
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    public function resolve(object $content): ContentUrlResult|null
    {
        if (!$content instanceof Portfolio) {
            return null;
        }

        $pageAdapter = $this->framework->getAdapter(PageModel::class);
        $archiveAdapter = $this->framework->getAdapter(PortfolioFeed::class);

        // Link to the default page
        return ContentUrlResult::resolve($pageAdapter->findById((int) $archiveAdapter->findById($content->pid)?->jumpTo));
    }

    public function getParametersForContent(object $content, PageModel $pageModel): array
    {
        if (!$content instanceof Portfolio) {
            return [];
        }

        $params = \sprintf(
            '/category/%s/item/%s',
            $content->getRelated('pid')->alias,
            $content->slug ?: $content->id,
        );

        return ['parameters' => $params];
    }
}
