<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;
use Oro\Bundle\NavigationBundle\Menu\Provider\GlobalOwnershipProvider;
use Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\MenuUpdateStub;
use Oro\Bundle\NavigationBundle\Tests\Unit\MenuItemTestTrait;

class MenuUpdateManagerTest extends \PHPUnit_Framework_TestCase
{
    const MENU_ID = 'menu';
    use MenuItemTestTrait;

    /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityRepository;

    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityManager;

    /** @var BuilderChainProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $builderChainProvider;

    /** @var MenuUpdateManager */
    protected $manager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->entityRepository = $this->getMock(EntityRepository::class, [], [], '', false);

        $this->entityManager = $this->getMock(EntityManager::class, [], [], '', false);
        $this->entityManager
            ->expects($this->any())
            ->method('getRepository')
            ->with(MenuUpdateStub::class)
            ->will($this->returnValue($this->entityRepository));

        /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject $managerRegistry */
        $managerRegistry = $this->getMock(ManagerRegistry::class);
        $managerRegistry
            ->expects($this->any())
            ->method('getManagerForClass')
            ->with(MenuUpdateStub::class)
            ->will($this->returnValue($this->entityManager));

        $this->builderChainProvider = $this->getMock(BuilderChainProvider::class, [], [], '', false);

        $this->manager = new MenuUpdateManager($managerRegistry, $this->builderChainProvider);
    }

    public function testCreateMenuUpdate()
    {
        $ownershipType = GlobalOwnershipProvider::TYPE;
        $ownerId = 1;

        $entity = new MenuUpdateStub();
        $entity
            ->setOwnershipType($ownershipType)
            ->setOwnerId($ownerId)
            ->setMenu(self::MENU_ID)
        ;

        $this->manager->setEntityClass(MenuUpdateStub::class);
        $result = $this->manager->createMenuUpdate($ownershipType, $ownerId, ['menu'=> self::MENU_ID]);
        $entity->setKey($result->getKey());

        $this->assertEquals($entity, $result);
        $this->assertEquals($entity->getOwnershipType(), $result->getOwnershipType());
        $this->assertEquals($entity->getOwnerId(), $result->getOwnerId());
    }

    public function testGetMenuUpdatesByMenuAndScope()
    {
        $menuName = 'test-menu';
        $ownershipType = GlobalOwnershipProvider::TYPE;
        $ownerId = 1;

        $update = new MenuUpdateStub();

        $this->manager->setEntityClass(MenuUpdateStub::class);

        $this->entityRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['menu' => $menuName, 'ownershipType' => $ownershipType, 'ownerId' => $ownerId])
            ->will($this->returnValue([$update]));

        $result = $this->manager->getMenuUpdatesByMenuAndScope($menuName, $ownershipType, $ownerId);
        $this->assertEquals([$update], $result);
    }

    public function testGetMenuUpdateByKeyAndScopeWithMenuItem()
    {
        $key = 'item-1-1-1';
        $ownershipType = GlobalOwnershipProvider::TYPE;
        $ownerId = 1;

        $update = new MenuUpdateStub();
        $update
            ->setOwnershipType($ownershipType)
            ->setOwnerId($ownerId)
            ->setKey($key)
            ->setCustom(false)
        ;

        $menu = $this->getMenu();

        $item = $menu->getChild('item-1')
            ->getChild('item-1-1')
            ->getChild('item-1-1-1');

        $item->setUri('uri');

        $this->manager->setEntityClass(MenuUpdateStub::class);

        $this->entityRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['menu' => 'menu', 'key' => $key, 'ownershipType' => $ownershipType, 'ownerId' => $ownerId])
            ->will($this->returnValue(null));

        $this->builderChainProvider
            ->expects($this->once())
            ->method('get')
            ->with('menu')
            ->will($this->returnValue($menu));

        $result = $this->manager->getMenuUpdateByKeyAndScope('menu', $key, $ownershipType, $ownerId);

        $update
            ->setDefaultTitle('item-1-1-1')
            ->setParentKey('item-1-1')
            ->setMenu('menu')
            ->setUri('uri');

        $this->assertEquals($update, $result);
    }

    public function testGetMenuUpdateByKeyAndScopeWithoutMenuItem()
    {
        $key = 'item-1-1-1-1';
        $ownershipType = GlobalOwnershipProvider::TYPE;
        $ownerId = 1;

        $update = new MenuUpdateStub();
        $update
            ->setOwnershipType($ownershipType)
            ->setOwnerId($ownerId)
            ->setKey($key)
            ->setCustom(true)
            ->setMenu(self::MENU_ID)
        ;

        $menu = $this->getMenu();

        $this->manager->setEntityClass(MenuUpdateStub::class);

        $this->entityRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['menu' => self::MENU_ID, 'key' => $key, 'ownershipType' => $ownershipType, 'ownerId' => $ownerId])
            ->will($this->returnValue(null));

        $this->builderChainProvider
            ->expects($this->once())
            ->method('get')
            ->with('menu')
            ->will($this->returnValue($menu));

        $result = $this->manager->getMenuUpdateByKeyAndScope('menu', $key, $ownershipType, $ownerId);

        $this->assertEquals($update, $result);
    }

    public function testGetReorderedMenuUpdates()
    {
        $this->manager->setEntityClass(MenuUpdateStub::class);

        $ownershipType = GlobalOwnershipProvider::TYPE;
        $ownerId = 1;

        $menu = $this->getMenu();

        $this->builderChainProvider
            ->expects($this->any())
            ->method('get')
            ->with('menu')
            ->will($this->returnValue($menu));

        $update1 = new MenuUpdateStub();
        $update1->setKey('item-2');
        $update1->setPriority(1);

        $update3 = new MenuUpdateStub();
        $update3->setKey('item-4');
        $update3->setPriority(3);

        $updates = [$update1, $update3];

        $this->entityRepository
            ->expects($this->once())
            ->method('findBy')
            ->with([
                'menu' => 'menu',
                'key' => ['item-1', 'item-2', 'item-3', 'item-4'],
                'ownershipType' => $ownershipType,
                'ownerId' => $ownerId,
            ])
            ->will($this->returnValue($updates));

        $update0 = new MenuUpdateStub();
        $update0->setKey('item-1');
        $update0->setDefaultTitle('item-1');
        $update0->setMenu('menu');
        $update0->setParentKey(null);
        $update0->setPriority(0);
        $update0->setOwnershipType($ownershipType);
        $update0->setOwnerId($ownerId);

        $update2 = new MenuUpdateStub();
        $update2->setKey('item-3');
        $update2->setDefaultTitle('item-3');
        $update2->setMenu('menu');
        $update0->setParentKey(null);
        $update2->setPriority(2);
        $update2->setOwnershipType($ownershipType);
        $update2->setOwnerId($ownerId);

        $orderedChildren = array_values($menu->getChildren());
        $this->assertEquals(
            [$update1, $update3, $update0, $update2],
            $this->manager->getReorderedMenuUpdates('menu', $orderedChildren, $ownershipType, $ownerId)
        );
    }

    public function testShowMenuItem()
    {
        $ownershipType = GlobalOwnershipProvider::TYPE;
        $ownerId = 1;
        $menuName = 'menu';

        $this->manager->setEntityClass(MenuUpdateStub::class);

        $menu = $this->getMenu();
        $menu->getChild('item-1')->setDisplay(false);
        $menu->getChild('item-1')->getChild('item-1-1')->setDisplay(false);
        $menu->getChild('item-1')->getChild('item-1-1')->getChild('item-1-1-1')->setDisplay(false);

        $this->builderChainProvider
            ->expects($this->any())
            ->method('get')
            ->with($menuName)
            ->will($this->returnValue($menu));

        $update1 = new MenuUpdateStub();
        $update1
            ->setMenu($menuName)
            ->setOwnershipType($ownershipType)
            ->setOwnerId($ownerId)
            ->setKey('item-1')
            ->setParentKey(null)
            ->setCustom(false)
            ->setActive(true)
            ->setDefaultTitle('item-1')
        ;

        $update11 = new MenuUpdateStub();
        $update11
            ->setMenu($menuName)
            ->setOwnershipType($ownershipType)
            ->setOwnerId($ownerId)
            ->setKey('item-1-1')
            ->setParentKey('item-1')
            ->setCustom(false)
            ->setActive(true)
            ->setDefaultTitle('item-1-1')
        ;

        $update111 = new MenuUpdateStub();
        $update111
            ->setMenu($menuName)
            ->setOwnershipType($ownershipType)
            ->setOwnerId($ownerId)
            ->setKey('item-1-1-1')
            ->setParentKey('item-1-1')
            ->setCustom(false)
            ->setActive(true)
            ->setDefaultTitle('item-1-1-1')
        ;

        $this->entityManager->expects($this->exactly(3))
            ->method('persist')
            ->with($this->logicalOr(
                $this->equalTo($update1),
                $this->equalTo($update11),
                $this->equalTo($update111)
            ));

        $this->manager->showMenuItem($menuName, 'item-1-1', $ownershipType, $ownerId);
    }

    public function testHideMenuItem()
    {
        $ownershipType = GlobalOwnershipProvider::TYPE;
        $ownerId = 1;
        $menuName = 'menu';

        $this->manager->setEntityClass(MenuUpdateStub::class);

        $menu = $this->getMenu();

        $this->builderChainProvider
            ->expects($this->any())
            ->method('get')
            ->with($menuName)
            ->will($this->returnValue($menu));

        $update11 = new MenuUpdateStub();
        $update11
            ->setMenu($menuName)
            ->setOwnershipType($ownershipType)
            ->setOwnerId($ownerId)
            ->setKey('item-1-1')
            ->setParentKey('item-1')
            ->setCustom(false)
            ->setActive(false)
            ->setDefaultTitle('item-1-1')
        ;

        $update111 = new MenuUpdateStub();
        $update111
            ->setMenu($menuName)
            ->setOwnershipType($ownershipType)
            ->setOwnerId($ownerId)
            ->setKey('item-1-1-1')
            ->setParentKey('item-1-1')
            ->setCustom(false)
            ->setActive(false)
            ->setDefaultTitle('item-1-1-1')
        ;

        $this->entityManager->expects($this->exactly(2))
            ->method('persist')
            ->with($this->logicalOr(
                $this->equalTo($update11),
                $this->equalTo($update111)
            ));

        $this->manager->hideMenuItem($menuName, 'item-1-1', $ownershipType, $ownerId);
    }

    public function testGetMenu()
    {
        $menu = $this->getMenu();

        $this->builderChainProvider
            ->expects($this->once())
            ->method('get')
            ->with('menu')
            ->will($this->returnValue($menu));

        $this->assertEquals($menu, $this->manager->getMenu('menu'));
    }

    public function testFindMenuItem()
    {
        $ownershipType = 1;

        $menu = $this->getMenu();

        $this->builderChainProvider
            ->expects($this->once())
            ->method('get')
            ->with('menu', ['ignoreCache' => true, 'ownershipType' => $ownershipType])
            ->will($this->returnValue($menu));

        $item = $menu->getChild('item-1')
            ->getChild('item-1-1')
            ->getChild('item-1-1-1');

        $this->assertEquals($item, $this->manager->findMenuItem('menu', 'item-1-1-1', $ownershipType));
    }

    public function testResetMenuUpdatesWithOwnershipType()
    {
        $ownershipType = GlobalOwnershipProvider::TYPE;
        $ownerId = 1;

        $update = new MenuUpdateStub();

        $this->manager->setEntityClass(MenuUpdateStub::class);

        $this->entityRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['ownershipType' => $ownershipType, 'ownerId' => $ownerId])
            ->will($this->returnValue([$update]));

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($update);

        $this->entityManager
            ->expects($this->once())
            ->method('flush')
            ->with([$update]);

        $this->manager->resetMenuUpdatesWithOwnershipType($ownershipType, $ownerId);
    }
}
