<?php

declare(strict_types=1);

namespace WEM\PortfolioBundle\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Input;
use WEM\PortfolioBundle\Model\Portfolio;


class CountPortfoliosInsertTagListener
{
    public const TAG = 'countportfolios';

    /**
     * Example {{countportfolios::1,2,3...}}
     * @Hook("replaceInsertTags", priority=100)
     */
    public function replaceInsertTags(string $tag)
    {
        $chunks = explode('::', $tag);

        if (self::TAG !== $chunks[0]) {
            return false;
        }

        // Retrieve the PIDs wanted
        $c['pid'] = explode(",", $chunks[1]);
        $c['published'] = 1;

        // Retrieve filters
        if ($_GET !== [] || $_POST !== []) {
            foreach ($_GET as $f => $v) {
                if (false === strpos($f, 'portfolio_filter_')) {
                    continue;
                }

                if (Input::get($f)) {
                    $c[str_replace('portfolio_filter_', '', $f)] = Input::get($f);
                }
            }

            foreach (array_keys($_POST) as $f) {
                if (false === strpos($f, 'portfolio_filter_')) {
                    continue;
                }

                if (Input::post($f)) {
                    $c[str_replace('portfolio_filter_', '', $f)] = Input::post($f);
                }
            }
        }

        // Call the Model
        return Portfolio::countItems($c);

    }
}