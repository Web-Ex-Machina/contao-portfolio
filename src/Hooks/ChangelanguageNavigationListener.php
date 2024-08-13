<?php declare(strict_types=1);

namespace WEM\PortfolioBundle\Hooks;


use Contao\Input;
use Terminal42\ChangeLanguage\Event\ChangelanguageNavigationEvent;

class ChangelanguageNavigationListener
{
    public function __invoke(ChangelanguageNavigationEvent $event)
    {
        $event->getUrlParameterBag()->setQueryParameter('uid', $this->getUid());
    }

    private function getUid(): ?string
    {
        $uid = (string)Input::get('uid', false, true);

        return '' === $uid ? null : $uid;

    }

}