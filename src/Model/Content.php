<?php

namespace WEM\PortfolioBundle\Model;

use Contao\Date;
use Contao\System;
use WEM\UtilsBundle\Model\Model;

class Content extends Model
{
    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_content';


    public static function findPublishedByPidAndTableAndLanguage($intPid, $strParentTable, array $arrOptions=array())
    {
        $t = static::$strTable;
        $arrColumns = array("$t.pid=? AND $t.ptable=?");

        if (!static::isPreviewMode($arrOptions))
        {
            $time = Date::floorToMinute();
            $arrColumns[] = "$t.invisible=0 AND ($t.start='' OR $t.start<=$time) AND ($t.stop='' OR $t.stop>$time)";
        }

        $r = System::getContainer()->get('request_stack')->getCurrentRequest();

        if (null !== $r) {
            $arrColumns[] = "$t.wem_language=?";
        }

        $arrColumns[] = "$t.tstamp!=0";

        if (!isset($arrOptions['order']))
        {
            $arrOptions['order'] = "$t.sorting";
        }

        return static::findBy($arrColumns, array($intPid, $strParentTable,$r->getLocale()?:''), $arrOptions);
    }

}