<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;

class MenuUpdateTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testGetExtras()
    {
        $priority = 10;

        $update = new MenuUpdate();
        $update->setPriority($priority);
        $update->setDivider(true);

        $this->assertEquals(
            [
                'position' => $priority,
                'divider' => true,
                'translateDisabled' => false
            ],
            $update->getExtras()
        );
    }
}
