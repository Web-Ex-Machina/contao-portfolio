<?php

declare(strict_types=1);

namespace WEM\PortfolioBundle\Service;

use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\Controller;
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
        // If we did not load a model directly, we will have to guess
        // if we want a translation or a model
        else {
            $this->model = Portfolio::findByIdOrSlug($var, $this->locale);

            if (!$this->model) {
                $translation = PortfolioL10n::findByIdOrSlug($var, $this->locale);

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
        }

        // Load translation if locale is different from Model
        if ($this->model->language !== $this->locale) {
            if (!$translation) {
                $translation = PortfolioL10n::findByIdOrSlug($this->model->id, $this->locale);
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
    public function getL10n(string $locale): PortfolioL10n
    {
        $translation = PortfolioL10n::findByIdOrSlug($this->model->id, $locale);

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
        // If the field is an attribute
        if ($this->isAttribute($field)) {
            dump($this->getAttributeConfig($field));
        }

        // If we have no locale or the current one is the same
        // return current model field
        if (!$locale || $locale === $this->locale) {
            return $this->model->{$field};
        }

        // Try to retrieve a l10n entry for this pid and language
        $translation = $this->getL10n($locale);

        return $translation->{$field};
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
            $data[$key] = $this->getField($key);
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
}
