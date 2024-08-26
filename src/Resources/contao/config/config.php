<?php

declare(strict_types=1);

/**
 * Contao Portfolio for Contao Open Source CMS
 * Copyright (c) 2015-2023 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-portfolio
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-portfolio/
 */

use Contao\ArrayUtil;
use Contao\System;
use WEM\PortfolioBundle\Hooks;
use WEM\PortfolioBundle\Model;
use WEM\PortfolioBundle\Module;

/**
 * Load Contao 4 Bundles.
 */
$bundles = System::getContainer()->getParameter('kernel.bundles');
$scopeMatcher = System::getContainer()->get('wem.scope_matcher');

/*
 * Back end modules
 */
ArrayUtil::arrayInsert(
    $GLOBALS['BE_MOD'],
    1,
    [
        'wem_portfolio' => [
            'wem_portfolio_feed' => [
                'tables' => ['tl_wem_portfolio_feed', 'tl_wem_portfolio', 'tl_wem_portfolio_feed_attribute', 'tl_content'],
            ],
        ],
    ]
);

// Load icon in Contao backend
if ($scopeMatcher->isBackend()) {
    $GLOBALS['TL_CSS'][] = 'bundles/wemportfolio/backend_svg.css';
}

/*
 * Front end modules
 */
ArrayUtil::arrayInsert(
    $GLOBALS['FE_MOD'],
    2,
    [
        'wem_portfolio' => [
            'wem_portfolio_list' => Module\ModulePortfoliosList::class,
            'wem_portfolio_reader' => Module\ModulePortfoliosReader::class,
            'wem_portfolio_filters' => Module\ModulePortfoliosFilters::class,
        ],
    ]
);


/*
 * Models
 */
$GLOBALS['TL_MODELS']['tl_wem_portfolio_feed_attribute'] = Model\PortfolioFeedAttribute::class;
$GLOBALS['TL_MODELS']['tl_wem_portfolio_feed'] = Model\PortfolioFeed::class;
$GLOBALS['TL_MODELS']['tl_wem_portfolio'] = Model\Portfolio::class;
