<?php

/**
 * Module Portfolio for Contao Open Source CMS
 *
 * Copyright (c) 2015-2018 Web ex Machina
 *
 * @author Web ex Machina <http://www.webexmachina.fr>
 */


/**
 * Table tl_wem_portfolio_item
 */
$GLOBALS['TL_DCA']['tl_wem_portfolio_item'] = array(

    // Config
    'config' => array(
        'dataContainer'               => 'Table',
        'ctable'                      => array('tl_wem_portfolio_item_attribute', 'tl_content'),
        'switchToEdit'                => true,
        'enableVersioning'            => true,
        'sql' => array(
            'keys' => array(
                'id' => 'primary',
                'alias' => 'index',
            )
        )
    ),

    // List
    'list' => array(
        'sorting' => array(
            'mode'                    => 2,
            'fields'                  => array('date DESC'),
            'flag'                    => 1,
            'panelLayout'             => 'filter;sort,search,limit'
        ),
        'label' => array(
            'fields'                  => array('title', 'date'),
            'format'                  => '%s <span style="color:#999;padding-left:3px">[%s]</span>',
            'label_callback'          => array('tl_wem_portfolio_item', 'addIcon')
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
                'label'               => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item']['edit'],
                'href'                => 'table=tl_content',
                'icon'                => 'edit.svg',
            ),
            'header' => array(
                'label'               => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item']['header'],
                'href'                => 'act=edit',
                'icon'                => 'header.svg',
            ),
            'copy' => array(
                'label'               => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item']['copy'],
                'href'                => 'act=copy',
                'icon'                => 'copy.svg',
                'attributes'          => 'onclick="Backend.getScrollOffset()"',
            ),
            'delete' => array(
                'label'               => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item']['delete'],
                'href'                => 'act=delete',
                'icon'                => 'delete.svg',
                'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"',
            ),
            'toggle' => array(
                'label'               => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item']['toggle'],
                'icon'                => 'visible.svg',
                'attributes'          => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
                'button_callback'     => array('tl_wem_portfolio_item', 'toggleIcon')
            ),
            'show' => array(
                'label'               => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item']['show'],
                'href'                => 'act=show',
                'icon'                => 'show.svg'
            )
        )
    ),

    // Palettes
    'palettes' => array(
        'default'                     => '
            {title_legend},title,alias,date,category;
            {media_legend},pictures;
            {details_legend},teaser;
            {attributes_legend},attributes;
            {publish_legend},published,start,stop
        '
    ),

    // Fields
    'fields' => array(
        'id' => array(
            'label'                   => array('ID'),
            'search'                  => true,
            'sql'                     => "int(10) unsigned NOT NULL auto_increment"
        ),
        'created_on' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item']['created_on'],
            'default'                 => time(),
            'flag'                    => 8,
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'tstamp' => array(
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),

        'title' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item']['title'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'search'                  => true,
            'eval'                    => array('mandatory'=>true, 'decodeEntities'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'alias' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item']['alias'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => array('rgxp'=>'alias', 'doNotCopy'=>true, 'unique'=>true, 'maxlength'=>128, 'tl_class'=>'w50'),
            'save_callback' => array(
                array('tl_wem_portfolio_item', 'generateAlias')
            ),
            'sql'                     => "varchar(128) BINARY NOT NULL default ''"
        ),
        'date' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item']['date'],
            'default'                 => time(),
            'exclude'                 => true,
            'filter'                  => true,
            'sorting'                 => true,
            'flag'                    => 8,
            'inputType'               => 'text',
            'eval'                    => array('rgxp'=>'date', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'category' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item']['category'],
            'exclude'                 => true,
            'sorting'                 => true,
            'flag'                    => 11,
            'inputType'               => 'pageTree',
            'foreignKey'              => 'tl_page.title',
            'eval'                    => array('mandatory'=>true, 'fieldType'=>'radio', 'tl_class'=>'w50'),
            'sql'                     => "int(10) unsigned NOT NULL default '0'",
            'relation'                => array('type'=>'hasOne', 'load'=>'eager')
        ),
        'pictures' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item']['pictures'],
            'exclude'                 => true,
            'inputType'               => 'fileTree',
            'eval'                    => array('files'=>true, 'extensions'=>Config::get('validImageTypes'), 'multiple'=>true, 'fieldType'=>'checkbox', 'orderField'=>'orderPictures'),
            'sql'                     => "blob NULL"
        ),
        'orderPictures' => array(
            'label'                   => &$GLOBALS['TL_LANG']['MSC']['sortOrder'],
            'sql'                     => "blob NULL"
        ),
        'teaser' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item']['teaser'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'textarea',
            'eval'                    => array('rte'=>'tinyMCE', 'tl_class'=>'clr'),
            'sql'                     => "text NULL"
        ),

        'attributes' => array(
            'label'                 => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item']['attributes'],
            'inputType'             => 'dcaWizard',
            'foreignTable'          => 'tl_wem_portfolio_item_attribute',
            'foreignField'          => 'pid',
            'params'                  => array(
                'do'                  => 'wem_portfolio_item',
            ),
            'eval'                  => array(
                'fields' => array('attribute', 'value'),
                'editButtonLabel' => $GLOBALS['TL_LANG']['tl_wem_portfolio_item']['edit_attribute'],
                'applyButtonLabel' => $GLOBALS['TL_LANG']['tl_wem_portfolio_item']['apply_attribute'],
                'orderField' => 'attribute',
                'showOperations' => true,
                'operations' => array('edit', 'delete'),
                'tl_class'=>'clr',
            ),
        ),

        'published' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item']['published'],
            'exclude'                 => true,
            'filter'                  => true,
            'flag'                    => 1,
            'inputType'               => 'checkbox',
            'eval'                    => array('doNotCopy'=>true),
            'sql'                     => "char(1) NOT NULL default ''"
        ),
        'start' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item']['start'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
            'sql'                     => "varchar(10) NOT NULL default ''"
        ),
        'stop' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_wem_portfolio_item']['stop'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
            'sql'                     => "varchar(10) NOT NULL default ''"
        ),


    )
);

// Handle i18nl10n compatibility
$bundles = \System::getContainer()->getParameter('kernel.bundles');
if (array_key_exists("VerstaerkerI18nl10nBundle", $bundles)) {
    // Update palettes
    $GLOBALS['TL_DCA']['tl_wem_portfolio_item']['palettes']['default'] .= ';{i18nl10n_legend},i18nl10n_lang,i18nl10n_id';

    $GLOBALS['TL_DCA']['tl_wem_portfolio_item']['fields']['i18nl10n_id'] = array(
        'label'            => array('I18NL10N_ID'),
        'exclude'          => true,
        'inputType'        => 'i18nl10nAssociatedLocationsWizard',
        'eval'             => array('tl_class'=>'w50'),
        'sql'              => "int(10) unsigned NOT NULL default '0'"
    );
    $GLOBALS['TL_DCA']['tl_wem_portfolio_item']['fields']['i18nl10n_lang'] = array(
        'label'            => &$GLOBALS['TL_LANG']['MSC']['i18nl10n_fields']['language']['label'],
        'exclude'          => true,
        'filter'           => true,
        'inputType'        => 'select',
        'sorting'          => true,
        'flag'             => 11,
        'options_callback' => array('tl_wem_portfolio_item', 'getAvailableLanguages'),
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
 * Handle Portfolio Items DCA functions
 *
 * @author Web ex Machina <contact@webexmachina.fr>
 */
class tl_wem_portfolio_item extends Backend
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
     * Add an image to each item in the tree
     *
     * @param array         $row
     * @param string        $label
     * @param DataContainer $dc
     * @param string        $imageAttribute
     * @param boolean       $blnReturnImage
     * @param boolean       $blnProtected
     *
     * @return string
     */
    public function addIcon($row, $label, DataContainer $dc = null, $imageAttribute = '', $blnReturnImage = false, $blnProtected = false)
    {
        return '<img src="assets/contao/images/iconJPG.svg" width="18" height="18" alt="image/jpeg" style="margin-right:3px"><span style="vertical-align:-1px">'.$label.'</span>';
    }

    /**
     * Auto-generate an article alias if it has not been set yet
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

        $objAlias = $this->Database->prepare("SELECT id FROM tl_wem_portfolio_item WHERE id=? OR alias=?")
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
     * Return the "toggle visibility" button
     *
     * @param array  $row
     * @param string $href
     * @param string $label
     * @param string $title
     * @param string $icon
     * @param string $attributes
     *
     * @return string
     */
    public function toggleIcon($row, $href, $label, $title, $icon, $attributes)
    {
        if (strlen(Input::get('tid'))) {
            $this->toggleVisibility(Input::get('tid'), (Input::get('state') == 1), (@func_get_arg(12) ?: null));
            $this->redirect($this->getReferer());
        }

        // Check permissions AFTER checking the tid, so hacking attempts are logged
        if (!$this->User->hasAccess('tl_wem_portfolio_item::published', 'alexf')) {
            return '';
        }

        $href .= '&amp;tid='.$row['id'].'&amp;state='.($row['published'] ? '' : 1);

        if (!$row['published']) {
            $icon = 'invisible.svg';
        }

        return '<a href="'.$this->addToUrl($href).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label, 'data-state="' . ($row['published'] ? 1 : 0) . '"').'</a> ';
    }


    /**
     * Disable/enable a user group
     *
     * @param integer       $intId
     * @param boolean       $blnVisible
     * @param DataContainer $dc
     *
     * @throws Contao\CoreBundle\Exception\AccessDeniedException
     */
    public function toggleVisibility($intId, $blnVisible, DataContainer $dc = null)
    {
        // Set the ID and action
        Input::setGet('id', $intId);
        Input::setGet('act', 'toggle');

        if ($dc) {
            $dc->id = $intId;
        } // see #8043

        // Trigger the onload_callback
        if (is_array($GLOBALS['TL_DCA']['tl_wem_portfolio_item']['config']['onload_callback'])) {
            foreach ($GLOBALS['TL_DCA']['tl_wem_portfolio_item']['config']['onload_callback'] as $callback) {
                if (is_array($callback)) {
                    $this->import($callback[0]);
                    $this->{$callback[0]}->{$callback[1]}($dc);
                } elseif (is_callable($callback)) {
                    $callback($dc);
                }
            }
        }

        // Check the field access
        if (!$this->User->hasAccess('tl_wem_portfolio_item::published', 'alexf')) {
            throw new Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to publish/unpublish article ID "' . $intId . '".');
        }

        // Set the current record
        if ($dc) {
            $objRow = $this->Database->prepare("SELECT * FROM tl_wem_portfolio_item WHERE id=?")->limit(1)->execute($intId);

            if ($objRow->numRows) {
                $dc->activeRecord = $objRow;
            }
        }

        $objVersions = new Versions('tl_wem_portfolio_item', $intId);
        $objVersions->initialize();

        // Trigger the save_callback
        if (is_array($GLOBALS['TL_DCA']['tl_wem_portfolio_item']['fields']['published']['save_callback'])) {
            foreach ($GLOBALS['TL_DCA']['tl_wem_portfolio_item']['fields']['published']['save_callback'] as $callback) {
                if (is_array($callback)) {
                    $this->import($callback[0]);
                    $blnVisible = $this->{$callback[0]}->{$callback[1]}($blnVisible, $dc);
                } elseif (is_callable($callback)) {
                    $blnVisible = $callback($blnVisible, $dc);
                }
            }
        }

        $time = time();

        // Update the database
        $this->Database->prepare("UPDATE tl_wem_portfolio_item SET tstamp=$time, published='" . ($blnVisible ? '1' : '') . "' WHERE id=?")->execute($intId);

        if ($dc) {
            $dc->activeRecord->tstamp = $time;
            $dc->activeRecord->published = ($blnVisible ? '1' : '');
        }

        // Trigger the onsubmit_callback
        if (is_array($GLOBALS['TL_DCA']['tl_wem_portfolio_item']['config']['onsubmit_callback'])) {
            foreach ($GLOBALS['TL_DCA']['tl_wem_portfolio_item']['config']['onsubmit_callback'] as $callback) {
                if (is_array($callback)) {
                    $this->import($callback[0]);
                    $this->{$callback[0]}->{$callback[1]}($dc);
                } elseif (is_callable($callback)) {
                    $callback($dc);
                }
            }
        }

        $objVersions->create();
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
