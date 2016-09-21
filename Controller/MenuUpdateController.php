<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Form\Type\MenuUpdateType;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;

/**
 * @Route("/menuupdate")
 */
class MenuUpdateController extends Controller
{
    /**
     * @Route("/", name="oro_navigation_menu_update_index")
     * @Template()
     * @AclAncestor("oro_navigation_menu_update_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => MenuUpdate::class
        ];
    }

    /**
     * @Route("/{menu}/create/{parentKey}", name="oro_navigation_menu_update_create")
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
    public function createAction($menu, $parentKey = null)
    {
        /** @var MenuUpdateManager $manager */
        $manager = $this->get('oro_navigation.manager.menu_update_default');

        /** @var MenuUpdate $menuUpdate */
        $menuUpdate = $manager->createMenuUpdate();

        if ($parentKey) {
            $parent = $manager->getMenuUpdateByKey($menu, $parentKey);

            if (!$parent) {
                throw $this->createNotFoundException();
            }

            $menuUpdate->setParentKey($parent->getKey());
        }

        $menuUpdate->setMenu($menu);

        return $this->update($menu, $menuUpdate);
    }

    /**
     * @Route("/{menu}/update/{key}", name="oro_navigation_menu_update_update")
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
     * @return array|RedirectResponse
     */
    public function updateAction($menu, $key)
    {
        /** @var MenuUpdateManager $manager */
        $manager = $this->get('oro_navigation.manager.menu_update_default');

        /** @var MenuUpdate $menuUpdate */
        $menuUpdate = $manager->getMenuUpdateByKey($menu, $key);
        if (!$menuUpdate) {
            throw $this->createNotFoundException();
        }

        return $this->update($menu, $menuUpdate);
    }

    /**
     * @Route("/{menu}", name="oro_navigation_menu_update_view", requirements={"menu" = "[-_\w]+"})
     * @Template()
     * @Acl(
     *     id="oro_navigation_menu_update_view",
     *     type="entity",
     *     class="OroNavigationBundle:MenuUpdate",
     *     permission="VIEW"
     * )
     *
     * @param string $menu
     * @return array
     */
    public function viewAction($menu)
    {
        $root = $this->get('oro_menu.builder_chain')->get($menu);
        if (!count($root->getChildren())) {
            throw $this->createNotFoundException("Menu '$menu' not found");
        }

        return [
            'entity' => $root,
            'menu' => $menu,
            'tree' => $this->getTree($menu),
        ];
    }

    /**
     * @param string $menu
     * @return array
     */
    public function getTree($menu)
    {
        $root = $this->get('oro_menu.builder_chain')->get($menu);

        return $this->get('oro_navigation.tree.menu_update_tree_handler')->createTree($root);
    }

    /**
     * @param string $menu
     * @param MenuUpdate $menuUpdate
     * @return array|RedirectResponse
     */
    private function update($menu, MenuUpdate $menuUpdate)
    {
        $form = $this->createForm(MenuUpdateType::NAME, $menuUpdate, ['menu_update_key' => $menuUpdate->getKey()]);

        $response = $this->get('oro_form.model.update_handler')->update(
            $menuUpdate,
            $form,
            $this->get('translator')->trans('oro.navigation.menuupdate.saved_message')
        );
        if (is_array($response)) {
            $response['menu'] = $menu;
            $response['tree'] = $this->getTree($menu);
        }

        return $response;
    }
}
