<?php

namespace Oro\Bundle\NavigationBundle\Tests\Behat\Context;

use Oro\Bundle\ConfigBundle\Tests\Behat\Element\SystemConfigForm;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactoryAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\ElementFactoryDictionary;

class FeatureContext extends OroFeatureContext implements OroElementFactoryAware
{
    use ElementFactoryDictionary;

    /**
     * @Given uncheck Use Default for :label field
     */
    public function uncheckUseDefaultForField($label)
    {
        /** @var SystemConfigForm $form */
        $form = $this->createElement('SystemConfigForm');
        $form->uncheckUseDefaultCheckbox($label);
    }

    /**
     * @When I save setting
     */
    public function iSaveSetting()
    {
        $this->getPage()->pressButton('Save settings');
    }

    /**
     * @Then menu must be on left side
     * @Then menu is on the left side
     */
    public function menuMustBeOnLeftSide()
    {
        self::assertFalse($this->createElement('MainMenu')->hasClass('main-menu-top'));
    }

    /**
     * @Then menu must be at top
     * @Then menu is at the top
     */
    public function menuMustBeOnRightSide()
    {
        self::assertTrue($this->createElement('MainMenu')->hasClass('main-menu-top'));
    }

    /**
     * @When /^(?:|I )click "(?P<link>[^"]+)" in shortcuts search results$/
     */
    public function clickInShortcutsSearchResults($link)
    {
        $result = $this->spin(function (FeatureContext $context) use ($link) {
            $result = $context->getPage()->find('css', sprintf('li[data-value="%s"] a', $link));

            if ($result && $result->isVisible()) {
                return $result;
            }

            return false;
        });

        self::assertNotFalse($result, sprintf('Link "%s" not found', $link));

        $result->click();
    }

    /**
     * @When /^(?:|I )(?P<action>(pin|unpin)) page$/
     */
    public function iPinPage($action)
    {
        $button = $this->getPage()->findButton('Pin/unpin the page');
        self::assertNotNull($button, 'Pin/Unpin button not found on page');

        $activeClass = 'gold-icon';

        if ('pin' === $action) {
            if ($button->hasClass($activeClass)) {
                self::fail('Can\'t pin tab that already pinned');
            }

            $button->press();
        } elseif ('unpin' === $action) {
            if (!$button->hasClass($activeClass)) {
                self::fail('Can\'t unpin tab that not pinned before');
            }

            $button->press();
        }
    }

    /**
     * @Given /^(?P<link>[\w\s]+) link must not be in pin holder$/
     */
    public function usersLinkMustNotBeInPinHolder($link)
    {
        $linkElement = $this->getPage()->findElementContains('PinBarLink', $link);
        self::assertFalse($linkElement->isValid(), "Link with '$link' anchor found, but it's not expected");
    }

    /**
     * @Then /^(?P<link>[\w\s]+) link must be in pin holder$/
     */
    public function linkMustBeInPinHolder($link)
    {
        $linkElement = $this->getPage()->findElementContains('PinBarLink', $link);
        self::assertTrue($linkElement->isValid(), "Link with '$link' anchor not found");
    }

    /**
     * @When /^(?:|I )follow (?P<link>[\w\s]+) link in pin holder$/
     */
    public function followUsersLinkInPinHolder($link)
    {
        $linkElement = $this->getPage()->findElementContains('PinBarLink', $link);
        self::assertTrue($linkElement->isValid(), "Link with '$link' anchor not found");

        $linkElement->click();
    }

    /**
     * @When press Create User button
     */
    public function pressCreateUserButton()
    {
        $this->getPage()->find('css', 'div.title-buttons-container a.btn-primary')->click();
    }

}
