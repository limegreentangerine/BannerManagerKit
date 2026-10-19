<?php

declare(strict_types=1);

namespace BannerManagerKit\Tests\Entity;

use PHPUnit\Framework\TestCase;
use BannerManagerKit\Entity\Slide;
use BannerManagerKit\Entity\SlideType;

final class SlideTypeTest extends TestCase
{
    public function testAccessors(): void
    {
        $type = (new SlideType())->setHandle('standard')->setName('Standard')->setExpressObject('obj');

        $this->assertSame('standard', $type->getHandle());
        $this->assertSame('Standard', $type->getName());
        $this->assertSame('obj', $type->getExpressObject());
    }

    public function testAddSlideIgnoresDuplicates(): void
    {
        $type = new SlideType();
        $slide = new Slide();

        $type->addSlide($slide);
        $type->addSlide($slide);

        $this->assertSame(1, $type->getSlidesCount());
    }

    public function testRemoveSlide(): void
    {
        $type = new SlideType();
        $slide = new Slide();
        $type->addSlide($slide);

        $type->removeSlide($slide);
        $type->removeSlide($slide);

        $this->assertSame(0, $type->getSlidesCount());
    }
}
