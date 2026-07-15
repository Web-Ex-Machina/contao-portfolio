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

namespace WEM\PortfolioBundle\EventListener\DataContainer\Portfolio;

use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
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

    #[AsHook('loadDataContainer', priority: 100)]
    public function addAttributesToPortfolioDca($strTable): void
    {
        try {
            if ('tl_wem_portfolio' === $strTable || 'tl_wem_portfolio_l10n' === $strTable) {
                // For everytime we load a tl_wem_portfolio DCA, we want to load all the existing attributes as fields
                $objAttributes = PortfolioFeedAttribute::findAll();

                if (!$objAttributes || 0 === $objAttributes->count()) {
                    return;
                }

                if ('tl_wem_portfolio_l10n' === $strTable && null === $GLOBALS['TL_DCA']['tl_wem_portfolio']) {
                    Controller::loadDataContainer('tl_wem_portfolio');
                }

                while ($objAttributes->next()) {
                    $field = $this->parseDcaAttribute($objAttributes->row(), $objAttributes->current(), $strTable);

                    if ('tl_wem_portfolio' === $strTable) {
                        $GLOBALS['TL_DCA']['tl_wem_portfolio']['fields'][$objAttributes->name] = $field;
                    } elseif ('tl_wem_portfolio_l10n' === $strTable && 1 === (int) $objAttributes->translatable) {
                        $GLOBALS['TL_DCA']['tl_wem_portfolio_l10n']['fields'][$objAttributes->name] = $field;
                    }
                }
            }
        } catch (\Exception $exception) {
            $this->logger->log('ERROR', \sprintf('An error occured: %s | %s', $exception->getMessage(), $exception->getTraceAsString()), ['WEM_PORTFOLIO']);
        }
    }

    protected function parseDcaAttribute(array $row, PortfolioFeedAttribute $model, string $strTable): array
    {
        // Generic data
        $data = [
            'label' => [0 => $model->getL10nLabel('label') ?: $row['name']],
            'name' => $row['name'],
            'inputType' => $row['type'],
            'eval' => ['wemIsAttribute' => true, 'wemAttributeConfig' => $model->id],
            'sql' => ['name' => $row['name']],
        ];

        // Default settings
        if (\array_key_exists('default', $row)) {
            $data['default'] = $row['default'];
            $data['sql']['default'] = $row['default'];
        }

        // Maxlength settings
        if ($row['maxlength']) {
            $data['eval']['maxlength'] = (int) $row['maxlength'];
            $data['sql']['length'] = (int) $row['maxlength'];
        }

        // Available for filters settings
        if ($row['isFilter']) {
            $data['eval']['wemIsFilter'] = true;
        }

        // Mandatory settings
        if ($row['mandatory']) {
            $data['eval']['mandatory'] = true;
        }

        // rte settings
        if ($row['rte']) {
            $data['eval']['rte'] = $row['rte'];
        }

        // Class settings
        if ($row['explanation']) {
            $data['explanation'] = $model->getL10nLabel('explanation');
        }

        // Class settings
        if ($row['class']) {
            $data['eval']['tl_class'] = $row['class'];
        }

        // Allow helpwizard
        if ($row['helpwizard']) {
            $data['eval']['helpwizard'] = true;
        }

        switch ($row['type']) {
            case 'text':
                $val = $row['value'] ? $model->getL10nLabel('value') : '';
                $data['default'] = $val;
                $data['sql'] = sprintf("varchar(255) NOT NULL default '%s'", $val);
                break;

            case 'textarea':
                // Allow HTML settings
                if ($row['allowHtml']) {
                    $data['eval']['allowHtml'] = true;
                }

                $data['sql'] = 'mediumtext NULL';

                break;

            case 'select':
                $data['default'] = '';
                $data['sql'] = "varchar(255) NOT NULL default ''";

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
                $options = StringUtil::deserialize($model->getL10nLabel('options'));
                if (null !== $options) {                  
                    // Options might already exist so to avoid option to be erased, we will "concatenate" every options 
                    // from attributes. This should be improved by restrict attributes to their parent feed. 
                    if (
                        array_key_exists($row['name'], $GLOBALS['TL_DCA'][$strTable]['fields'])
                        && array_key_exists('options', $GLOBALS['TL_DCA'][$strTable]['fields'][$row['name']])
                    ) {
                        $data['options'] = $GLOBALS['TL_DCA']['tl_wem_portfolio']['fields'][$row['name']]['options'];
                    } else {
                        $data['options'] = [];
                    }

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
                            $data['sql'] = sprintf("varchar(255) NOT NULL default '%s'", $o['default']);
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
                    $data['sql'] = 'binary(16) NULL';
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
