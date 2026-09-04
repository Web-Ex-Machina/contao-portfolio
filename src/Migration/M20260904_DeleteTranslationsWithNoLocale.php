<?php

declare(strict_types=1);

namespace WEM\PortfolioBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\Model\Collection;
use Doctrine\DBAL\Connection;
use Exception;
use WEM\PortfolioBundle\Model\PortfolioL10n;

class M20260904_DeleteTranslationsWithNoLocale extends AbstractMigration
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        // If the database table itself does not exist we should do nothing
        if (!$schemaManager->tablesExist(['tl_wem_portfolio', 'tl_wem_portfolio_l10n'])) {
            return false;
        }

        return $this->countItems() > 0;
    }

    public function run(): MigrationResult
    {
        $items = $this->getItems();
        $i = 0;
        if ($items instanceof Collection) {
            while ($items->next()) {
                $items->current()->delete();
                ++$i;
            }
        }

        return $this->createResult(
            true,
            $i . ' portfolio translations with no locales deleted.'
        );
    }

    private function getItems(): ?Collection
    {
        try {
            return PortfolioL10n::findItems([
                'language' => '',
            ]);
        } catch (Exception $exception) {
            return null;
        }
    }

    private function countItems()
    {
        $items = $this->getItems();
        if (!$items instanceof Collection) {
            return 0;
        }

        return $items->count();
    }
}
