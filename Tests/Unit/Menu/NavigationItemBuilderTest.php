<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\NavigationBundle\Menu\NavigationItemBuilder;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

class NavigationItemBuilderBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var \Symfony\Component\Security\Core\SecurityContextInterface
     */
    protected $securityContext;

    /**
     * @var NavigationItemBuilder
     */
    protected $builder;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var FeatureChecker
     */
    protected $featureChecker;

    /**
     * @var \Oro\Bundle\NavigationBundle\Entity\Builder\ItemFactory
     */
    protected $factory;

    protected function setUp()
    {
        $this->securityContext = $this->getMock('Symfony\Component\Security\Core\SecurityContextInterface');
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->factory = $this->getMock('Oro\Bundle\NavigationBundle\Entity\Builder\ItemFactory');
        $this->router = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();
        $this->featureChecker = $this->getMockBuilder(FeatureChecker::class)
            ->setMethods(['isResourceEnabled'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->builder = new NavigationItemBuilder($this->securityContext, $this->em, $this->factory, $this->router);
        $this->builder->setFeatureChecker($this->featureChecker);
        $this->builder->addFeature('email');
    }

    public function testBuildAnonUser()
    {
        $token = $this
            ->getMockForAbstractClass(
                'Symfony\Component\Security\Core\Authentication\Token\TokenInterface',
                [],
                '',
                true,
                true,
                true,
                ['getUser', 'getOrganizationContext']
            )
        ;
        $token->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue('anon.'));

        $token->expects($this->never())->method('getOrganizationContext');

        $this->securityContext->expects($this->atLeastOnce())
            ->method('getToken')
            ->will($this->returnValue($token));

        $menu = $this->getMockBuilder('Knp\Menu\ItemInterface')
            ->getMock();
        $menu->expects($this->never())
            ->method('addChild');
        $menu->expects($this->once())
            ->method('setExtra')
            ->with('type', 'pinbar');

        $this->builder->build($menu, array(), 'pinbar');
    }

    public function testBuild()
    {
        $organization   = new Organization();
        $type           = 'favorite';
        $userId         = 1;
        $user = $this->getMockBuilder('stdClass')
            ->setMethods(array('getId'))
            ->getMock();
        $user->expects($this->once($userId))
            ->method('getId')
            ->will($this->returnValue(1));
        $token = $this->getMockBuilder(
            'Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken'
        )
            ->disableOriginalConstructor()
            ->getMock();
        $token->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($user));
        $token->expects($this->once())
            ->method('getOrganizationContext')
            ->will($this->returnValue($organization));
        $this->securityContext->expects($this->atLeastOnce())
            ->method('getToken')
            ->will($this->returnValue($token));

        $item = $this->getMock('Oro\Bundle\NavigationBundle\Entity\NavigationItemInterface');
        $this->factory->expects($this->once())
            ->method('createItem')
            ->with($type, array())
            ->will($this->returnValue($item));

        $repository = $this->getMockBuilder('Oro\Bundle\NavigationBundle\Entity\Repository\NavigationItemRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $items = array(
            array('id' => 1, 'title' => 'test1', 'url' => '/', 'type' => $type),
            array('id' => 2, 'title' => 'test2', 'url' => '/home', 'type' => $type)
        );
        $repository->expects($this->once())
            ->method('getNavigationItems')
            ->with($userId, $organization, $type)
            ->will($this->returnValue($items));
        $this->router->expects($this->exactly(2))
            ->method('match')
            ->with($this->isType('string'))
            ->willReturn(['_route' => 'route']);
        $this->featureChecker->expects($this->exactly(2))
            ->method('isResourceEnabled')
            ->with($this->anything())
            ->willReturn(true);
        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(get_class($item))
            ->will($this->returnValue($repository));

        $menu = $this->getMockBuilder('Knp\Menu\ItemInterface')
            ->getMock();
        $menu->expects($this->exactly(2))
            ->method('addChild');
        $menu->expects($this->once())
            ->method('setExtra')
            ->with('type', $type);

        $this->builder->build($menu, array(), $type);
    }
}
