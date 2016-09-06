<?php

namespace Oro\Bundle\NavigationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\NavigationBundle\Model\ExtendMenuUpdate;

/**
 * @ORM\Entity()
 * @ORM\Table(name="oro_navigation_menu_update")
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-th"
 *          }
 *      }
 * )
 */
class MenuUpdate extends ExtendMenuUpdate
{
    const OWNERSHIP_BUSINESS_UNIT = 3;
    const OWNERSHIP_USER          = 4;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=100)
     */
    protected $title;

    /**
     * {@inheritdoc}
     */
    public function getExtras()
    {
        return [
            'title' => $this->title
        ];
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     * @return MenuUpdate
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }
}
