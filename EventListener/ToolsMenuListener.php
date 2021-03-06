<?php

namespace FSi\Bundle\AdminSecurityBundle\EventListener;

use FSi\Bundle\AdminBundle\Event\MenuEvent;
use FSi\Bundle\AdminBundle\Menu\Item\Item;
use FSi\Bundle\AdminBundle\Menu\Item\RoutableItem;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ToolsMenuListener
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(
        TranslatorInterface $translator,
        TokenStorageInterface $tokenStorage
    ) {
        $this->translator = $translator;
        $this->tokenStorage = $tokenStorage;
    }

    public function createAccountMenu(MenuEvent $event)
    {
        $rootItem = $this->createRootItem();

        $changePasswordItem = new RoutableItem('account.change-password', 'fsi_admin_change_password');
        $changePasswordItem->setLabel($this->translator->trans('admin.change_password', array(), 'FSiAdminSecurity'));
        $rootItem->addChild($changePasswordItem);

        $logoutItem = new RoutableItem('account.logout', 'fsi_admin_security_user_logout');
        $logoutItem->setLabel($this->translator->trans('admin.logout', array(), 'FSiAdminSecurity'));
        $rootItem->addChild($logoutItem);

        $event->getMenu()->addChild($rootItem);

        return $event->getMenu();
    }

    /**
     * @return Item
     */
    private function createRootItem()
    {
        $rootItem = new Item('account');

        $rootItem->setLabel(
            $this->translator->trans(
                'admin.welcome',
                array('%username%' => $this->tokenStorage->getToken()->getUsername()),
                'FSiAdminSecurity'
            )
        );

        $rootItem->setOptions(array(
            'attr' => array(
                'id' => 'account',
            )
        ));

        return $rootItem;
    }
}
