<?php

declare(strict_types=1);

/**
 * Contao Portfolio for Contao Open Source CMS
 * Copyright (c) 2015-2025 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-portfolio
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-portfolio/
 */

namespace WEM\PortfolioBundle\Controller\Frontend;

use Contao\BackendTemplate;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\ContaoPageSchema;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;
use Contao\CoreBundle\Util\UrlUtil;
use Contao\Environment;
use Contao\Input;
use Contao\PageModel;
use Contao\ModuleModel;
use Contao\System;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WEM\PortfolioBundle\Model\Portfolio;
use WEM\PortfolioBundle\Model\PortfolioFeed;
use WEM\PortfolioBundle\Model\PortfolioL10n;
use WEM\UtilsBundle\Classes\StringUtil;

#[AsFrontendModule(
    RemoteReaderModuleController::TYPE, 
    category: 'wem_portfolio',
    template: 'mod_wem_portfolio_reader'
)]
class RemoteReaderModuleController extends ReaderModuleController
{
    public const TYPE = 'wem_portfolio_remote_reader';

    protected ?Portfolio $portfolio = null;
    protected ?PortfolioFeed $feed = null;
    protected ModuleModel $model;

    public function __construct(
        private readonly ContentUrlGenerator $contentUrlGenerator
    ) {
        parent::__construct($contentUrlGenerator);
    }

    protected function findItem(): ?Portfolio
    {
        $api = $this->getApi();
        
        if (null === $api) {
            throw new Exception("The API is not reachable");
        }

        return $api->getItem(Input::get('item'));
    }
}
