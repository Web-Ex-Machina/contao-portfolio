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
 * Register the templates.
 */
TemplateLoader::addFiles(
    [
        'mod_wem_portfolio_list' => 'system/modules/wem-portfolio/templates/modules',
        'mod_wem_portfolio_reader' => 'system/modules/wem-portfolio/templates/modules',
        'wem_portfolio_item' => 'system/modules/wem-portfolio/templates/elements',
        'wem_portfolio_full' => 'system/modules/wem-portfolio/templates/elements',
    ]
);
