<?php

declare(strict_types=1);

namespace WEM\PortfolioBundle\Service;

use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Exception;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use WEM\PortfolioBundle\Model\Portfolio;

class PortfolioService
{
    protected Portfolio $model;

    public function __construct(
        private readonly ContentUrlGenerator $contentUrlGenerator
    ) {
    }

    /**
     * Load an item
     * 
     * @param int|string|Portfolio - Model to load
     */
    public function load(int|string|Portfolio $var): void
    {
        if ($var instanceof Portfolio) {
            $this->model = $var;
        } else {
            try {
                $this->model = Portfolio::findByIdOrSlug($var);
            } catch (Exception $e) {
                throw $e;
            }
        }
    }

    /**
     * Generate item URL
     * 
     * @param array - Params to add
     * @param int - URL format (check UrlGeneratorInterface)
     * 
     * @return string
     */
    public function getUrl(array $params = [], int $format = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        return $this->contentUrlGenerator->generate(
            $this->model,
            $params, 
            $format,
        );
    }
}
