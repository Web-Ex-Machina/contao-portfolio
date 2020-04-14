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

/*
 * Table tl_wem_portfolio_attribute.
 */
$GLOBALS['TL_DCA']['tl_wem_portfolio_attribute'] = [
    // Config
    'config' => [
        'dataContainer' => 'Table',
        'switchToEdit' => true,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
    ],

    // List
    'list' => [
        'sorting' => [
            'mode' => 2,
            'fields' => ['title'],
            'flag' => 1,
            'panelLayout' => 'filter;sort,search,limit',
        ],
        'label' => [
            'fields' => ['title'],
            'format' => '%s',
        ],
        'global_operations' => [
            'all' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations' => [
            'edit' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_attribute']['edit'],
                'href' => 'act=edit',
                'icon' => 'edit.svg',
            ],
            'copy' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_attribute']['copy'],
                'href' => 'act=copy',
                'icon' => 'copy.svg',
            ],
            'delete' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_attribute']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\''.$GLOBALS['TL_LANG']['MSC']['deleteConfirm'].'\'))return false;Backend.getScrollOffset()"',
            ],
            'show' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_attribute']['show'],
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],
        ],
    ],

    // Palettes
    'palettes' => [
        '__selector__' => ['type'],
        'default' => '
            {title_legend},title,alias;
            {values_legend},type;
            {config_legend},useAsFilter,displayInFrontend
        ',
    ],

    // Subpalettes
    'subpalettes' => [
        'type_select' => 'options',
    ],

    // Fields
    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'created_on' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_attribute']['created_on'],
            'default' => time(),
            'flag' => 8,
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'title' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_attribute']['title'],
            'exclude' => true,
            'inputType' => 'text',
            'search' => true,
            'sorting' => true,
            'flag' => 1,
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'alias' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_attribute']['alias'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'alias', 'doNotCopy' => true, 'unique' => true, 'maxlength' => 128, 'tl_class' => 'w50'],
            'save_callback' => [
                ['tl_wem_portfolio_attribute', 'generateAlias'],
            ],
            'sql' => "varchar(128) BINARY NOT NULL default ''",
        ],

        'type' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_attribute']['type'],
            'exclude' => true,
            'default' => 'text',
            'filter' => true,
            'flag' => 11,
            'inputType' => 'select',
            'options' => ['text', 'select'],
            'reference' => $GLOBALS['TL_LANG']['tl_wem_portfolio_attribute']['type'],
            'eval' => ['chosen' => true, 'mandatory' => true, 'tl_class' => 'w50', 'submitOnChange' => true],
            'sql' => "varchar(16) NOT NULL default 'text'",
        ],
        'options' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_attribute']['options'],
            'exclude' => true,
            'inputType' => 'listWizard',
            'eval' => ['mandatory' => true, 'allowHtml' => true],
            'sql' => 'blob NULL',
        ],

        'useAsFilter' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_attribute']['useAsFilter'],
            'exclude' => true,
            'filter' => true,
            'flag' => 1,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true, 'tl_class' => 'w50'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'displayInFrontend' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_attribute']['displayInFrontend'],
            'exclude' => true,
            'filter' => true,
            'flag' => 1,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true, 'tl_class' => 'w50'],
            'sql' => "char(1) NOT NULL default ''",
        ],
    ],
];

// Handle i18nl10n compatibility
$bundles = \System::getContainer()->getParameter('kernel.bundles');
if (\array_key_exists('VerstaerkerI18nl10nBundle', $bundles)) {
    \System::loadLanguageFile('languages');
    // Update palettes
    $GLOBALS['TL_DCA']['tl_wem_portfolio_attribute']['palettes']['default'] .= ';{i18nl10n_legend},i18nl10n_lang,i18nl10n_id';

    $GLOBALS['TL_DCA']['tl_wem_portfolio_attribute']['fields']['i18nl10n_id'] = [
        'label' => &$GLOBALS['TL_LANG']['tl_wem_portfolio_attribute']['i18nl10n_id'],
        'exclude' => true,
        'inputType' => 'i18nl10nAssociatedLocationsWizard',
        'eval' => ['tl_class' => 'w50'],
        'sql' => "int(10) unsigned NOT NULL default '0'",
    ];
    $GLOBALS['TL_DCA']['tl_wem_portfolio_attribute']['fields']['i18nl10n_lang'] = [
        'label' => &$GLOBALS['TL_LANG']['MSC']['i18nl10n_fields']['language']['label'],
        'exclude' => true,
        'filter' => true,
        'inputType' => 'select',
        'sorting' => true,
        'flag' => 11,
        'options_callback' => ['tl_wem_portfolio_attribute', 'getAvailableLanguages'],
        'reference' => &$GLOBALS['TL_LANG']['LNG'],
        'eval' => [
            'mandatory' => true,
            'rgxp' => 'language',
            'maxlength' => 5,
            'nospace' => true,
            'doNotCopy' => true,
            'tl_class' => 'w50 clr',
            'includeBlankOption' => true,
        ],
        'sql' => "varchar(5) NOT NULL default ''",
    ];
}

/**
 * Handle Portfolio Customers DCA functions.
 *
 * @author Web ex Machina <contact@webexmachina.fr>
 */
class tl_wem_portfolio_attribute extends Backend
{
    /**
     * Import the back end user object.
     */
    public function __construct()
    {
        parent::__construct();
        $this->import('BackendUser', 'User');
    }

    /**
     * Auto-generate the category alias if it has not been set yet.
     *
     * @throws Exception
     *
     * @return string
     */
    public function generateAlias($varValue, DataContainer $dc)
    {
        $autoAlias = false;

        // Generate an alias if there is none
        if ('' === $varValue) {
            $autoAlias = true;
            $slugOptions = [];

            // Read the slug options from the associated page
            if (null !== ($objPage = PageModel::findWithDetails($dc->activeRecord->pages))) {
                $slugOptions = $objPage->getSlugOptions();
            }

            $varValue = System::getContainer()->get('contao.slug.generator')->generate(StringUtil::prepareSlug($dc->activeRecord->title), $slugOptions);

            // Prefix numeric aliases (see #1598)
            if (is_numeric($varValue)) {
                $varValue = 'id-'.$varValue;
            }
        }

        $objAlias = $this->Database->prepare('SELECT id FROM tl_wem_portfolio_attribute WHERE id=? OR alias=?')
                                   ->execute($dc->id, $varValue)
        ;

        // Check whether the page alias exists
        if ($objAlias->numRows > 1) {
            if (!$autoAlias) {
                throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
            }

            $varValue .= '-'.$dc->id;
        }

        return $varValue;
    }

    /**
     * Get available languages, for i18nl10n bundle.
     *
     * @param DataContainer $dc [Contao DataContainer]
     *
     * @return array [Languages available, as Array]
     */
    public function getAvailableLanguages(DataContainer $dc)
    {
        $arrOptions = Verstaerker\I18nl10nBundle\Classes\I18nl10n::getInstance()->getAvailableLanguages(true, true);

        // Add neutral option if available
        if ($this->User->isAdmin || false !== strpos(implode('', (array) $this->User->i18nl10n_languages), '::*')) {
            array_unshift($arrOptions, '');
        }

        return $arrOptions;
    }
}
