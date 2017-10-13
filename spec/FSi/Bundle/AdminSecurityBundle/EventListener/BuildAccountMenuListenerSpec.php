<?php

namespace spec\FSi\Bundle\AdminSecurityBundle\EventListener;

use FSi\Bundle\AdminBundle\Event\MenuEvent;
use FSi\Bundle\AdminBundle\Menu\Item\Item;
use FSi\Bundle\AdminSecurityBundle\Security\User\UserInterface;
use PhpSpec\ObjectBehavior;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class BuildAccountMenuListenerSpec extends ObjectBehavior
{
    function let(
        TranslatorInterface $translator,
        TokenStorageInterface $tokenStorage,
        TokenInterface $token,
        UserInterface $user
    ) {
        $tokenStorage->getToken()->willReturn($token);
        $token->getUser()->willReturn($user);
        $token->getUsername()->willReturn('some user');

        $translator->trans('admin.welcome', ['%username%' => 'some user'], 'FSiAdminSecurity')
            ->willReturn('Hello some user');
        $translator->trans('admin.change_password', [], 'FSiAdminSecurity')->willReturn('change password');
        $translator->trans('admin.logout', [], 'FSiAdminSecurity')->willReturn('logout');

        $this->beConstructedWith($translator, $tokenStorage);
    }

    function it_builds_account_menu()
    {
        $this->createAccountMenu(new MenuEvent(new Item()))->shouldHaveItem('account', false);
        $this->createAccountMenu(new MenuEvent(new Item()))->shouldHaveItemThatHaveChild(
            'account',
            'account.change-password',
            'fsi_admin_change_password'
        );
        $this->createAccountMenu(new MenuEvent(new Item()))->shouldHaveItemThatHaveChild(
            'account',
            'account.logout',
            'fsi_admin_security_user_logout'
        );
    }

    public function getMatchers()
    {
        return [
            'haveItem' => function(Item $menu, $itemName, $route = false) {
                $items = $menu->getChildren();
                foreach ($items as $item) {
                    if ($item->getName() === $itemName) {
                        if (!$route) {
                            return true;
                        }
                        /** @var ElementItem $item */
                        return $item->getRoute() === $route;
                    }
                }
                return false;
            },
            'haveItemThatHaveChild' => function(Item $menu, $itemName, $childName, $route = false) {
                foreach ($menu->getChildren() as $item) {
                    if ($item->getName() === $itemName && $item->hasChildren()) {
                        foreach ($item->getChildren() as $child) {
                            if ($child->getName() === $childName) {
                                if (!$route) {
                                    return true;
                                }
                                /** @var ElementItem $child */
                                return $child->getRoute() === $route;
                            }
                        }
                    }
                }
                return false;
            }
        ];
    }
}
