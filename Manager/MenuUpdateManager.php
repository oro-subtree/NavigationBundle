<?php

namespace Oro\Bundle\NavigationBundle\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Knp\Menu\ItemInterface;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\Helper\MenuUpdateHelper;
use Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider;

class MenuUpdateManager
{
    /** @var ManagerRegistry */
    private $managerRegistry;

    /** @var BuilderChainProvider */
    private $builderChainProvider;

    /** @var MenuUpdateHelper */
    private $menuUpdateHelper;

    /** @var string */
    private $entityClass;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param BuilderChainProvider $builderChainProvider
     * @param MenuUpdateHelper $menuUpdateHelper
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        BuilderChainProvider $builderChainProvider,
        MenuUpdateHelper $menuUpdateHelper
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->builderChainProvider = $builderChainProvider;
        $this->menuUpdateHelper = $menuUpdateHelper;
    }

    /**
     * @param string $entityClass
     *
     * @return MenuUpdateManager
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;

        return $this;
    }

    /**
     * @param int $ownership
     * @return MenuUpdateInterface
     */
    public function createMenuUpdate($ownership = MenuUpdateInterface::OWNERSHIP_GLOBAL)
    {
        /** @var MenuUpdateInterface $entity */
        $entity = new $this->entityClass;
        $entity->setOwnershipType($ownership);

        return $entity;
    }

    /**
     * @param string $menuName
     * @param string $key
     *
     * @return null|MenuUpdateInterface
     */
    public function getMenuUpdateByKey($menuName, $key)
    {
        /** @var MenuUpdateInterface $update */
        $update = $this->getRepository()->findOneBy(['menu' => $menuName, 'key' => $key]);
        if ($update) {
            return $update;
        }

        return $this->getMenuUpdateFromMenu($menuName, $key);
    }

    /**
     * @param string $menuName
     * @param string $key
     *
     * @return null|MenuUpdateInterface
     */
    private function getMenuUpdateFromMenu($menuName, $key)
    {
        $menu = $this->getMenu($menuName);
        $item = $this->menuUpdateHelper->findMenuItem($menu, $key);
        if ($item) {
            $update = $this->createMenuUpdate();
            $this->menuUpdateHelper->updateMenuUpdate($update, $item, $menu->getName());

            return $update;
        }

        return null;
    }

    /**
     * @param string $name
     *
     * @return ItemInterface
     */
    public function getMenu($name)
    {
        return $this->builderChainProvider->get($name);
    }

    /**
     * @return EntityRepository
     */
    private function getRepository()
    {
        return $this->getEntityManager()->getRepository($this->entityClass);
    }

    /**
     * @return EntityManager
     */
    private function getEntityManager()
    {
        return $this->managerRegistry->getManagerForClass($this->entityClass);
    }
}
