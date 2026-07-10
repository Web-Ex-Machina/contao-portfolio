<?php

namespace WEM\PortfolioBundle\EventListener\DataContainer\Content;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\ContentElement;
use Contao\ContentModel;
use Contao\System;
use Symfony\Component\HttpFoundation\RequestStack;
use WEM\PortfolioBundle\Model\Portfolio;

#[AsHook('getContentElement')]
class DisplayLanguageInBackendTemplateListener
{
	public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ScopeMatcher $scopeMatcher,
    ) {
    }

    public function __invoke(ContentModel $contentModel, string $buffer, $element): string
    {
    	// If we are not in backend, return buffer
    	// If we are not inside the portfolio bundle, return buffer
    	if (!$this->isBackend() || Portfolio::getTable() !== $contentModel->ptable) {
    		return $buffer;
    	}

    	$arrLanguages = System::getContainer()->get('contao.intl.locales')->getLocales(null, false);
        $strLang = $contentModel->wem_language ? $arrLanguages[$contentModel->wem_language] : 'NR';

    	return '<strong>'.$strLang.'</strong><br>' . $buffer;
    }

    private function isBackend()
    {
        return $this->scopeMatcher->isBackendRequest($this->requestStack->getCurrentRequest());
    }
}