<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\AdminSecurityBundle\EventListener;

use FSi\Bundle\AdminSecurityBundle\Security\Firewall\FirewallMapper;
use FSi\Bundle\AdminSecurityBundle\Security\User\EnforceablePasswordChangeInterface;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\FirewallMapInterface;

class EnforcePasswordChangeListener implements EventSubscriberInterface
{
    /**
     * @var FirewallMapper
     */
    private $firewallMapper;

    /**
     * @var FirewallMapInterface
     */
    private $firewallMap;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var string
     */
    private $firewallName;

    /**
     * @var string
     */
    private $changePasswordRoute;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @param FirewallMapper $firewallMapper
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenStorageInterface $tokenStorage
     * @param RouterInterface $router
     * @param string $firewallName
     * @param string $changePasswordRoute
     */
    public function __construct(
        FirewallMapper $firewallMapper,
        FirewallMapInterface $firewallMap,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage,
        RouterInterface $router,
        $firewallName,
        $changePasswordRoute
    ) {
        $this->firewallMapper = $firewallMapper;
        $this->firewallMap = $firewallMap;
        $this->authorizationChecker = $authorizationChecker;
        $this->firewallName = $firewallName;
        $this->changePasswordRoute = $changePasswordRoute;
        $this->router = $router;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest'
        ];
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($event->getRequestType() !== HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return;
        }

        if (!$this->isConfiguredFirewall($event->getRequest())) {
            return;
        }

        if (!$this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            return;
        }

        $user = $token->getUser();
        if (!($user instanceof EnforceablePasswordChangeInterface) ||
            !$user->isForcedToChangePassword()) {
            return;
        }

        if ($event->getRequest()->get('_route') !== $this->changePasswordRoute) {
            $event->setResponse(new RedirectResponse($this->router->generate($this->changePasswordRoute)));
        } else {
            $event->stopPropagation();
        }
    }

    /**
     * @param Request $request
     * @return boolean
     */
    private function isConfiguredFirewall(Request $request)
    {
        if (method_exists($this->firewallMap, 'getFirewallConfig')) {
            $firewallName = $this->firewallMap->getFirewallConfig($request)->getName();
        } else {
            $firewallName = $this->firewallMapper->getFirewallName($request);
        }

        return !empty($firewallName) && $firewallName === $this->firewallName;
    }
}
