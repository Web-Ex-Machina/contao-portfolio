<?php

declare(strict_types=1);

namespace WEM\PortfolioBundle\Service;

use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Exception;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use WEM\PortfolioBundle\Model\Portfolio;
use WEM\PortfolioBundle\Model\PortfolioFeedAttribute;
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
     */
    public function getField(string $field, ?string $locale = null): mixed
    {
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
     */
    public function getFields(?string $locale = null): mixed
    {
        // If we have no locale or the current one is the same
        // return current model field
        if (!$locale || $locale === $this->locale) {
            return $this->model->row();
        }

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
}
