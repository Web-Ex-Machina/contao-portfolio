<?php

declare(strict_types=1);

namespace WEM\PortfolioBundle\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Psr\Log\LoggerInterface;
use WEM\PortfolioBundle\Model\PortfolioFeedAttribute;
use WEM\UtilsBundle\Classes\StringUtil;

class LoadDataContainerListener
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @Hook("loadDataContainer", priority=100)
     */
    public function addAttributesToPortfolioDca($strTable): void
    {
        try {
            if ('tl_wem_portfolio' === $strTable) {
                // For everytime we load a tl_wem_portfolio DCA, we want to load all the existing attributes as fields
                $objAttributes = PortfolioFeedAttribute::findAll();

                if (!$objAttributes || 0 === $objAttributes->count()) {
                    return;
                }

                while ($objAttributes->next()) {
                    $GLOBALS['TL_DCA']['tl_wem_portfolio']['fields'][$objAttributes->name] = $this->parseDcaAttribute($objAttributes->row());
                }
            }
        } catch (\Exception $exception) {
            $this->logger->log('ERROR', vsprintf(($GLOBALS['TL_LANG']['WEM']['PORTFOLIO']['ERROR']['generic']) ?: "coucou", [$exception->getMessage(), $exception->getTrace()]), ["WEM_OFFERS"]);
        }
    }

    protected function parseDcaAttribute(array $row): array
    {
        // Generic data
        $data = [
            'label' => [0 => $row['label'] ?: $row['name']],
            'name' => $row['name'],
            'inputType' => $row['type'],
            'eval' => [],
            'sql' => ['name' => $row['name']],
        ];

        // Default settings
        if (\array_key_exists('default', $row)) {
            $data['default'] = $row['default'];
            $data['sql']['default'] = $row['default'];
        }

        // Maxlength settings
        if ($row['maxlength']) {
            $data['eval']['maxlength'] = (int)$row['maxlength'];
            $data['sql']['length'] = (int)$row['maxlength'];
        }

        // Available for alerts settings
        // if ($row['wemoffers_isAvailableForAlerts']) {
        if ($row['isAlertCondition']) {
            $data['eval']['isAlertCondition'] = true;
        }

        // Available for filters settings
        // if ($row['wemoffers_isAvailableForFilters']) {
        if ($row['isFilter']) {
            $data['eval']['isFilter'] = true;
        }

        // Mandatory settings
        if ($row['mandatory']) {
            $data['eval']['mandatory'] = true;
        }

        // Class settings
        if ($row['class']) {
            $data['eval']['tl_class'] = $row['class'];
        }

        switch ($row['type']) {
            case 'text':
                // Allow HTML settings
                if ($row['allowHtml']) {
                    $data['eval']['allowHtml'] = true;
                }

                $data['sql']['type'] = 'string';

                if ($row['value']) {
                    $data['default'] = $row['value'];
                    $data['sql']['default'] = $row['value'];
                } else {
                    $data['default'] = '';
                    $data['sql']['default'] = '';
                }

                break;

            case 'select':
                $data['sql']['type'] = 'string';
                $data['default'] = '';
                $data['sql']['default'] = '';

                // Multiple settings
                if ($row['multiple']) {
                    $data['eval']['multiple'] = true;
                    $data['sql'] = 'blob NULL';
                }

                // Chosen settings
                if ($row['chosen']) {
                    $data['eval']['chosen'] = true;
                }

                // Options
                $options = StringUtil::deserialize($row['options']);
                if (null !== $options) {
                    $data['options'] = [];
                    $blnIsGroup = false;
                    $blnIsChild = true;
                    $key = null;
                    foreach ($options as $o) {
                        if (\array_key_exists('group', $o) && $o['group']) {
                            $blnIsGroup = true;
                            $blnIsChild = false;
                            $key = $o['label'];
                        } else {
                            $blnIsGroup = false;
                            $blnIsChild = true;
                        }

                        if (null === $key) {
                            $data['options'][$o['value']] = $o['label'];
                        } elseif ($blnIsGroup) {
                            // $data['options'][$key] = ['label'=>$o['label'],'options'=>[]];
                        } elseif ($blnIsChild) {
                            $data['options'][$key][$o['value']] = $o['label'];
                        }

                        if (\array_key_exists('default', $o)) {
                            $data['default'] = $o['default'];
                            $data['sql']['default'] = $o['default'];
                        }
                    }
                }

                break;

            case 'picker':
                // Fkey settings
                if ($row['fkey']) {
                    $data['foreignKey'] = $row['fkey'];
                }

                // Multiple settings
                if ($row['multiple']) {
                    $data['eval']['multiple'] = true;
                    $data['sql'] = 'blob NULL';
                    $data['relation'] = ['type' => 'hasMany', 'load' => 'lazy'];
                } else {
                    $data['sql'] = 'int(10) unsigned NOT NULL default 0';
                    $data['relation'] = ['type' => 'hasOne', 'load' => 'lazy'];
                }

                break;

            case 'fileTree':
                // filesOnly settings
                if ($row['filesOnly']) {
                    $data['eval']['filesOnly'] = true;
                }

                // extensions settings
                if ($row['fieldType']) {
                    $data['eval']['fieldType'] = $row['fieldType'];
                }

                // extensions settings
                if ($row['extensions']) {
                    $data['eval']['extensions'] = $row['extensions'];
                }

                // Multiple settings
                if ($row['multiple']) {
                    $data['eval']['multiple'] = true;
                    $data['sql'] = 'blob NULL';
                } else {
                    $data['sql']['type'] = 'binary';
                    $data['sql']['length'] = 16;
                    $data['sql']['default'] = 'NULL';
                }

                break;

            case 'listWizard':
                // Allow HTML settings
                if ($row['allowHtml']) {
                    $data['eval']['allowHtml'] = true;
                }

                // Multiple settings
                if ($row['multiple']) {
                    $data['eval']['multiple'] = true;
                }

                $data['sql'] = 'blob NULL';
                break;
        }

        return $data;
    }
}