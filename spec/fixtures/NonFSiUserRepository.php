<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\AdminSecurityBundle\spec\fixtures;

use Doctrine\Common\Persistence\ObjectRepository;

abstract class NonFSiUserRepository implements ObjectRepository
{
}