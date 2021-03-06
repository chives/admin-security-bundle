<?php

namespace FSi\Bundle\AdminSecurityBundle\Mailer;

use FSi\Bundle\AdminSecurityBundle\Model\UserPasswordResetInterface;
use Swift_Mailer;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Twig_Environment;

class Mailer implements MailerInterface
{
    /**
     * @var Swift_Mailer
     */
    private $mailer;

    /**
     * @var Twig_Environment
     */
    private $twig;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var string
     */
    private $templateName;

    /**
     * @var string
     */
    private $fromEmail;

    /**
     * @var string|null
     */
    private $replayToEmail;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(
        Swift_Mailer $mailer,
        Twig_Environment $twig,
        RouterInterface $router,
        RequestStack $requestStack,
        $templateName,
        $fromEmail,
        $replayToEmail = null
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->router = $router;
        $this->requestStack = $requestStack;
        $this->templateName = $templateName;
        $this->fromEmail = $fromEmail;
        $this->replayToEmail = $replayToEmail;
    }

    public function sendPasswordResetMail(UserPasswordResetInterface $user)
    {
        $masterRequest = $this->requestStack->getMasterRequest();

        list($subject, $htmlBody) = $this->prepareMessage(array(
            'user' => $user,
            'ip' => $masterRequest->getClientIp(),
            'user_agent' => $masterRequest->headers->get('User-Agent'),
        ));

        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setTo($user->getEmail())
            ->setFrom($this->fromEmail)
        ;

        if ($this->replayToEmail) {
            $message->setReplyTo($this->replayToEmail);
        }

        if (!empty($htmlBody)) {
            $message->setBody($htmlBody, 'text/html');
        }

        return $this->mailer->send($message);
    }

    protected function prepareMessage($data)
    {
        $templateContext = $this->twig->mergeGlobals($data);
        /** @var \Twig_Template $template */
        $template = $this->twig->loadTemplate($this->templateName);
        $subject = $template->renderBlock('subject', $templateContext);
        $htmlBody = $template->renderBlock('body_html', $templateContext);

        return array($subject, $htmlBody);
    }
}
