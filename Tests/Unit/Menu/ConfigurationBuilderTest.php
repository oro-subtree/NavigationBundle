<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu;

use Oro\Bundle\NavigationBundle\Menu\AclAwareMenuFactoryExtension;
use Oro\Bundle\NavigationBundle\Menu\ConfigurationBuilder;
use Oro\Component\Config\Resolver\SystemAwareResolver;

use Knp\Menu\MenuItem;

class ConfigurationBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigurationBuilder $configurationBuilder
     */
    protected $configurationBuilder;

    /**
     * @var AclAwareMenuFactoryExtension
     */
    protected $factory;

    protected function setUp()
    {
        $resolver = new SystemAwareResolver();
        $this->configurationBuilder = new ConfigurationBuilder($resolver);

        $this->factory = $this->getMockBuilder('Knp\Menu\MenuFactory')
            ->setMethods(['getRouteInfo', 'processRoute'])
            ->getMock();

        $this->factory->expects($this->any())
            ->method('getRouteInfo')
            ->will($this->returnValue(false));

        $this->factory->expects($this->any())
            ->method('processRoute')
            ->will($this->returnSelf());
    }

    /**
     * @dataProvider menuStructureProvider
     * @param array $options
     */
    public function testBuild($options)
    {
        $this->configurationBuilder->setConfiguration($options);

        $menu = new MenuItem('navbar', $this->factory);
        $this->configurationBuilder->build($menu, [], 'navbar');

        $this->assertCount(2, $menu->getChildren());
        $this->assertEquals($options['tree']['navbar']['type'], $menu->getExtra('type'));
        $this->assertCount(
            count($options['tree']['navbar']['children']['user_user_show']['children']),
            $menu->getChild('user_user_show')
        );
        $this->assertEquals('user_user_show', $menu->getChild('user_user_show')->getName());
    }

    /**
     * @dataProvider setAreaToExtraProvider
     * @param array $options
     * @param string $expectedArea
     */
    public function testSetAreaToExtra($options, $expectedArea)
    {
        $this->configurationBuilder->setConfiguration($options);

        $menu = new MenuItem('navbar', $this->factory);
        $this->configurationBuilder->build($menu, [], 'navbar');

        $this->assertEquals($expectedArea, $menu->getExtra('area'));
    }

    public function setAreaToExtraProvider()
    {
        return [
            'with no area specified' => [
                'options' => [
                    'items' => [
                        'homepage' => [
                            'name' => 'Home page 2',
                            'label' => 'Home page title',
                            'route' => 'oro_menu_index',
                            'translateDomain' => 'SomeBundle',
                            'translateParameters' => [],
                            'routeParameters' => [],
                            'extras' => []
                        ]
                    ],
                    'tree' => [
                        'navbar' => [
                            'type' => 'navbar',
                            'children' => [
                                'homepage' => [
                                    'position' => 7,
                                    'children' => []
                                ]
                            ]
                        ]
                    ]
                ],
                'expectedArea' => 'default',
            ],
            'with area' => [
                'options' => [
                    'items' => [
                        'homepage' => [
                            'name' => 'Home page 2',
                            'label' => 'Home page title',
                            'route' => 'oro_menu_index',
                            'translateDomain' => 'SomeBundle',
                            'translateParameters' => [],
                            'routeParameters' => [],
                            'extras' => []
                        ]
                    ],
                    'tree' => [
                        'navbar' => [
                            'type' => 'navbar',
                            'area' => 'frontend',
                            'children' => [
                                'homepage' => [
                                    'position' => 7,
                                    'children' => []
                                ]
                            ]
                        ]
                    ]
                ],
                'expectedArea' => 'frontend',
            ]
        ];
    }

    /**
     * @return array
     */
    public function menuStructureProvider()
    {
        return [
            'full_menu' => [[
                'areas' => [],
                'templates' => [
                    'navbar' => [
                        'template' => 'OroNavigationBundle:Menu:navbar.html.twig'
                    ],
                    'dropdown' => [
                        'template' => 'OroNavigationBundle:Menu:dropdown.html.twig'
                    ]
                ],
                'items' => [
                    'homepage' => [
                        'name' => 'Home page 2',
                        'label' => 'Home page title',
                        'route' => 'oro_menu_index',
                        'translateDomain' => 'SomeBundle',
                        'translateParameters' => [],
                        'routeParameters' => [],
                        'extras' => []
                    ],
                    'user_registration_register' => [
                        'route' => 'oro_menu_submenu',
                        'translateDomain' => 'SomeBundle',
                        'translateParameters' => [],
                        'routeParameters' => [],
                        'extras' => []
                    ],
                    'user_user_show' => [
                        'translateDomain' => 'SomeBundle',
                        'translateParameters' => [],
                        'routeParameters' => [],
                        'extras' => []
                    ],
                ],
                'tree' => [
                    'navbar' => [
                        'type' => 'navbar',
                        'extras' => [
                            'brand' => 'Oro',
                            'brandLink' => '/'
                        ],
                        'children' => [
                            'user_user_show' => [
                                'position' => '10',
                                'children' => [
                                    'user_registration_register' => [
                                        'children' => []
                                    ]
                                ]
                            ],
                            'homepage' => [
                                'position' => 7,
                                'children' => []
                            ]
                        ]
                    ]
                ]
            ]]
        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Item key "user_user_show" duplicated in tree menu "navbar".
     */
    public function testBuildDuplicatedItemTreeCallException()
    {
        $options = [
            'areas' => [],
            'items' => [
                'user_registration_register' => [
                    'route' => 'oro_menu_submenu',
                    'extras' => []
                ],
                'user_user_show' => [
                    'translateDomain' => 'SomeBundle',
                    'extras' => []
                ],
            ],
            'tree' => [
                'navbar' => [
                    'type' => 'navbar',
                    'extras' => [],
                    'children' => [
                        'user_user_show' => [
                            'position' => '10',
                            'children' => [
                                'user_registration_register' => [
                                    'children' => [
                                        'user_user_show' => [
                                            'children' => []
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $this->configurationBuilder->setConfiguration($options);
        $menu = new MenuItem('navbar', $this->factory);
        $this->configurationBuilder->build($menu, [], 'navbar');
    }
}
