<?php

namespace Oro\Bundle\NavigationBundle\Controller\Api;

use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

/**
 * @RouteResource("menuupdates")
 * @NamePrefix("oro_api_")
 */
class MenuUpdateController extends Controller
{
    /**
     * @Delete("/menuupdate/{menuName}/{key}")
     *
     * @Acl(
     *     id="oro_navigation_menu_update_delete",
     *     type="entity",
     *     class="OroNavigationBundle:MenuUpdate",
     *     permission="DELETE"
     * )
     *
     * @ApiDoc(
     *  description="Delete menu item for user"
     * )
     *
     * @param string $menuName
     * @param string $key
     *
     * @return Response
     */
    public function deleteAction($menuName, $key)
    {
        /** @var MenuUpdateManager $manager */
        $manager = $this->get('oro_navigation.manager.menu_update_default');

        $userId = $this->getUser()->getId();
        $menuUpdate = $manager->getMenuUpdateByKeyAndScope($menuName, $key, MenuUpdate::OWNERSHIP_USER, $userId);

        if ($menuUpdate === null) {
            throw $this->createNotFoundException();
        }

        if (!$menuUpdate->isExistsInNavigationYml()) {
            $manager->removeMenuUpdate($menuUpdate);
        } else {
            $menuUpdate->setActive(false);
            $manager->updateMenuUpdate($menuUpdate);
        }

        return new JsonResponse(null, 204);
    }
}
