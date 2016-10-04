<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @Route("/menu/global")
 */
class GlobalMenuController extends AbstractMenuController
{
    /**
     * {@inheritdoc}
     */
    protected function getOwnershipProvider()
    {
        return $this->get('oro_navigation.ownership_provider.global');
    }

    /**
     * @Route("/", name="oro_navigation_org_menu_index")
     * @Template
     *
     * @return array
     */
    public function indexAction()
    {
        return parent::indexAction();
    }

    /**
     * @Route("/{menuName}", name="oro_navigation_org_menu_view")
     * @Template
     *
     * @param string $menuName
     *
     * @return array
     */
    public function viewAction($menuName)
    {
        return parent::viewAction($menuName);
    }

    /**
     * @Route("/{menuName}/create/{parentKey}", name="oro_navigation_org_menu_create")
     * @Template("OroNavigationBundle:GlobalMenu:update.html.twig")
     *
     * @param string $menuName
     * @param string|null $parentKey
     *
     * @return array|RedirectResponse
     */
    public function createAction($menuName, $parentKey = null)
    {
        return parent::createAction($menuName, $parentKey);
    }

    /**
     * @Route("/{menuName}/update/{key}", name="oro_navigation_org_menu_update")
     * @Template
     *
     * @param string $menuName
     * @param string $key
     *
     * @return array|RedirectResponse
     */
    public function updateAction($menuName, $key)
    {
        return parent::updateAction($menuName, $key);
    }
}
