<?php

declare(strict_types=1);

namespace BannerManagerKit\Tests\Entity;

use PHPUnit\Framework\TestCase;
use BannerManagerKit\Entity\Slide;
use BannerManagerKit\Entity\Banner;
use BannerManagerKit\Entity\SlideType;
use BannerManagerKit\Entity\SlidePosition;

final class SlideTest extends TestCase
{
    private function withId(object $entity, int $id): object
    {
        $property = new \ReflectionProperty($entity, 'id');
        $property->setValue($entity, $id);

        return $entity;
    }

    public function testAccessors(): void
    {
        $slide = (new Slide())->setHandle('s1')->setName('Slide 1')->setExpressEntry('entry');

        $this->assertSame('s1', $slide->getHandle());
        $this->assertSame('Slide 1', $slide->getName());
        $this->assertSame('entry', $slide->getExpressEntry());
    }

    public function testSlideTypeHelpersFallBackWhenNoTypeSet(): void
    {
        $slide = new Slide();

        $this->assertSame('', $slide->getSlideTypeHandle());
        $this->assertSame('--', $slide->getSlideTypeName());
    }

    public function testSlideTypeHelpersUseAssignedType(): void
    {
        $type = (new SlideType())->setHandle('standard')->setName('Standard');
        $slide = (new Slide())->setSlideType($type);

        $this->assertSame($type, $slide->getSlideType());
        $this->assertSame('standard', $slide->getSlideTypeHandle());
        $this->assertSame('Standard', $slide->getSlideTypeName());
    }

    public function testAddAndRemoveSlidePositionLinkBothSides(): void
    {
        $slide = new Slide();
        $position = new SlidePosition();

        $slide->addSlidePosition($position)->addSlidePosition($position);
        $this->assertSame([$position], $slide->getSlidePositions());
        $this->assertSame($slide, $position->getSlide());

        $slide->removeSlidePosition($position);
        $this->assertSame([], $slide->getSlidePositions());
        $this->assertNull($position->getSlide());
    }

    public function testGetBannersMapsPositionsToBanners(): void
    {
        $bannerA = new Banner();
        $bannerB = new Banner();
        $slide = new Slide();
        $slide->addSlidePosition((new SlidePosition())->setBanner($bannerA));
        $slide->addSlidePosition((new SlidePosition())->setBanner($bannerB));

        $this->assertSame([$bannerA, $bannerB], $slide->getBanners());
    }

    public function testGetBannerIdsSkipsPositionsWithoutBanner(): void
    {
        $slide = new Slide();
        $slide->addSlidePosition((new SlidePosition())->setBanner($this->withId(new Banner(), 7)));
        $slide->addSlidePosition(new SlidePosition());
        $slide->addSlidePosition((new SlidePosition())->setBanner($this->withId(new Banner(), 9)));

        $this->assertEquals([7, 9], $slide->getBannerIds());
    }

    public function testGetAttributeReturnsNullWithoutExpressEntry(): void
    {
        $this->assertNull((new Slide())->getAttribute('title'));
    }

    public function testGetAttributeRejectsInvalidHandles(): void
    {
        $entry = new class {
            public function __call($name, $args)
            {
                throw new \LogicException('must not be called');
            }
        };
        $slide = (new Slide())->setExpressEntry($entry);

        $this->assertNull($slide->getAttribute('bad-handle'));
        $this->assertNull($slide->getAttribute('x(); drop'));
        $this->assertNull($slide->getAttribute(''));
    }

    public function testGetAttributeCallsCapitalisedGetterOnEntry(): void
    {
        $entry = new class {
            public ?string $called = null;

            public function __call($name, $args)
            {
                $this->called = $name;

                return 'value';
            }
        };
        $slide = (new Slide())->setExpressEntry($entry);

        $this->assertSame('value', $slide->getAttribute('button_text'));
        $this->assertSame('getButton_text', $entry->called);
    }
}
