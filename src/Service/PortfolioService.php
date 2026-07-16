<?php

declare(strict_types=1);

namespace WEM\PortfolioBundle\Service;

use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\FilesystemItemIterator;
use Contao\CoreBundle\Filesystem\FilesystemUtil;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\Controller;
use Contao\FilesModel;
use Contao\StringUtil;
use Contao\System;
use Exception;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use WEM\PortfolioBundle\Model\Portfolio;
use WEM\PortfolioBundle\Model\PortfolioFeedAttribute;
use WEM\PortfolioBundle\Model\PortfolioFeedAttributeL10n;
use WEM\PortfolioBundle\Model\PortfolioL10n;

class PortfolioService
{
    protected ?Portfolio $model;
    protected string $locale;

    public function __construct(
        private readonly ContentUrlGenerator $contentUrlGenerator,
        private readonly RequestStack $requestStack,
    ) {
        $this->locale = $this->requestStack->getCurrentRequest()->getLocale();

        Controller::loadDataContainer('tl_wem_portfolio');
        Controller::loadDataContainer('tl_wem_portfolio_l10n');
    }

    /**
     * Load an item
     * 
     * @param int|string|Portfolio|PortfolioL10n - Model to load
     * @param string - Locale
     */
    public function load(int|string|Portfolio|PortfolioL10n $var, ?string $locale = null): void
    {
        if (null !== $locale) {
            $this->locale = $locale;
        }
        
        $translation = null;

        // If the var is a L10n, load its parent
        if ($var instanceof PortfolioL10n) {
            $this->model = $var->getRelated('pid');
            $translation = $var;
        }
        // If it's a Portfolio, we won't load the translation now
        else if ($var instanceof Portfolio) {
            $this->model = $var;
        } 
        // If we did not load a model directly, we will find a translation with the right ID / Slug
        else {
            $translation = PortfolioL10n::findByIdOrSlug($var);

            if (!$translation) {
                throw new Exception(
                    \sprintf(
                        "%s (%s) is not a Portfolio nor a Translation", 
                        $var,
                        $this->locale,
                    )
                );
            }
                
            $this->model = $translation->getRelated('pid');
        }

        // Load translation if locale is different from Model
        if ($this->model->language !== $this->locale) {
            if (!$translation) {
                $translation = PortfolioL10n::findTranslation($this->model->id, $this->locale);
            }

            $this->loadTranslationFields($translation);
        }
    }

    /**
     * Load the translated fields into current model
     * 
     * @param PortfolioL10n - $translation
     */
    public function loadTranslationFields(PortfolioL10n $translation): void
    {
        foreach ($translation->row() as $col => $value) {
            switch ($col) {
                // Skip technical fields
                case 'id':
                case 'pid':
                break;
                
                default:
                    $this->model->{$col} = $value;
                break;
            }
        }
    }

    /**
     * Load a specific translation
     * for the current item
     * 
     * @param string $locale - Locale
     * 
     * @return PortfolioL10n
     */
    public function getTranslation(string $locale): PortfolioL10n
    {
        $translation = PortfolioL10n::findTranslation($this->model->id, $locale);

        if (!$translation) {
            throw new Exception(
                \sprintf(
                    "Translation cannot be loaded for %s (%s)", 
                    $this->model->id,
                    $locale,
                )
            );
        }

        return $translation;
    }

    /**
     * Get item field
     * 
     * @param string - Attribute to retrieve
     * @param string - Locale
     * 
     * @return mixed
     */
    public function getField(string $field, ?string $locale = null): mixed
    {
        if (null !== $locale && $locale !== $this->locale) {
            $translation = $this->getTranslation($locale);
            $this->loadTranslationFields($translation);
            $this->locale = $locale;
        }

        // If the field is an attribute
        if ($this->isAttribute($field)) {
            return $this->getAttributeValue($field);
        }

        // Else, return the model field
        return $this->model->{$field};
    }

    /**
     * Get item fields
     * 
     * @param string - Locale
     * 
     * @return array
     */
    public function getFields(?string $locale = null): array
    {
        // Try to retrieve a l10n entry for this pid and language
        $data = [];
        foreach ($this->model->row() as $key => $value) {
            $data[$key] = $this->getField($key, $locale);
        }

        return $data;
    }

    /**
     * Generate item URL
     * 
     * @param array - Params to add
     * @param int - URL format (check UrlGeneratorInterface)
     * 
     * @return string
     */
    public function getUrl(array $params = [], int $format = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        return $this->contentUrlGenerator->generate(
            $this->model,
            $params, 
            $format,
        );
    }

    /**
     * Return true if field is an attribute
     * 
     * @param string $field - The field to check
     * 
     * @return bool
     */
    private function isAttribute(string $field): bool
    {
        $dc = $GLOBALS['TL_DCA']['tl_wem_portfolio']['fields'];

        return
            array_key_exists($field, $dc)
            && array_key_exists('eval', $dc[$field])
            && array_key_exists('wemIsAttribute', $dc[$field]['eval'])
            && true === $dc[$field]['eval']['wemIsAttribute']
        ;
    }

    /**
     * Return attribute config
     * 
     * @param string $field - The field to check
     *
     * @return PortfolioFeedAttribute
     */
    private function getAttributeConfig(string $field): PortfolioFeedAttribute
    {
        if (!$this->isAttribute($field)) {
            throw new Exception(sprintf('Field %s is not an attribute', $field));
        }

        $dc = $GLOBALS['TL_DCA']['tl_wem_portfolio']['fields'];

        if (
            !array_key_exists($field, $dc)
            || !array_key_exists('eval', $dc[$field])
            || !array_key_exists('wemAttributeConfig', $dc[$field]['eval'])
        ) {
            throw new Exception(sprintf('Field %s is an attribute with no wemAttributeConfig parameter', $field));
        }

        $config = $dc[$field]['eval']['wemAttributeConfig'];
        $objConfig = PortfolioFeedAttribute::findById($config);

        if (!$objConfig) {
            throw new Exception(sprintf('Cannot retrieve the config ID %s for field %s', $config, $field));
        }

        // Get translations
        $objL10n = PortfolioFeedAttributeL10n::findItems(['language' => $this->locale, 'pid' => $objConfig->id], 1);

        if ($objL10n) {
            foreach ($objL10n->row() as $col => $value) {
                $objConfig->{$col} = $value;
            }
        }

        return $objConfig;
    }

    /**
     * Return a field value
     *
     * @param string      $field
     *
     * @throws \Exception
     *
     * @return array|Collection|mixed|string|Portfolio|null
     */
    private function getAttributeValue(string $field, bool $forApi = false)
    {
        // Retrieve attribute config
        $objAttr = $this->getAttributeConfig($field);

        switch ($objAttr->type) {
            case 'select':
                $return = null;
                $arrArticleData = $this->model->row();
                $options = StringUtil::deserialize($objAttr->options ?? []);

                if ($objAttr->translatable) {
                    $objL10n = PortfolioFeedAttributeL10n::findItems(['language' => $this->locale, 'pid' => $objAttr->id], 1);

                    if (null !== $objL10n) {
                        $options = StringUtil::deserialize($objL10n->options ?? []);
                    }
                }

                if ($objAttr->multiple) {
                    $arrArticleData[$objAttr->name] = StringUtil::deserialize($arrArticleData[$objAttr->name]);
                    $return = [];
                }

                foreach ($options as $option) {
                    if ($objAttr->multiple && \is_array($arrArticleData[$objAttr->name]) && \in_array($option['value'], $arrArticleData[$objAttr->name], true)) {
                        $return[] = $option['label'];
                    } elseif (!$objAttr->multiple && $option['value'] === $arrArticleData[$objAttr->name]) {
                        $return = $option['label'];
                    }
                }

                if ($objAttr->multiple) {
                    $return = implode(', ', $return);
                }

                return $return;

            case 'picker':
                return $this->model->getRelated($objAttr->name);

            case 'fileTree':
                return $this->getFilesFromSources($this->model->{$objAttr->name} ?: '', $objAttr);

            case 'listWizard':
                $varValue = StringUtil::deserialize($this->model->{$objAttr->name});

                if (!$varValue) {
                    return '';
                }

                if (is_array($varValue)) {
                    return implode(', ', $varValue);
                }

                return $varValue;

            default:
                return $this->model->{$objAttr->name};
        }
    }

    private function getFilesFromSources(string $sources, PortfolioFeedAttribute $config): array
    {
        if ($config->multiple) {
            $sources = StringUtil::deserialize($sources);
        } else {
            $sources = [$sources];
        }

        $data = [];

        $filesStorage = System::getContainer()->get('contao.filesystem.virtual.files');
        $filesystemItems = FilesystemUtil::listContentsFromSerialized($filesStorage, $sources);

        return $this->compileFiles($filesystemItems);
    }

    private function compileFiles(FilesystemItemIterator $filesystemItems): array
    {
        return array_map(
            fn (FilesystemItem $filesystemItem): array => [
                'file' => $filesystemItem,
            ],
            iterator_to_array($filesystemItems),
        );
    }
}
