<?php

declare(strict_types=1);

namespace WEM\PortfolioBundle\EventListener\DataContainer\Portfolio;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\Database;
use Contao\DataContainer;
use WEM\PortfolioBundle\Model\Portfolio;
use WEM\PortfolioBundle\Model\PortfolioL10n;

#[AsCallback(table: 'tl_wem_portfolio', target: 'config.onsubmit')]
class PortfolioSubmitCallbackListener
{
    public function __invoke(DataContainer $dc): void
    {
        if (!$dc->id) {
            return;
        }

        // Try to find a translation
        $db = Database::getInstance();
        $objModel = Portfolio::findById((int) $dc->id);
        $objL10n = PortfolioL10n::findTranslation((int) $dc->id, $dc->activeRecord->language);

        if (null === $objL10n) {
        	$objL10n = new PortfolioL10n();
        	$objL10n->createdAt = time();
        	$objL10n->pid = $dc->id;
        }

        $skip = ['id', 'tstamp', 'pid'];

        foreach ($objModel->row() as $col => $val) {
        	if (in_array($col, $skip)) {
        		continue;
        	}

        	if ($db->fieldExists($col, 'tl_wem_portfolio_l10n')) {
        		$objL10n->{$col} = $val;
        	}
        }

        $objL10n->tstamp = time();
        $objL10n->save();
    }
}