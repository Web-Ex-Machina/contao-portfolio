<?php

declare(strict_types=1);

/**
 * Contao Portfolio for Contao Open Source CMS
 * Copyright (c) 2015-2024 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-portfolio
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-portfolio/
 */

namespace WEM\PortfolioBundle\Model;

use Contao\Date;
use Contao\System;
use WEM\UtilsBundle\Model\Model;

class Content extends Model
{
    /**
     * Table name.
     *
     * @var string
     */
    protected static $strTable = 'tl_content';

    public static function findPublishedByPidAndTableAndLanguage($intPid, $strParentTable, array $arrOptions = [])
    {
        $t = static::$strTable;
        $arrColumns = [\sprintf('%s.pid=? AND %s.ptable=?', $t, $t)];

        if (!static::isPreviewMode($arrOptions)) {
            $time = Date::floorToMinute();
            $arrColumns[] = \sprintf('%s.invisible=0 AND (%s.start=\'\' OR %s.start<=%d) AND (%s.stop=\'\' OR %s.stop>%d)', $t, $t, $t, $time, $t, $t, $time);
        }

        $r = System::getContainer()->get('request_stack')->getCurrentRequest();

        if (null !== $r) {
            $arrColumns[] = $t.'.wem_language=?';
        }

        $arrColumns[] = $t.'.tstamp!=0';

        if (!isset($arrOptions['order'])) {
            $arrOptions['order'] = $t.'.sorting';
        }

        return static::findBy($arrColumns, [$intPid, $strParentTable, $r->getLocale() ?: ''], $arrOptions);
    }
}
