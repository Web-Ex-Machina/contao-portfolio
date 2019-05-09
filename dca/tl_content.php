<?php

/**
 * Module Portfolio for Contao Open Source CMS
 *
 * Copyright (c) 2015-2019 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

// Dynamically add the permission check and parent table
if (Input::get('do') == 'wem_portfolio_item') {
    $GLOBALS['TL_DCA']['tl_content']['config']['ptable'] = 'tl_wem_portfolio_item';
}
