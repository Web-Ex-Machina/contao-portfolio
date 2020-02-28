<?php

/**
 * Module Portfolio for Contao Open Source CMS
 *
 * Copyright (c) 2015-2018 Web ex Machina
 *
 * @author Web ex Machina <http://www.webexmachina.fr>
 */


/**
 * Table tl_wem_portfolio_attribute
 */
$GLOBALS['TL_DCA']['tl_wem_portfolio_attribute'] = array(

    // Config
    'config' => array(
        'dataContainer'               => 'Table',
        'switchToEdit'                => true,
        'enableVersioning'            => true,
        'sql' => array(
            'keys' => array(
                'id' => 'primary',
            )
        )
    ),

    // List
    'list' => array(
        'sorting' => array(
            'mode'                    => 2,
            'fields'                  => array('title'),
            'flag'                    => 1,
            'panelLayout'             => 'filter;sort,search,limit'
        ),
        'label' => array(
            'fields'                  => array('title'),
            'format'                  => '%s'
        ),
        'global_operations' => array(
            'all' => array(
                'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href'                => 'act=select',
                'class'               => 'header_edit_all',
                'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            )
        ),
        'operations' => array(
            'edit' => array(
                'label'               => &$GLOBALS['TL_LANG']['tl_wem_portfolio_attribute']['edit'],
                'href'                => 'act=edit',
                'icon'                => 'edit.svg',
            ),
            'copy' => array(
                'label'               => &$GLOBALS['TL_LANG']['tl_wem_portfolio_attribute']['copy'],
                'href'                => 'act=copy',
                'icon'                => 'copy.svg',
            ),
            'delete' => array(
                'label'               => &$GLOBALS['TL_LANG']['tl_wem_portfolio_attribute']['delete'],
                'href'                => 'act=delete',
                'icon'                => 'delete.svg',
                'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"',
            ),
            'show' => array(
                'label'               => &$GLOBALS['TL_LANG']['tl_wem_portfolio_attribute']['show'],
                'href'                => 'act=show',
                'icon'                => 'show.svg'
            )
        )
    ),

    // Palettes
    'palettes' => array(
        'default'                     => '
            {title_legend},title,alias;
            {config_legend},useAsFilter,displayInFrontend
        '
    ),

    // Fields
    'fields' => array(
        'id' => array(
            'sql'                     => "int(10) unsigned NOT NULL auto_increment"
        ),
        'tstamp' => array(
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'created_on' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_wem_portfolio_attribute']['created_on'],
            'default'                 => time(),
            'flag'                    => 8,
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'title' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_wem_portfolio_attribute']['title'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'search'                  => true,
            'sorting'                 => true,
            'flag'                    => 1,
            'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'decodeEntities'=>true, 'tl_class'=>'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'alias' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_wem_portfolio_attribute']['alias'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => array('rgxp'=>'alias', 'doNotCopy'=>true, 'unique'=>true, 'maxlength'=>128, 'tl_class'=>'w50'),
            'save_callback' => array(
                array('tl_wem_portfolio_attribute', 'generateAlias')
            ),
            'sql'                     => "varchar(128) BINARY NOT NULL default ''"
        ),

        'useAsFilter' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_wem_portfolio_attribute']['useAsFilter'],
            'exclude'                 => true,
            'filter'                  => true,
            'flag'                    => 1,
            'inputType'               => 'checkbox',
            'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'w50'),
            'sql'                     => "char(1) NOT NULL default ''"
        ),
        'displayInFrontend' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_wem_portfolio_attribute']['displayInFrontend'],
            'exclude'                 => true,
            'filter'                  => true,
            'flag'                    => 1,
            'inputType'               => 'checkbox',
            'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'w50'),
            'sql'                     => "char(1) NOT NULL default ''"
        ),
    )
);

// Handle i18nl10n compatibility
$bundles = \System::getContainer()->getParameter('kernel.bundles');
if (array_key_exists("VerstaerkerI18nl10nBundle", $bundles)) {
    // Update palettes
    $GLOBALS['TL_DCA']['tl_wem_portfolio_attribute']['palettes']['default'] .= ';{i18nl10n_legend},i18nl10n_lang,i18nl10n_id';

    $GLOBALS['TL_DCA']['tl_wem_portfolio_attribute']['fields']['i18nl10n_id'] = array(
        'label'            => &$GLOBALS['TL_LANG']['tl_wem_portfolio_attribute']['i18nl10n_id'],
        'exclude'          => true,
        'inputType'        => 'i18nl10nAssociatedLocationsWizard',
        'eval'             => array('tl_class'=>'w50'),
        'sql'              => "int(10) unsigned NOT NULL default '0'"
    );
    $GLOBALS['TL_DCA']['tl_wem_portfolio_attribute']['fields']['i18nl10n_lang'] = array(
        'label'            => &$GLOBALS['TL_LANG']['MSC']['i18nl10n_fields']['language']['label'],
        'exclude'          => true,
        'filter'           => true,
        'inputType'        => 'select',
        'sorting'          => true,
        'flag'             => 11,
        'options_callback' => array('tl_wem_portfolio_attribute', 'getAvailableLanguages'),
        'reference'        => &$GLOBALS['TL_LANG']['LNG'],
        'eval'             => array(
            'mandatory'          => true,
            'rgxp'               => 'language',
            'maxlength'          => 5,
            'nospace'            => true,
            'doNotCopy'          => true,
            'tl_class'           => 'w50 clr',
            'includeBlankOption' => true
        ),
        'sql'              => "varchar(5) NOT NULL default ''"
    );
}

/**
 * Handle Portfolio Customers DCA functions
 *
 * @author Web ex Machina <contact@webexmachina.fr>
 */
class tl_wem_portfolio_attribute extends Backend
{

    /**
     * Import the back end user object
     */
    public function __construct()
    {
        parent::__construct();
        $this->import('BackendUser', 'User');
    }

    /**
     * Auto-generate the category alias if it has not been set yet
     *
     * @param mixed         $varValue
     * @param DataContainer $dc
     *
     * @return string
     *
     * @throws Exception
     */
    public function generateAlias($varValue, DataContainer $dc)
    {
        $autoAlias = false;

        // Generate an alias if there is none
        if ($varValue == '') {
            $autoAlias = true;
            $slugOptions = array();

            // Read the slug options from the associated page
            if (($objPage = PageModel::findWithDetails($dc->activeRecord->pages)) !== null) {
                $slugOptions = $objPage->getSlugOptions();
            }

            $varValue = System::getContainer()->get('contao.slug.generator')->generate(StringUtil::prepareSlug($dc->activeRecord->title), $slugOptions);

            // Prefix numeric aliases (see #1598)
            if (is_numeric($varValue)) {
                $varValue = 'id-' . $varValue;
            }
        }

        $objAlias = $this->Database->prepare("SELECT id FROM tl_wem_portfolio_attribute WHERE id=? OR alias=?")
                                   ->execute($dc->id, $varValue);

        // Check whether the page alias exists
        if ($objAlias->numRows > 1) {
            if (!$autoAlias) {
                throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
            }

            $varValue .= '-' . $dc->id;
        }

        return $varValue;
    }

    /**
     * Get available languages, for i18nl10n bundle
     *
     * @param DataContainer $dc [Contao DataContainer]
     *
     * @return Array            [Languages available, as Array]
     */
    public function getAvailableLanguages(DataContainer $dc)
    {
        $arrOptions = Verstaerker\I18nl10nBundle\Classes\I18nl10n::getInstance()->getAvailableLanguages(true, true);

        // Add neutral option if available
        if ($this->User->isAdmin || strpos(implode((array) $this->User->i18nl10n_languages), '::*') !== false) {
            array_unshift($arrOptions, '');
        }

        return $arrOptions;
    }
}
