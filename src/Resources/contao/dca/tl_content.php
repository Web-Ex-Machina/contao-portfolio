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

use Contao\Input;
use Contao\System;
use WEM\PortfolioBundle\DataContainer\ContentContainer;

if ('wem_portfolio_feed' === Input::get('do')) {
    $GLOBALS['TL_DCA']['tl_content']['config']['ptable'] = 'tl_wem_portfolio';

    $GLOBALS['TL_DCA']['tl_content']['list']['sorting']['child_record_callback'] = ['tl_content_wemportfolio', 'addCteType'];
}

$GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = [ContentContainer::class, 'updatePalettes'];
$GLOBALS['TL_DCA']['tl_content']['fields']['wem_language'] = [
    'exclude' => true,
    'filter' => true,
    'sorting' => true,
    'inputType' => 'select',
    'options' => System::getContainer()->get('contao.intl.locales')->getLocales(null, false),
    'eval' => ['includeBlankOption' => true, 'mandatory' => true, 'chosen' => true, 'tl_class' => 'w50'],
    'sql' => "varchar(64) NOT NULL default ''",
];

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 */
class tl_content_wemportfolio extends tl_content
{
    /**
     * Import the back end user object.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Add the type of content element.
     *
     * @param array $arrRow
     *
     * @return string
     */
    public function addCteType($arrRow)
    {
        $key = $arrRow['invisible'] ? 'unpublished' : 'published';
        $type = $GLOBALS['TL_LANG']['CTE'][$arrRow['type']][0] ?? $arrRow['type'];
        $class = 'limit_height';

        // Remove the class if it is a wrapper element
        if (\in_array($arrRow['type'], $GLOBALS['TL_WRAPPERS']['start'], true) || \in_array($arrRow['type'], $GLOBALS['TL_WRAPPERS']['separator'], true) || \in_array($arrRow['type'], $GLOBALS['TL_WRAPPERS']['stop'], true)) {
            $class = '';

            if (($group = $this->getContentElementGroup($arrRow['type'])) !== null) {
                $type = ($GLOBALS['TL_LANG']['CTE'][$group] ?? $group).' ('.$type.')';
            }
        }

        // Add the group name if it is a single element (see #5814)
        elseif (\in_array($arrRow['type'], $GLOBALS['TL_WRAPPERS']['single'], true)) {
            if (($group = $this->getContentElementGroup($arrRow['type'])) !== null) {
                $type = ($GLOBALS['TL_LANG']['CTE'][$group] ?? $group).' ('.$type.')';
            }
        }

        // Add the ID of the aliased element
        if ('alias' === $arrRow['type']) {
            $type .= ' ID '.$arrRow['cteAlias'];
        }

        // Add the protection status
        if ($arrRow['protected'] ?? null) {
            $groupIds = StringUtil::deserialize($arrRow['groups'], true);
            $groupNames = [];

            if (!empty($groupIds)) {
                if (\in_array(-1, array_map('intval', $groupIds), true)) {
                    $groupNames[] = $GLOBALS['TL_LANG']['MSC']['guests'];
                }

                if (null !== ($groups = MemberGroupModel::findMultipleByIds($groupIds))) {
                    $groupNames += $groups->fetchEach('name');
                }
            }

            $type .= ' ('.$GLOBALS['TL_LANG']['MSC']['protected'].($groupNames ? ': '.implode(', ', $groupNames) : '').')';
        }

        // Add the headline level (see #5858)
        if ('headline' === $arrRow['type'] && \is_array($headline = StringUtil::deserialize($arrRow['headline']))) {
            $type .= ' ('.$headline['unit'].')';
        }

        // Limit the element's height
        if (!Config::get('doNotCollapse')) {
            $class .= ' h40';
        }

        $objModel = new ContentModel();
        $objModel->setRow($arrRow);

        $arrLanguages = System::getContainer()->get('contao.intl.locales')->getLocales(null, false);
        $strLang = $arrRow['wem_language'] ? $arrLanguages[$arrRow['wem_language']] : 'NR';

        return '
<div class="cte_type '.$key.'">'.$type.' // '.$strLang.'</div>
<div class="'.trim($class).'">
'.StringUtil::insertTagToSrc($this->getContentElement($objModel)).'
</div>'."\n";
    }
}
