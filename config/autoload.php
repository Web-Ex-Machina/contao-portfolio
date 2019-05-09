<?php

/**
 * Module Portfolio for Contao Open Source CMS
 *
 * Copyright (c) 2015-2019 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

/**
 * Register the templates
 */
TemplateLoader::addFiles(
    array(
        'mod_wem_portfolio_list' => 'system/modules/wem-portfolio/templates/modules',
        'mod_wem_portfolio_reader' => 'system/modules/wem-portfolio/templates/modules',
        'wem_portfolio_item' => 'system/modules/wem-portfolio/templates/elements',
        'wem_portfolio_full' => 'system/modules/wem-portfolio/templates/elements',
    )
);
