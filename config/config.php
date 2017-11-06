<?php

/**
 * Module Portfolio for Contao Open Source CMS
 *
 * Copyright (c) 2015-2017 Web ex Machina
 *
 * @author Web ex Machina <http://www.webexmachina.fr>
 */

/**
 * Back end modules
 */
array_insert($GLOBALS['BE_MOD']['wem_portfolio'], 1, array
(
	'wem_portfolio_item' => array
	(
		'tables'    => array('tl_wem_portfolio_item', 'tl_wem_portfolio_item_testimonial'),
		'icon'		=> 'system/modules/wem-portfolio/assets/icon_item.png'
	),
	'wem_portfolio_customer' => array
	(
		'tables'    => array('tl_wem_portfolio_customer', 'tl_wem_portfolio_item'),
		'icon'		=> 'system/modules/wem-portfolio/assets/icon_customer.png'
	),
	'wem_portfolio_category' => array
	(
		'tables'    => array('tl_wem_portfolio_category'),
		'icon'		=> 'system/modules/wem-portfolio/assets/icon_category.png'
	),
	'wem_portfolio_tag' => array
	(
		'tables'    => array('tl_wem_portfolio_tag'),
		'icon'		=> 'system/modules/wem-portfolio/assets/icon_tag.png'
	),
));

// Load icon in Contao 4.2 backend
if ('BE' === TL_MODE) {
    if (version_compare(VERSION, '4.4', '<')) {
        $GLOBALS['TL_CSS'][] = 'system/modules/wem-portfolio/assets/backend.css';
    } else {
        $GLOBALS['TL_CSS'][] = 'system/modules/wem-portfolio/assets/backend_svg.css';
    }
}

/**
 * Front end modules
 */
array_insert($GLOBALS['FE_MOD'], 2, array
(
	'wem_portfolio' => array
	(
		'wem_portfolio_list' 		=> 'WEM\Portfolio\Module\PortfolioList',
		'wem_portfolio_reader'  	=> 'WEM\Portfolio\Module\PortfolioReader',
	)
));

/**
 * Models
 */
$GLOBALS['TL_MODELS']["tl_wem_portfolio_item"] 				= 'WEM\Portfolio\Model\Item';
$GLOBALS['TL_MODELS']["tl_wem_portfolio_item_testimonial"] 	= 'WEM\Portfolio\Model\Item\Testimonial';
$GLOBALS['TL_MODELS']["tl_wem_portfolio_category"] 			= 'WEM\Portfolio\Model\Category';
$GLOBALS['TL_MODELS']["tl_wem_portfolio_customer"] 			= 'WEM\Portfolio\Model\Customer';
$GLOBALS['TL_MODELS']["tl_wem_portfolio_tag"] 				= 'WEM\Portfolio\Model\Tag';