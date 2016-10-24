<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;
use Knp\Menu\ItemInterface;
use Oro\Bundle\FormBundle\Form\Type\OroIconType;
use Oro\Bundle\FormBundle\Tests\Unit\Form\Type\Stub\OroIconTypeStub;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationCollectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedPropertyType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizationCollectionTypeStub;
use Oro\Bundle\NavigationBundle\Validator\Constraints\MaxNestedLevelValidator;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Form\Type\MenuUpdateType;
use Oro\Bundle\LocaleBundle\Form\Type\TranslatedLocalizedFallbackValueCollectionType;

class MenuUpdateTypeTest extends FormIntegrationTestCase
{
    const TEST_TITLE = 'Test Title';
    const TEST_URI = 'http://test_uri';
    const TEST_ACL_RESCOURCE_ID = 'test_acl_rescource_id';

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {

        $registry = $this->getMock(ManagerRegistry::class);

        $translator = $this->getMock(TranslatorInterface::class);

        $kernel = $this->getMock(KernelInterface::class);

        return [
            new PreloadedExtension(
                [
                    TranslatedLocalizedFallbackValueCollectionType::NAME => new TranslatedLocalizedFallbackValueCollectionType(
                        $translator
                    ),
                    LocalizedFallbackValueCollectionType::NAME => new LocalizedFallbackValueCollectionType($registry),
                    LocalizedPropertyType::NAME => new LocalizedPropertyType(),
                    LocalizationCollectionType::NAME => new LocalizationCollectionTypeStub(),
                    'oro_icon_select' => new OroIconTypeStub($kernel),
                    'genemu_jqueryselect2_choice' => new Select2Type('choice'),
                ],
                []
            ),
            $this->getValidatorExtension(true)
        ];
    }

    public function testSubmitValid()
    {
        $menuUpdate = new MenuUpdate();
        $form = $this->factory->create(new MenuUpdateType(), $menuUpdate);

        $form->submit(
            [
                'titles' => [
                    'values' => [
                        'default' => self::TEST_TITLE
                    ]
                ],
                'icon'=> 'icon-anchor',
            ]
        );

        $expected = new MenuUpdate();
        $expectedTitle = (new LocalizedFallbackValue)->setString(self::TEST_TITLE);
        $expected->addTitle($expectedTitle);
        $expected->setIcon('icon-anchor');

        $expected->addDescription(new LocalizedFallbackValue);

        $this->assertFormOptionEqual(true, 'disabled', $form->get('uri'));
        $this->assertFormNotContainsField('aclResourceId', $form);

        $this->assertFormIsValid($form);
        $this->assertEquals($expected, $form->getData());
    }

    public function testSubmitIsCustom()
    {
        $menuUpdate = new MenuUpdate();
        $menuUpdate->setCustom(true);

        $form = $this->factory->create(new MenuUpdateType(), $menuUpdate);

        $form->submit(
            [
                'titles' => [
                    'values' => [
                        'default' => self::TEST_TITLE
                    ]
                ],
                'uri' => self::TEST_URI
            ]
        );

        $expected = new MenuUpdate();
        $expectedTitle = (new LocalizedFallbackValue)->setString(self::TEST_TITLE);
        $expected
            ->setCustom(true)
            ->addTitle($expectedTitle)
            ->addDescription(new LocalizedFallbackValue)
            ->setUri(self::TEST_URI);

        $this->assertFormIsValid($form);
        $this->assertEquals($expected, $form->getData());
    }

    public function testSubmitEmptyTitle()
    {
        $menuUpdate = new MenuUpdate();
        $form = $this->factory->create(new MenuUpdateType(), $menuUpdate);

        $form->submit([]);

        $expected = new MenuUpdate();
        $expectedTitle = (new LocalizedFallbackValue);
        $expected->addTitle($expectedTitle);
        $expected->addDescription(new LocalizedFallbackValue);

        $this->assertFormIsNotValid($form);
        $this->assertEquals($expected, $form->getData());
    }

    public function testSubmitCustomWithEmptyUri()
    {
        $menuUpdate = new MenuUpdate();
        $menuUpdate->setCustom(true);
        $form = $this->factory->create(new MenuUpdateType(), $menuUpdate);

        $form->submit(
            [
                'titles' => [
                    'values' => [
                        'default' => self::TEST_TITLE
                    ]
                ],
            ]
        );

        $expected = new MenuUpdate();
        $expectedTitle = (new LocalizedFallbackValue)->setString(self::TEST_TITLE);
        $expected
            ->setCustom(true)
            ->addDescription(new LocalizedFallbackValue)
            ->addTitle($expectedTitle);

        $this->assertFormIsNotValid($form);
        $this->assertEquals($expected, $form->getData());
    }

    public function testAclResourceIdShouldExist()
    {
        $menuUpdate = new MenuUpdate();
        $menuItem = $this->getMock(ItemInterface::class);
        $menuItem->expects($this->any())
            ->method('getExtra')
            ->with('aclResourceId')
            ->willReturn(self::TEST_ACL_RESCOURCE_ID);

        $form = $this->factory->create(new MenuUpdateType(), $menuUpdate, ['menu_item' => $menuItem]);

        $expected = new MenuUpdate();
        $expectedTitle = (new LocalizedFallbackValue)->setString(self::TEST_TITLE);
        $expected->addTitle($expectedTitle);
        $expected->addDescription(new LocalizedFallbackValue);

        $this->assertFormContainsField('aclResourceId', $form);
        $this->assertFormOptionEqual(true, 'disabled', $form->get('aclResourceId'));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ConstraintValidatorFactoryInterface
     */
    protected function getConstraintValidatorFactory()
    {
        /* @var $factory \PHPUnit_Framework_MockObject_MockObject|ConstraintValidatorFactoryInterface */
        $factory = $this->getMock('Symfony\Component\Validator\ConstraintValidatorFactoryInterface');
        $factory->expects($this->any())
            ->method('getInstance')
            ->willReturnCallback(
                function (Constraint $constraint) {
                    $className = $constraint->validatedBy();

                    if ($className === MaxNestedLevelValidator::class) {
                        $this->validators[$className] = $this->getMockBuilder(MaxNestedLevelValidator::class)
                            ->disableOriginalConstructor()
                            ->getMock();
                    }

                    if (!isset($this->validators[$className]) ||
                        $className === 'Symfony\Component\Validator\Constraints\CollectionValidator'
                    ) {
                        $this->validators[$className] = new $className();
                    }

                    return $this->validators[$className];
                }
            );

        return $factory;
    }
}
