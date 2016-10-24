<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Controller;

use Oro\Bundle\NavigationBundle\Menu\Provider\GlobalOwnershipProvider;
use Oro\Bundle\NavigationBundle\Menu\Provider\UserOwnershipProvider;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolation
 */
class AjaxMenuControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());

        $this->loadFixtures([
            'Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures\MenuUpdateData'
        ]);
    }

    public function testCreateGlobal()
    {
        $parameters = [
            'menuName' => 'application_menu',
            'parentKey' => 'menu_update.1',
            'ownershipType' => GlobalOwnershipProvider::TYPE
        ];

        $this->client->request(
            'POST',
            $this->getUrl('oro_navigation_menuupdate_create', $parameters),
            [
                'ownerId' => 0,
                'isDivider' => true
            ]
        );

        $result = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($result, Response::HTTP_NO_CONTENT);
    }

    public function testCreateUser()
    {
        $parameters = [
            'menuName' => 'application_menu',
            'parentKey' => 'menu_update.3',
            'ownershipType' => UserOwnershipProvider::TYPE
        ];

        $this->client->request(
            'POST',
            $this->getUrl('oro_navigation_menuupdate_create', $parameters),
            [
                'ownerId' => 1,
                'isDivider' => true
            ]
        );

        $result = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($result, Response::HTTP_NO_CONTENT);
    }

    public function testDeleteGlobal()
    {
        $parameters = [
            'menuName' => 'application_menu',
            'key' => 'menu_update.1_1',
            'ownershipType' => GlobalOwnershipProvider::TYPE
        ];

        $this->client->request(
            'DELETE',
            $this->getUrl('oro_navigation_menuupdate_delete', $parameters),
            ['ownerId' => 0]
        );

        $result = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($result, Response::HTTP_NO_CONTENT);
    }

    public function testDeleteUser()
    {
        $parameters = [
            'menuName' => 'application_menu',
            'key' => 'menu_update.3_1',
            'ownershipType' => UserOwnershipProvider::TYPE
        ];

        $this->client->request(
            'DELETE',
            $this->getUrl('oro_navigation_menuupdate_delete', $parameters),
            ['ownerId' => 1]
        );

        $result = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($result, Response::HTTP_NO_CONTENT);
    }

    public function testShowGlobal()
    {
        $parameters = [
            'menuName' => 'application_menu',
            'key' => 'menu_update.2_1',
            'ownershipType' => GlobalOwnershipProvider::TYPE
        ];

        $this->client->request(
            'PUT',
            $this->getUrl('oro_navigation_menuupdate_show', $parameters),
            ['ownerId' => 0]
        );

        $result = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($result, Response::HTTP_OK);
    }

    public function testShowUser()
    {
        $parameters = [
            'menuName' => 'application_menu',
            'key' => 'menu_update.3',
            'ownershipType' => UserOwnershipProvider::TYPE
        ];

        $this->client->request(
            'PUT',
            $this->getUrl('oro_navigation_menuupdate_show', $parameters),
            ['ownerId' => 1]
        );

        $result = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($result, Response::HTTP_OK);
    }

    public function testHideGlobal()
    {
        $parameters = [
            'menuName' => 'application_menu',
            'key' => 'menu_update.2',
            'ownershipType' => GlobalOwnershipProvider::TYPE
        ];

        $this->client->request(
            'PUT',
            $this->getUrl('oro_navigation_menuupdate_hide', $parameters),
            ['ownerId' => 0]
        );

        $result = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($result, Response::HTTP_OK);
    }

    public function testHideUser()
    {
        $parameters = [
            'menuName' => 'application_menu',
            'key' => 'menu_update.3',
            'ownershipType' => UserOwnershipProvider::TYPE
        ];

        $this->client->request(
            'PUT',
            $this->getUrl('oro_navigation_menuupdate_hide', $parameters),
            ['ownerId' => 1]
        );

        $result = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($result, Response::HTTP_OK);
    }

    public function testResetGlobal()
    {
        $parameters = [
            'menuName' => 'application_menu',
            'ownershipType' => GlobalOwnershipProvider::TYPE
        ];

        $this->client->request(
            'DELETE',
            $this->getUrl('oro_navigation_menuupdate_reset', $parameters),
            ['ownerId' => 0]
        );

        $result = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($result, Response::HTTP_NO_CONTENT);
    }

    public function testResetUser()
    {
        $parameters = [
            'menuName' => 'application_menu',
            'ownershipType' => UserOwnershipProvider::TYPE
        ];

        $this->client->request(
            'DELETE',
            $this->getUrl('oro_navigation_menuupdate_reset', $parameters),
            ['ownerId' => 1]
        );

        $result = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($result, Response::HTTP_NO_CONTENT);
    }

    public function testMoveGlobal()
    {
        $parameters = [
            'menuName' => 'application_menu',
            'ownershipType' => GlobalOwnershipProvider::TYPE
        ];

        $this->client->request(
            'PUT',
            $this->getUrl('oro_navigation_menuupdate_move', $parameters),
            [
                'ownerId' => 0,
                'key' => 'menu_update.1',
                'parentKey' => 'application_menu',
                'position' => 33
            ]
        );

        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, Response::HTTP_OK);
    }

    public function testMoveUser()
    {
        $parameters = [
            'menuName' => 'application_menu',
            'ownershipType' => UserOwnershipProvider::TYPE
        ];

        $this->client->request(
            'PUT',
            $this->getUrl('oro_navigation_menuupdate_move', $parameters),
            [
                'ownerId' => 1,
                'key' => 'menu_update.3',
                'parentKey' => 'application_menu',
                'position' => 11
            ]
        );

        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, Response::HTTP_OK);
    }
}
