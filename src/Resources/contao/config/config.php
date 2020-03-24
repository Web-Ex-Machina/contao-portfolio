<?php

declare(strict_types=1);

/**
 * Contao Portfolio for Contao Open Source CMS
 * Copyright (c) 2015-2020 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-portfolio
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-portfolio/
 */

/**
 * Load Contao 4 Bundles.
 */
$bundles = \System::getContainer()->getParameter('kernel.bundles');

/*
 * Back end modules
 */
array_insert(
    $GLOBALS['BE_MOD'],
    1,
    [
        'wem_portfolio' => [
            'wem_portfolio_item' => [
                'tables' => ['tl_wem_portfolio_item', 'tl_wem_portfolio_item_page', 'tl_wem_portfolio_item_attribute', 'tl_content'],
                'icon' => 'system/modules/wem-portfolio/assets/icon_item.png',
            ],
            'wem_portfolio_attribute' => [
                'tables' => ['tl_wem_portfolio_attribute'],
                'icon' => 'system/modules/wem-portfolio/assets/icon_tag.png',
            ],
        ],
    ]
);

// Load icon in Contao 4.2 backend
if ('BE' === TL_MODE) {
    if (version_compare(VERSION, '4.4', '<')) {
        $GLOBALS['TL_CSS'][] = 'system/modules/wem-contao-portfolio/assets/backend.css';
    } else {
        $GLOBALS['TL_CSS'][] = 'system/modules/wem-contao-portfolio/assets/backend_svg.css';
    }
}

/*
 * Front end modules
 */
array_insert(
    $GLOBALS['FE_MOD'],
    2,
    [
        'wem_portfolio' => [
            'wem_portfolio_list' => 'WEM\PortfolioBundle\Module\PortfolioList',
            'wem_portfolio_reader' => 'WEM\PortfolioBundle\Module\PortfolioReader',
        ],
    ]
);

/*
 * Hooks
 */
$GLOBALS['TL_HOOKS']['getSearchablePages'][] = [\WEM\PortfolioBundle\Hooks\GetSearchablePagesListener::class, 'onGetSearchablePages'];
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = [\WEM\PortfolioBundle\Hooks\ReplaceInsertTagsListener::class, 'onReplaceInsertTags'];

/*
 * Models
 */
$GLOBALS['TL_MODELS']['tl_wem_portfolio_item'] = 'WEM\PortfolioBundle\Model\Item';
$GLOBALS['TL_MODELS']['tl_wem_portfolio_item_attribute'] = 'WEM\PortfolioBundle\Model\ItemAttribute';
$GLOBALS['TL_MODELS']['tl_wem_portfolio_item_page'] = 'WEM\PortfolioBundle\Model\ItemPage';
$GLOBALS['TL_MODELS']['tl_wem_portfolio_attribute'] = 'WEM\PortfolioBundle\Model\Attribute';

/*
 * i18nl10n specific items
 */
if (\array_key_exists('VerstaerkerI18nl10nBundle', $bundles)) {
    // Hooks
    $GLOBALS['TL_HOOKS']['i18nl10nUpdateLanguageSelectionItem'][] = ["WEM\PortfolioBundle\Controller\Item", 'getFrontendUrl'];

    // Wizards
    $GLOBALS['BE_FFL']['i18nl10nAssociatedLocationsWizard'] = 'WEM\PortfolioBundle\Widget\I18nl10nAssociatedLocationsWizard';
}
