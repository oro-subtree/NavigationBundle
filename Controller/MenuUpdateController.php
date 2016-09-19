<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Form\Type\MenuUpdateType;

/**
 * @Route("/menuupdate")
 */
class MenuUpdateController extends Controller
{
    /**
     * @Route(
     *     "/{menu}/create/{parentKey}",
     *     name="oro_navigation_menu_update_create",
     *     defaults={"parentKey" = null},
     *     requirements={"menu" = "[-_\w]+", "parentKey" = "[-_w]+"}
     * )
     * @Template("OroNavigationBundle:MenuUpdate:update.html.twig")
     * @Acl(
     *     id="oro_navigation_menu_update_create",
     *     type="entity",
     *     class="OroNavigationBundle:MenuUpdate",
     *     permission="CREATE"
     * )
     *
     * @param string $menu
     * @param string|null $parentKey
     * @return array|RedirectResponse
     */
    public function createAction($menu, $parentKey)
    {
        return $this->update(new MenuUpdate());
    }

    /**
     * @Route(
     *     "/{menu}/update/{key}/{parentKey}",
     *     name="oro_navigation_menu_update_update",
     *     defaults={"parentKey" = null},
     *     requirements={"menu" = "[-_\w]+", "parentKey" = "[-_w]+"}
     * )
     * @Template()
     * @Acl(
     *     id="oro_navigation_menu_update_update",
     *     type="entity",
     *     class="OroNavigationBundle:MenuUpdate",
     *     permission="EDIT"
     * )
     *
     * @param string $menu
     * @param string $key
     * @param string $parentKey
     * @return array|RedirectResponse
     */
    public function updateAction($menu, $key, $parentKey)
    {
        return $this->update(new MenuUpdate());
    }

    /**
     * @param MenuUpdate $menuUpdate
     * @return array|RedirectResponse
     */
    private function update(MenuUpdate $menuUpdate)
    {
        $form = $this->createForm(MenuUpdateType::NAME, $menuUpdate);

        return $this->get('oro_form.model.update_handler')->update(
            $menuUpdate,
            $form,
            $this->get('translator')->trans('oro.navigation.menuupdate.saved_message')
        );
    }

    /**
     * @Route("/", name="oro_navigation_menu_update_index")
     * @Template()
     * @Acl(
     *     id="oro_navigation_menu_update_view",
     *     type="entity",
     *     class="OroNavigationBundle:MenuUpdate",
     *     permission="VIEW"
     * )
     *
     * @return array
     */
    public function indexAction()
    {
        return [];
    }

    /**
     * @Route("/{menu}", name="oro_navigation_menu_update_view", requirements={"menu" = "[-_\w]+"})
     * @Template()
     * @AclAncestor("oro_navigation_menu_update_view")
     *
     * @param string $menu
     * @return array
     */
    public function viewAction($menu)
    {
        return [];
    }

    /**
     * @Route("/", name="oro_navigation_menu_update_index")
     * @Template()
     *
     * @return array
     */
    public function indexAction()
    {
        return [];
    }

    /**
     * @Route("/{menu}", name="oro_navigation_menu_update_view")
     * @Template()
     *
     * @param string $menu
     *
     * @return array
     */
    public function viewAction($menu)
    {
        return [
            'entity' => $this->get('oro_menu.builder_chain')->get($menu)
        ];
    }
}
