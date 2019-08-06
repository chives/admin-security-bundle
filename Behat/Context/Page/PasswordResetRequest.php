<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\AdminSecurityBundle\Behat\Context\Page;

use SensioLabs\Behat\PageObjectExtension\PageObject\Exception\UnexpectedPageException;
use SensioLabs\Behat\PageObjectExtension\PageObject\Page;

class PasswordResetRequest extends Page
{
    protected $path = '/admin/password-reset/request';

    public function verifyPage(): void
    {
        if (!$this->has('css', 'form[name="request"]')) {
            throw new UnexpectedPageException(
                sprintf("Page %s does not have a Password Reset Request form", $this->path)
            );
        }

        $this->verifyResponse();
    }

    public function getMessage(): string
    {
        return $this->find('css', '.alert')->getText();
    }
}
