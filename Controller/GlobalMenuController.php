<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

/**
 * @Route("/menu/global")
 */
class GlobalMenuController extends AbstractMenuController
{
    /**
     * @Route("/", name="oro_navigation_global_menu_index")
     * @Template
     * @AclAncestor("oro_navigation_manage_menus")
     *
     * @return array
     */
    public function indexAction()
    {
        $this->checkAcl();

        return $this->index();
    }

    /**
     * @Route("/{menuName}", name="oro_navigation_global_menu_view")
     * @Template
     * @AclAncestor("oro_navigation_manage_menus")
     *
     * @param string $menuName
     *
     * @return array
     */
    public function viewAction($menuName)
    {
        $this->checkAcl();

        return $this->view($menuName);
    }

    /**
     * @Route("/{menuName}/create/{parentKey}", name="oro_navigation_global_menu_create")
     * @Template("OroNavigationBundle:GlobalMenu:update.html.twig")
     * @AclAncestor("oro_navigation_manage_menus")
     *
     * @param string $menuName
     * @param string|null $parentKey
     *
     * @return array|RedirectResponse
     */
    public function createAction($menuName, $parentKey = null)
    {
        $this->checkAcl();

        return parent::create($menuName, $parentKey, $this->getOwnerId());
    }

    /**
     * @Route("/{menuName}/update/{key}", name="oro_navigation_global_menu_update")
     * @Template
     * @AclAncestor("oro_navigation_manage_menus")
     *
     * @param string $menuName
     * @param string $key
     *
     * @return array|RedirectResponse
     */
    public function updateAction($menuName, $key)
    {
        $this->checkAcl();

        return parent::update($menuName, $key, $this->getOwnerId());
    }

    /**
     * {@inheritdoc}
     */
    protected function getOwnershipType()
    {
        return $this->getOwnershipProvider()->getType();
    }

    /**
     * {@inheritDoc}
     */
    protected function getOwnerId()
    {
        return $this->getOwnershipProvider()->getId();
    }

    /**
     * @return \Oro\Bundle\NavigationBundle\Menu\Provider\GlobalOwnershipProvider
     */
    protected function getOwnershipProvider()
    {
        return $this->get('oro_navigation.ownership_provider.global');
    }

    /**
     * @throws AccessDeniedException
     */
    private function checkAcl()
    {
        if (!$this->get('oro_security.security_facade')->isGranted('oro_config_system')) {
            throw new AccessDeniedException('Insufficient permission');
        }
    }
}
