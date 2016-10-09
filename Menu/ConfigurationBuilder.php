<?php

namespace Oro\Bundle\NavigationBundle\Menu;

use Oro\Component\Config\Resolver\ResolverInterface;

use Knp\Menu\ItemInterface;

class ConfigurationBuilder implements BuilderInterface
{
    const DEFAULT_AREA = 'default';

    /**
     * @var array $container
     */
    protected $configuration;

    /** @var ResolverInterface */
    protected $resolver;

    /**
     * @param ResolverInterface $resolver
     */
    public function __construct(ResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * @param array $configuration
     */
    public function setConfiguration(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Modify menu by adding, removing or editing items.
     *
     * @param \Knp\Menu\ItemInterface $menu
     * @param array                   $options
     * @param string|null             $alias
     */
    public function build(ItemInterface $menu, array $options = array(), $alias = null)
    {
        $menuConfig = $this->configuration;

        if (!empty($menuConfig['items']) && !empty($menuConfig['tree'])) {
            foreach ($menuConfig['tree'] as $menuTreeName => $menuTreeElement) {
                if ($menuTreeName == $alias) {
                    if (!empty($menuTreeElement['extras'])) {
                        $menu->setExtras($menuTreeElement['extras']);
                    }

                    $defaultArea = ConfigurationBuilder::DEFAULT_AREA;
                    $this->setExtraFromConfig($menu, $menuTreeElement, 'type');
                    $this->setExtraFromConfig($menu, $menuTreeElement, 'area', $defaultArea);
                    $this->setExtraFromConfig($menu, $menuTreeElement, 'read_only', false);
                    $this->setExtraFromConfig($menu, $menuTreeElement, 'max_nesting_level', 0);
                    $menu->setExtra('editable', true);

                    $this->createFromArray($menu, $menuTreeElement['children'], $menuConfig['items'], $options);
                }
            }
        }
    }

    /**
     * @param ItemInterface $menu
     * @param array $config
     * @param string $optionName
     * @param mixed $default
     */
    private function setExtraFromConfig($menu, $config, $optionName, $default = null)
    {
        if (!empty($config[$optionName])) {
            $menu->setExtra($optionName, $config[$optionName]);
        } elseif ($default !== null) {
            $menu->setExtra($optionName, $default);
        }
    }

    /**
     * @param ItemInterface $menu
     * @param array         $data
     * @param array         $itemList
     * @param array         $options
     * @param array         $itemCodes
     */
    private function createFromArray(
        ItemInterface $menu,
        array $data,
        array &$itemList,
        array $options = [],
        array &$itemCodes = []
    ) {
        $isAllowed = false;
        foreach ($data as $itemCode => $itemData) {
            if (in_array($itemCode, $itemCodes)) {
                throw new \InvalidArgumentException(sprintf(
                    'Item key "%s" duplicated in tree menu "%s".',
                    $itemCode,
                    $menu->getRoot()->getName()
                ));
            }
            $itemCodes[] = $itemCode;

            $itemData = $this->resolver->resolve($itemData);
            if (!empty($itemList[$itemCode])) {
                $itemOptions = $itemList[$itemCode];

                if (empty($itemOptions['name'])) {
                    $itemOptions['name'] = $itemCode;
                }

                if (!empty($itemData['position'])) {
                    $itemOptions['extras']['position'] = $itemData['position'];
                }

                $this->moveToExtras($itemOptions, 'translateDomain');
                $this->moveToExtras($itemOptions, 'translateParameters');
                $this->moveToExtras($itemOptions, 'aclResourceId');

                $newMenuItem = $menu->addChild($itemOptions['name'], array_merge($itemOptions, $options));

                $editable = true;
                if (!empty($itemOptions['read_only'])) {
                    $editable = !$itemOptions['read_only'];
                }
                $newMenuItem->setExtra('editable', $editable);

                if (!empty($itemData['children'])) {
                    $this->createFromArray($newMenuItem, $itemData['children'], $itemList, $options, $itemCodes);
                }

                $isAllowed = $isAllowed || $newMenuItem->getExtra('isAllowed');
            }
        }
        $menu->setExtra('isAllowed', $isAllowed);
    }

    /**
     * @param array  $menuItem
     * @param string $optionName
     *
     * @return void
     */
    private function moveToExtras(array &$menuItem, $optionName)
    {
        if (isset($menuItem[$optionName])) {
            $menuItem['extras'][$optionName] = $menuItem[$optionName];
            unset($menuItem[$optionName]);
        }
    }
}
