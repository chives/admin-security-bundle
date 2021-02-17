<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\AdminSecurityBundle\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use FSi\Bundle\AdminSecurityBundle\Behat\Context\Page\Element\Datagrid;
use FSi\Bundle\AdminSecurityBundle\Behat\Context\Page\UserList;
use SensioLabs\Behat\PageObjectExtension\Context\PageObjectContext;

use function expect;

final class AdminUserManagementContext extends PageObjectContext
{
    /**
     * @var UserList
     */
    private $userListPage;

    public function __construct(UserList $userListPage)
    {
        $this->userListPage = $userListPage;
    }

    /**
     * @Then I should see following table:
     */
    public function iShouldSeeTable(TableNode $table): void
    {
        $datagrid = $this->getDatagrid();

        expect($datagrid->getRowCount())->toBe(count($table->getHash()));

        foreach ($table->getHash() as $rowIndex => $row) {
            foreach ($row as $key => $value) {
                $cell = $datagrid->getCellByColumnName($key, $rowIndex + 1);
                expect($cell->getText())->toBe($value);
            }
        }
    }

    /**
     * @Then I should have following list batch actions:
     */
    public function iShouldHaveFollowingListBatchActions(TableNode $table): void
    {
        expect(array_values($this->userListPage->getBatchActions()))->toBe(array_keys($table->getRowsHash()));
    }

    /**
     * @When I delete second user on the list
     */
    public function iDeleteSecondUserOnTheList(): void
    {
        $this->performBatchAction('Delete', 2);
    }

    /**
     * @When I reset password for the second user on the list
     */
    public function iResetPasswordForTheSecondUserOnTheList(): void
    {
        $this->performBatchAction('Reset password', 2);
    }

    /**
     * @When I resend activation token to the second user on the list
     */
    public function iResendActivationTokenToTheFirstUserOnTheList(): void
    {
        $this->performBatchAction('Resend activation token', 2);
    }

    /**
     * @When I click :name link
     */
    public function iPressLink(string $name): void
    {
        $this->userListPage->clickLink($name);
    }

    /**
     * @Then I fill form with valid user data
     */
    public function iFillFormWithValidUserData(): void
    {
        $this->userListPage->fillField('Email', 'new-user@fsi.pl');
        $this->userListPage->checkField('ROLE_ADMIN');
    }

    private function performBatchAction(string $action, int $cellIndex): void
    {
        $this->userListPage->getBatchActionsElement()->selectOption($action);

        $this->getDatagrid()->checkCellCheckbox($cellIndex);

        $this->userListPage->pressButton('Ok');
    }

    private function getDatagrid(): Datagrid
    {
        /** @var Datagrid $datagrid */
        $datagrid = $this->getElement('Datagrid');
        return $datagrid;
    }
}
