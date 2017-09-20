<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\AdminSecurityBundle\Controller\Activation;

use FSi\Bundle\AdminBundle\Message\FlashMessages;
use FSi\Bundle\AdminSecurityBundle\Event\ActivationEvent;
use FSi\Bundle\AdminSecurityBundle\Event\AdminSecurityEvents;
use FSi\Bundle\AdminSecurityBundle\Event\ChangePasswordEvent;
use FSi\Bundle\AdminSecurityBundle\Security\User\ActivableInterface;
use FSi\Bundle\AdminSecurityBundle\Security\User\EnforceablePasswordChangeInterface;
use FSi\Bundle\AdminSecurityBundle\Security\User\UserRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;

class ActivationController
{
    /**
     * @var EngineInterface
     */
    private $templating;

    /**
     * @var string
     */
    private $changePasswordActionTemplate;

    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var FlashMessages
     */
    private $flashMessages;

    /**
     * @var string
     */
    private $changePasswordFormType;

    /**
     * @var array
     */
    private $changePasswordFormValidationGroups;

    public function __construct(
        EngineInterface $templating,
        $changePasswordActionTemplate,
        UserRepositoryInterface $userRepository,
        RouterInterface $router,
        FormFactoryInterface $formFactory,
        EventDispatcherInterface $eventDispatcher,
        FlashMessages $flashMessages,
        string $changePasswordFormType,
        array $changePasswordFormValidationGroups
    ) {
        $this->templating = $templating;
        $this->changePasswordActionTemplate = $changePasswordActionTemplate;
        $this->userRepository = $userRepository;
        $this->router = $router;
        $this->formFactory = $formFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->flashMessages = $flashMessages;
        $this->changePasswordFormType = $changePasswordFormType;
        $this->changePasswordFormValidationGroups = $changePasswordFormValidationGroups;
    }

    public function activateAction($token)
    {
        $user = $this->tryFindUserByActivationToken($token);

        if ($this->isUserEnforcedToChangePassword($user)) {
            $this->flashMessages->info(
                'admin.activation.message.change_password',
                [],
                'FSiAdminSecurity'
            );

            return new RedirectResponse(
                $this->router->generate('fsi_admin_activation_change_password', ['token' => $token])
            );
        } else {
            $this->eventDispatcher->dispatch(
                AdminSecurityEvents::ACTIVATION,
                new ActivationEvent($user)
            );

            return $this->addFlashAndRedirect('success', 'admin.activation.message.success');
        }
    }

    public function changePasswordAction(Request $request, $token)
    {
        $user = $this->tryFindUserByActivationToken($token);

        if (!$this->isUserEnforcedToChangePassword($user)) {
            throw new NotFoundHttpException();
        }

        $form = $this->formFactory->create(
            $this->changePasswordFormType,
            $user,
            ['validation_groups' => $this->changePasswordFormValidationGroups]
        );

        if ($form->handleRequest($request)->isSubmitted() && $form->isValid()) {
            $this->eventDispatcher->dispatch(
                AdminSecurityEvents::ACTIVATION,
                new ActivationEvent($user)
            );

            $this->eventDispatcher->dispatch(
                AdminSecurityEvents::CHANGE_PASSWORD,
                new ChangePasswordEvent($user)
            );

            return $this->addFlashAndRedirect('success', 'admin.activation.message.change_password_success');
        }

        return $this->templating->renderResponse(
            $this->changePasswordActionTemplate,
            ['form' => $form->createView()]
        );
    }

    /**
     * @param $token
     * @return ActivableInterface|null
     */
    private function tryFindUserByActivationToken($token)
    {
        $user = $this->userRepository->findUserByActivationToken($token);

        if (!($user instanceof ActivableInterface)) {
            throw new NotFoundHttpException();
        }

        if ($user->isEnabled()) {
            throw new NotFoundHttpException();
        }

        if (!$user->getActivationToken()->isNonExpired()) {
            throw new NotFoundHttpException();
        }

        return $user;
    }

    /**
     * @param string $type
     * @param string $message
     * @return RedirectResponse
     */
    private function addFlashAndRedirect($type, $message)
    {
        $this->flashMessages->{$type}($message, [], 'FSiAdminSecurity');

        return new RedirectResponse($this->router->generate('fsi_admin_security_user_login'));
    }

    /**
     * @param $user
     * @return bool
     */
    private function isUserEnforcedToChangePassword($user)
    {
        return ($user instanceof EnforceablePasswordChangeInterface) && $user->isForcedToChangePassword();
    }
}
