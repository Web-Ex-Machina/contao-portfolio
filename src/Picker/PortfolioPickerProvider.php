<?php

declare(strict_types=1);

namespace WEM\PortfolioBundle\Picker;

use Contao\CoreBundle\DependencyInjection\Attribute\AsPickerProvider;
use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\CoreBundle\Picker\AbstractInsertTagPickerProvider;
use Contao\CoreBundle\Picker\DcaPickerProviderInterface;
use Contao\CoreBundle\Picker\PickerConfig;
use WEM\PortfolioBundle\Model\Portfolio;
use WEM\PortfolioBundle\Model\PortfolioFeed;
use Knp\Menu\FactoryInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsPickerProvider(priority: 128)]
class PortfolioPickerProvider extends AbstractInsertTagPickerProvider implements DcaPickerProviderInterface, FrameworkAwareInterface
{
    use FrameworkAwareTrait;

    /**
     * @internal
     */
    public function __construct(
        FactoryInterface $menuFactory,
        RouterInterface $router,
        TranslatorInterface|null $translator,
        private readonly Security $security,
    ) {
        parent::__construct($menuFactory, $router, $translator);
    }

    public function getName(): string
    {
        return 'portfolioPicker';
    }

    public function supportsContext(string $context): bool
    {
        return 'link' === $context && $this->security->isGranted('contao_user.modules', 'portfolio');
    }

    public function supportsValue(PickerConfig $config): bool
    {
        return $this->isMatchingInsertTag($config);
    }

    public function getDcaTable(PickerConfig|null $config = null): string
    {
        return 'tl_wem_portfolio';
    }

    public function getDcaAttributes(PickerConfig $config): array
    {
        $attributes = ['fieldType' => 'radio'];

        if ($this->supportsValue($config)) {
            $attributes['value'] = $this->getInsertTagValue($config);

            if ($flags = $this->getInsertTagFlags($config)) {
                $attributes['flags'] = $flags;
            }
        }

        return $attributes;
    }

    public function convertDcaValue(PickerConfig $config, mixed $value): string
    {
        return \sprintf($this->getInsertTag($config), $value);
    }

    protected function getRouteParameters(PickerConfig|null $config = null): array
    {
        $params = ['do' => 'wem_portfolio_feed'];

        if (!$config?->getValue() || !$this->supportsValue($config)) {
            return $params;
        }

        if (null !== ($portfolioFeedId = $this->getPortfolioFeedId($this->getInsertTagValue($config)))) {
            $params['table'] = 'tl_wem_portfolio';
            $params['id'] = $portfolioFeedId;
        }

        return $params;
    }

    protected function getDefaultInsertTag(): string
    {
        return '{{portfolio::url::%s}}';
    }

    private function getPortfolioFeedId(int|string $id): int|null
    {
        $portfolioAdapter = $this->framework->getAdapter(Portfolio::class);

        if (!$portfolioModel = $portfolioAdapter->findById($id)) {
            return null;
        }

        if (!$portfolioFeed = $this->framework->getAdapter(PortfolioFeed::class)->findById($portfolioModel->pid)) {
            return null;
        }

        return (int) $portfolioFeed->id;
    }
}
