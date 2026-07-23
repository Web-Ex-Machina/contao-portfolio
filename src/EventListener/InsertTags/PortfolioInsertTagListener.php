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

namespace WEM\PortfolioBundle\EventListener\InsertTags;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\FilesModel;
use Contao\Input;
use WEM\PortfolioBundle\Model\Portfolio;
use WEM\PortfolioBundle\Service\PortfolioService;

class PortfolioInsertTagListener
{
    public const TAG = 'portfolio';

    public function __construct(
        private readonly PortfolioService $service
    ) {
    }

    /**
     * Examples:
     * {{portfolio::title}}
     * {{portfolio::title::1}}
     * 
     * @TODO: Handle translated tags
     * @TODO: Handle all attributes (probably with service)
     */
    #[AsHook('replaceInsertTags', priority: 100)]
    public function replaceInsertTags(string $tag)
    {
        $chunks = explode('::', $tag);

        if (self::TAG !== $chunks[0]) {
            return false;
        }

        // Check if we want a specific portfolio or the current one
        $varItem = (3 === \count($chunks)) ? $chunks[2] : Input::get('item');

        try {
            $this->service->load($varItem);
        } catch (Exception $e) {
            return '';
        }

        // Specific behavior for singleSRC
        if ('singleSRC' === $chunks[1]) {
            $objFile = FilesModel::findByUuid($this->service->getField($chunks[1]));

            return $objFile->path;
        }

        return $this->service->getField($chunks[1]);
    }
}
