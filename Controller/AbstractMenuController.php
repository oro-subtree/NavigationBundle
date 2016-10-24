<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Form\Type\MenuUpdateType;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;

abstract class AbstractMenuController extends Controller
{
    /**
     * @var MenuUpdateManager
     */
    protected $manager;

    /**
     * @return string
     */
    abstract protected function getOwnershipType();

    /**
     * @throws AccessDeniedException
     */
    abstract protected function checkAcl();

    /**
     * @return array
     */
    protected function index()
    {
        $this->checkAcl();

        return [
            'ownershipType' => $this->getOwnershipType(),
            'entityClass' => MenuUpdate::class
        ];
    }

    /**
     * @param string $menuName
     * @return array
     */
    protected function view($menuName)
    {
        $this->checkAcl();

        $menu = $this->getMenu($menuName);

        return [
            'entity' => $menu,
            'ownershipType' => $this->getOwnershipType(),
            'tree' => $this->createMenuTree($menu)
        ];
    }

    /**
     * @param string  $menuName
     * @param string  $parentKey
     * @param integer $ownerId
     * @return array|RedirectResponse
     */
    protected function create($menuName, $parentKey, $ownerId)
    {
        $this->checkAcl();

        /** @var MenuUpdate $menuUpdate */
        $menuUpdate = $this->getManager()->createMenuUpdate(
            $this->getOwnershipType(),
            $ownerId,
            [
                'menu' => $menuName,
                'parentKey' => $parentKey,
                'custom' => true
            ]
        );

        return $this->handleUpdate($menuUpdate);

    }

    /**
     * @param string  $menuName
     * @param string  $key
     * @param integer $ownerId
     * @return array|RedirectResponse
     */
    protected function update($menuName, $key, $ownerId)
    {
        $this->checkAcl();

        $menuUpdate = $this->getManager()->getMenuUpdateByKeyAndScope(
            $menuName,
            $key,
            $this->getOwnershipType(),
            $ownerId
        );

        if (!$menuUpdate->getKey()) {
            throw $this->createNotFoundException(
                sprintf("Item \"%s\" in \"%s\" not found.", $key, $menuName)
            );
        }

        return $this->handleUpdate($menuUpdate);
    }

    /**
     * @param $menuUpdate
     * @return array|RedirectResponse
     */
    protected function handleUpdate(MenuUpdateInterface $menuUpdate)
    {
        $form = $this->createForm(MenuUpdateType::NAME, $menuUpdate);

        $response = $this->get('oro_form.model.update_handler')->update(
            $menuUpdate,
            $form,
            $this->get('translator')->trans('oro.navigation.menuupdate.saved_message')
        );

        if (is_array($response)) {
            $menu = $this->getMenu($menuUpdate->getMenu());

            $response['ownershipType'] = $this->getOwnershipType();
            $response['menuName'] = $menu->getName();
            $response['tree'] = $this->createMenuTree($menu);

            if ($menuUpdate->isCustom()) {
                $response['menuItem'] = null;
            } else {
                $response['menuItem'] = MenuUpdateUtils::findMenuItem($menu, $menuUpdate->getKey());
            }
        }

        return $response;
    }


    /**
     * @return MenuUpdateManager
     */
    protected function getManager()
    {
        return $this->get('oro_navigation.manager.menu_update_default');
    }

    /**
     * @param $menuName
     * @return \Knp\Menu\ItemInterface
     */
    protected function getMenu($menuName)
    {
        $options = [
            'ownershipType' => $this->getOwnershipType()
        ];
        $menu = $this->getManager()->getMenu($menuName, $options);
        if (!count($menu->getChildren())) {
            throw $this->createNotFoundException(sprintf("Menu \"%s\" not found.", $menuName));
        }

        return $menu;
    }

    /**
     * @param $menu
     * @return array
     */
    protected function createMenuTree($menu)
    {
        return $this->get('oro_navigation.tree.menu_update_tree_handler')->createTree($menu);
    }

}
