<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\AdminSecurityBundle\Controller;

use FSi\Bundle\AdminSecurityBundle\Event\AdminSecurityEvents;
use FSi\Bundle\AdminSecurityBundle\Event\ChangePasswordEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AdminController
{
    /**
     * @var \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    private $router;

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Templating\EngineInterface
     */
    private $templating;

    /**
     * @var \Symfony\Component\Form\FormInterface
     */
    private $changePasswordForm;

    /**
     * @var string
     */
    private $changePasswordActionTemplate;

    /**
     * @param EngineInterface $templating
     * @param FormInterface $changePasswordForm
     * @param TokenStorageInterface $tokenStorage
     * @param \Symfony\Component\Routing\RouterInterface $router
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
     * @param string $changePasswordActionTemplate
     */
    public function __construct(
        EngineInterface $templating,
        FormInterface $changePasswordForm,
        TokenStorageInterface $tokenStorage,
        RouterInterface $router,
        EventDispatcherInterface $eventDispatcher,
        $changePasswordActionTemplate
    ) {
        $this->templating = $templating;
        $this->changePasswordForm = $changePasswordForm;
        $this->tokenStorage = $tokenStorage;
        $this->router = $router;
        $this->eventDispatcher = $eventDispatcher;
        $this->changePasswordActionTemplate = $changePasswordActionTemplate;
    }

    public function changePasswordAction(Request $request)
    {
        $this->changePasswordForm->handleRequest($request);

        if ($this->changePasswordForm->isValid()) {
            $user = $this->tokenStorage->getToken()->getUser();
            $formData = $this->changePasswordForm->getData();

            $this->eventDispatcher->dispatch(
                AdminSecurityEvents::CHANGE_PASSWORD,
                new ChangePasswordEvent($user, $formData['plainPassword'])
            );

            $request->getSession()->invalidate();
            $this->tokenStorage->setToken(null);

            $request->getSession()->getFlashBag()->set(
                'success',
                'admin.change_password_message.success'
            );

            return new RedirectResponse($this->router->generate('fsi_admin_security_user_login'));
        }

        return $this->templating->renderResponse($this->changePasswordActionTemplate, array(
            'form' => $this->changePasswordForm->createView()
        ));
    }
}
